<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\SsoService;
use App\Services\SoapAuditService;
use App\Services\RabbitMqService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class PayrollController extends Controller
{
    protected SsoService $sso;
    protected SoapAuditService $soap;
    protected RabbitMqService $rabbitmq;

    public function __construct(SsoService $sso, SoapAuditService $soap, RabbitMqService $rabbitmq)
    {
        $this->sso      = $sso;
        $this->soap     = $soap;
        $this->rabbitmq = $rabbitmq;
    }

    #[OA\Get(
        path: "/api/v1/payrolls",
        summary: "Menampilkan seluruh data penggajian",
        tags: ["Payroll"],
        security: [["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Data retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $payrolls = Payroll::all();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data retrieved successfully',
            'data'    => $payrolls,
            'meta'    => [
                'service_name' => 'Payroll-Service',
                'api_version'  => 'v1',
                'total'        => $payrolls->count(),
            ],
        ], 200);
    }

    #[OA\Get(
        path: "/api/v1/payrolls/{id}",
        summary: "Menampilkan detail penggajian berdasarkan ID",
        tags: ["Payroll"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Data retrieved successfully"),
            new OA\Response(response: 404, description: "Payroll not found"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function show($id)
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payroll data not found',
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Data retrieved successfully',
            'data'    => $payroll,
            'meta'    => [
                'service_name' => 'Payroll-Service',
                'api_version'  => 'v1',
            ],
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/payrolls/process",
        summary: "Memproses penggajian karyawan",
        tags: ["Payroll"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["employee_id","period_month","period_year"],
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", example: 1),
                    new OA\Property(property: "period_month", type: "integer", example: 6),
                    new OA\Property(property: "period_year", type: "integer", example: 2026),
                    new OA\Property(property: "bonus", type: "number", example: 500000),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Payroll processed successfully"),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function process(Request $request)
    {
        $validated = $request->validate([
            'employee_id'   => 'required|integer',
            'period_month'  => 'required|integer|min:1|max:12',
            'period_year'   => 'required|integer|min:2000',
            'bonus'         => 'nullable|numeric|min:0',
        ]);

        // 1. Ambil data karyawan dari Data Karyawan Service
        $employeeServiceUrl = env('EMPLOYEE_SERVICE_URL', 'http://employee-web:80');
        $employeeServiceKey = env('EMPLOYEE_SERVICE_KEY', '102022400090');

        $employeeResponse = Http::withoutVerifying()
            ->withHeaders(['X-IAE-KEY' => $employeeServiceKey])
            ->get("{$employeeServiceUrl}/api/v1/employees/{$validated['employee_id']}");

        if ($employeeResponse->failed()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data karyawan tidak ditemukan atau Service Data Karyawan tidak aktif.',
                'errors'  => $employeeResponse->body(),
            ], 404);
        }

        $employeeData = $employeeResponse->json('data');
        $employeeName = $employeeData['nama'] ?? null;
        $baseSalary = $employeeData['gaji_pokok'] ?? null;

        if (!$employeeName || !$baseSalary) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Format data karyawan yang dikembalikan tidak valid.',
                'errors'  => null,
            ], 400);
        }

        // 2. Ambil data kehadiran dari Service Absensi
        $absensiServiceUrl = env('ABSENSI_SERVICE_URL', 'http://absensi-app:80');
        $absensiServiceKey = env('ABSENSI_SERVICE_KEY', '102022400319');

        $absensiResponse = Http::withoutVerifying()
            ->withHeaders(['X-IAE-KEY' => $absensiServiceKey])
            ->get("{$absensiServiceUrl}/api/v1/absensi", [
                'karyawan_id' => $validated['employee_id'],
                'bulan'       => $validated['period_month'],
                'tahun'       => $validated['period_year'],
            ]);

        if ($absensiResponse->failed()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data absensi tidak ditemukan atau Service Absensi tidak aktif.',
                'errors'  => $absensiResponse->body(),
            ], 404);
        }

        $absensiList = $absensiResponse->json('data');
        if (empty($absensiList)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Rekap absensi untuk karyawan ID {$validated['employee_id']} pada bulan {$validated['period_month']}/{$validated['period_year']} tidak ditemukan.",
                'errors'  => null,
            ], 404);
        }

        $absensiRecord = $absensiList[0];
        $totalPresent  = $absensiRecord['total_hadir'] ?? 22;
        $totalAbsent   = $absensiRecord['total_alpha'] ?? 0;
        $totalLeave    = ($absensiRecord['total_izin'] ?? 0) + ($absensiRecord['total_sakit'] ?? 0);
        $totalWorkDays = $absensiRecord['total_hari_kerja'] ?? 22;

        // Hitung gaji
        $bonus     = $validated['bonus'] ?? 0;
        $deduction = ($baseSalary / $totalWorkDays) * $totalAbsent;
        $netSalary = $baseSalary - $deduction + $bonus;
        $now       = Carbon::now();

        // Step 1: Login SSO
        $ssoResult = $this->sso->loginWithApiKey();
        $token     = $ssoResult['success'] ? $ssoResult['token'] : null;

        // Step 2: Simpan data penggajian
        $payroll = Payroll::create([
            'employee_id'   => $validated['employee_id'],
            'employee_name' => $employeeName,
            'period_month'  => $validated['period_month'],
            'period_year'   => $validated['period_year'],
            'base_salary'   => $baseSalary,
            'total_present' => $totalPresent,
            'total_absent'  => $totalAbsent,
            'total_leave'   => $totalLeave,
            'deduction'     => round($deduction, 2),
            'bonus'         => $bonus,
            'net_salary'    => round($netSalary, 2),
            'status'        => 'processed',
            'processed_at'  => $now,
        ]);

        $receiptNumber = null;

        // Step 3: SOAP Audit (jika SSO berhasil)
        if ($token) {
            $soapResult = $this->soap->auditPayroll([
                'employee_id'   => $payroll->employee_id,
                'employee_name' => $payroll->employee_name,
                'period_month'  => $payroll->period_month,
                'period_year'   => $payroll->period_year,
                'base_salary'   => $payroll->base_salary,
                'deduction'     => $payroll->deduction,
                'bonus'         => $payroll->bonus,
                'net_salary'    => $payroll->net_salary,
                'processed_at'  => $now->toDateTimeString(),
            ], $token);

            if ($soapResult['success']) {
                $receiptNumber = $soapResult['receipt_number'];
                $payroll->update(['receipt_number' => $receiptNumber]);
            }

            // Step 4: RabbitMQ Publish
            $this->rabbitmq->publishPayrollProcessed([
                'employee_id'    => $payroll->employee_id,
                'employee_name'  => $payroll->employee_name,
                'period_month'   => $payroll->period_month,
                'period_year'    => $payroll->period_year,
                'net_salary'     => $payroll->net_salary,
                'receipt_number' => $receiptNumber,
            ], $token);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Payroll processed successfully',
            'data'    => $payroll->fresh(),
            'meta'    => [
                'service_name'   => 'Payroll-Service',
                'api_version'    => 'v1',
                'sso_connected'  => $token !== null,
                'soap_audited'   => $receiptNumber !== null,
            ],
        ], 201);
    }
}