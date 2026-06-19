<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\SsoService;
use App\Services\SoapAuditService;
use App\Services\RabbitMqService;
use Illuminate\Http\Request;
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
                required: ["employee_id","employee_name","period_month","period_year","base_salary","total_present","total_absent","total_leave"],
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", example: 4),
                    new OA\Property(property: "employee_name", type: "string", example: "Dewi Lestari"),
                    new OA\Property(property: "period_month", type: "integer", example: 6),
                    new OA\Property(property: "period_year", type: "integer", example: 2025),
                    new OA\Property(property: "base_salary", type: "number", example: 5000000),
                    new OA\Property(property: "total_present", type: "integer", example: 20),
                    new OA\Property(property: "total_absent", type: "integer", example: 2),
                    new OA\Property(property: "total_leave", type: "integer", example: 0),
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
            'employee_name' => 'required|string',
            'period_month'  => 'required|integer|min:1|max:12',
            'period_year'   => 'required|integer|min:2000',
            'base_salary'   => 'required|numeric|min:0',
            'total_present' => 'required|integer|min:0',
            'total_absent'  => 'required|integer|min:0',
            'total_leave'   => 'required|integer|min:0',
            'bonus'         => 'nullable|numeric|min:0',
        ]);

        // Hitung gaji
        $workDays  = 22;
        $bonus     = $validated['bonus'] ?? 0;
        $deduction = ($validated['base_salary'] / $workDays) * $validated['total_absent'];
        $netSalary = $validated['base_salary'] - $deduction + $bonus;
        $now       = Carbon::now();

        // Step 1: Login SSO
        $ssoResult = $this->sso->loginWithApiKey();
        $token     = $ssoResult['success'] ? $ssoResult['token'] : null;

        // Step 2: Simpan data penggajian
        $payroll = Payroll::create([
            'employee_id'   => $validated['employee_id'],
            'employee_name' => $validated['employee_name'],
            'period_month'  => $validated['period_month'],
            'period_year'   => $validated['period_year'],
            'base_salary'   => $validated['base_salary'],
            'total_present' => $validated['total_present'],
            'total_absent'  => $validated['total_absent'],
            'total_leave'   => $validated['total_leave'],
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