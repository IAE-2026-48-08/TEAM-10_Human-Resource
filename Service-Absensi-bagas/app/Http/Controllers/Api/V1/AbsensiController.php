<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Service Absensi API',
    version: '1.0.0',
    description: 'API Service untuk mengelola rekap absensi bulanan karyawan. Bagian dari ekosistem HR untuk penggajian karyawan.',
    contact: new OA\Contact(
        name: 'Bagas - NIM 102022400319',
        email: 'bagas@example.com'
    )
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Absensi Service API v1'
)]
#[OA\SecurityScheme(
    securityScheme: 'ApiKeyAuth',
    type: 'apiKey',
    in: 'header',
    name: 'X-IAE-KEY',
    description: 'API Key untuk autentikasi. Gunakan NIM Mahasiswa sebagai value (contoh: 102022400319)'
)]
#[OA\Schema(
    schema: 'AbsensiResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'karyawan_id', type: 'integer', example: 1),
        new OA\Property(property: 'nama_karyawan', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'bulan', type: 'integer', example: 6),
        new OA\Property(property: 'tahun', type: 'integer', example: 2026),
        new OA\Property(property: 'total_hadir', type: 'integer', example: 20),
        new OA\Property(property: 'total_sakit', type: 'integer', example: 1),
        new OA\Property(property: 'total_izin', type: 'integer', example: 1),
        new OA\Property(property: 'total_alpha', type: 'integer', example: 0),
        new OA\Property(property: 'total_hari_kerja', type: 'integer', example: 22),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-06-04T14:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-06-04T14:00:00.000000Z'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'MetaInfo',
    properties: [
        new OA\Property(property: 'service_name', type: 'string', example: 'Absensi-Service'),
        new OA\Property(property: 'api_version', type: 'string', example: 'v1'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'success'),
        new OA\Property(property: 'message', type: 'string', example: 'Data retrieved successfully'),
        new OA\Property(property: 'data', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/MetaInfo'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'error'),
        new OA\Property(property: 'message', type: 'string', example: 'Resource not found'),
        new OA\Property(property: 'errors', type: 'object', nullable: true),
    ],
    type: 'object'
)]
class AbsensiController extends Controller
{
    /**
     * Helper method untuk format response sukses sesuai IAE-T2 Standard Integration Contract.
     */
    private function successResponse(mixed $data, string $message = 'Data retrieved successfully', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => [
                'service_name' => 'Absensi-Service',
                'api_version' => 'v1',
            ],
        ], $statusCode);
    }

    /**
     * Helper method untuk format response error sesuai IAE-T2 Standard Integration Contract.
     */
    private function errorResponse(string $message, int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    #[OA\Get(
        path: '/absensi',
        operationId: 'getAllAbsensi',
        summary: 'Menampilkan seluruh rekap absensi karyawan',
        description: 'Mengambil seluruh data rekap absensi bulanan karyawan. Mendukung filter berdasarkan bulan dan tahun via query parameter.',
        security: [['ApiKeyAuth' => []]],
        tags: ['Absensi'],
        parameters: [
            new OA\Parameter(
                name: 'bulan',
                in: 'query',
                required: false,
                description: 'Filter berdasarkan bulan (1-12)',
                schema: new OA\Schema(type: 'integer', example: 6)
            ),
            new OA\Parameter(
                name: 'tahun',
                in: 'query',
                required: false,
                description: 'Filter berdasarkan tahun',
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data absensi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AbsensiResource')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/MetaInfo'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - API Key tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Absensi::query();

        // Filter opsional berdasarkan karyawan_id
        if ($request->has('karyawan_id')) {
            $query->where('karyawan_id', $request->query('karyawan_id'));
        }

        // Filter opsional berdasarkan bulan
        if ($request->has('bulan')) {
            $query->where('bulan', $request->query('bulan'));
        }

        // Filter opsional berdasarkan tahun
        if ($request->has('tahun')) {
            $query->where('tahun', $request->query('tahun'));
        }

        $absensi = $query->orderBy('tahun', 'desc')
                         ->orderBy('bulan', 'desc')
                         ->orderBy('nama_karyawan', 'asc')
                         ->get();

        return $this->successResponse($absensi, 'Data retrieved successfully');
    }

    #[OA\Get(
        path: '/absensi/{id}',
        operationId: 'getAbsensiById',
        summary: 'Menampilkan rekap absensi berdasarkan karyawan',
        description: 'Mengambil detail rekap absensi berdasarkan ID absensi spesifik (perorangan).',
        security: [['ApiKeyAuth' => []]],
        tags: ['Absensi'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID absensi',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data absensi karyawan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AbsensiResource'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/MetaInfo'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Data absensi tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data absensi tidak ditemukan'),
                        new OA\Property(property: 'errors', type: 'object', nullable: true),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - API Key tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $absensi = Absensi::find($id);

        if (!$absensi) {
            return $this->errorResponse('Data absensi tidak ditemukan', 404);
        }

        return $this->successResponse($absensi, 'Data retrieved successfully');
    }

    #[OA\Post(
        path: '/absensi',
        operationId: 'createAbsensi',
        summary: 'Menambahkan atau generate rekap absensi bulanan',
        description: 'Menambahkan data rekap absensi bulanan baru untuk seorang karyawan. Jika data untuk karyawan pada bulan dan tahun yang sama sudah ada, akan mengembalikan error.',
        security: [['ApiKeyAuth' => []]],
        tags: ['Absensi'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Data rekap absensi bulanan',
            content: new OA\JsonContent(
                required: ['karyawan_id', 'nama_karyawan', 'bulan', 'tahun', 'total_hadir', 'total_sakit', 'total_izin', 'total_alpha', 'total_hari_kerja'],
                properties: [
                    new OA\Property(property: 'karyawan_id', type: 'integer', example: 11, description: 'ID karyawan dari Service Data Karyawan'),
                    new OA\Property(property: 'nama_karyawan', type: 'string', example: 'Budi Santoso', description: 'Nama lengkap karyawan'),
                    new OA\Property(property: 'bulan', type: 'integer', example: 6, description: 'Bulan rekap (1-12)'),
                    new OA\Property(property: 'tahun', type: 'integer', example: 2026, description: 'Tahun rekap'),
                    new OA\Property(property: 'total_hadir', type: 'integer', example: 20, description: 'Jumlah hari hadir'),
                    new OA\Property(property: 'total_sakit', type: 'integer', example: 1, description: 'Jumlah hari sakit'),
                    new OA\Property(property: 'total_izin', type: 'integer', example: 1, description: 'Jumlah hari izin'),
                    new OA\Property(property: 'total_alpha', type: 'integer', example: 0, description: 'Jumlah hari alpha/tanpa keterangan'),
                    new OA\Property(property: 'total_hari_kerja', type: 'integer', example: 22, description: 'Total hari kerja dalam bulan'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Data absensi berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data absensi berhasil ditambahkan'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AbsensiResource'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/MetaInfo'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Validasi gagal'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['karyawan_id' => ['Field karyawan_id wajib diisi.']]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - API Key tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'karyawan_id' => 'required|integer|min:1',
                'nama_karyawan' => 'nullable|string|max:255',
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000|max:2100',
                'total_hadir' => 'required|integer|min:0',
                'total_sakit' => 'required|integer|min:0',
                'total_izin' => 'required|integer|min:0',
                'total_alpha' => 'required|integer|min:0',
                'total_hari_kerja' => 'required|integer|min:1|max:31',
            ]);

            // Ambil data karyawan secara internal dari Data Karyawan Service
            $employeeServiceUrl = env('EMPLOYEE_SERVICE_URL', 'http://employee-web:80');
            $employeeServiceKey = env('EMPLOYEE_SERVICE_KEY', '102022400090');

            $employeeResponse = Http::withoutVerifying()
                ->withHeaders(['X-IAE-KEY' => $employeeServiceKey])
                ->get("{$employeeServiceUrl}/api/v1/employees/{$validated['karyawan_id']}");

            if ($employeeResponse->failed()) {
                return $this->errorResponse(
                    'Karyawan tidak ditemukan atau Service Data Karyawan tidak aktif: ' . $employeeResponse->body(),
                    404
                );
            }

            $employeeName = $employeeResponse->json('data.nama');
            if (empty($employeeName)) {
                return $this->errorResponse('Data karyawan tidak memiliki nama yang valid.', 400);
            }

            $validated['nama_karyawan'] = $employeeName;

            // Cek apakah data absensi untuk karyawan di bulan & tahun ini sudah ada
            $exists = Absensi::where('karyawan_id', $validated['karyawan_id'])
                             ->where('bulan', $validated['bulan'])
                             ->where('tahun', $validated['tahun'])
                             ->exists();

            if ($exists) {
                return $this->errorResponse(
                    'Data absensi untuk karyawan ini pada bulan dan tahun tersebut sudah ada',
                    409
                );
            }

            // Simpan absensi lokal terlebih dahulu
            $absensi = Absensi::create($validated);

            // ==========================================
            // INTEGRASI TUGAS 3: SOAP & RabbitMQ
            // ==========================================
            try {
                $ssoUrl = env('SSO_BASE_URL', 'https://iae-sso.virtualfri.id');
                $apiKey = env('SSO_API_KEY', 'KEY-MHS-409');

                // 1. Dapatkan M2M Token dari SSO
                $m2mToken = Cache::remember('sso_m2m_token', 3000, function () use ($ssoUrl, $apiKey) {
                    $response = Http::withoutVerifying()->post("{$ssoUrl}/api/v1/auth/token", [
                        'api_key' => $apiKey,
                        'nim'     => env('API_KEY', '102022400319'),
                    ]);
                    if ($response->failed()) {
                        throw new \Exception('Gagal mendapatkan M2M token dari SSO server.');
                    }
                    return $response->json('token');
                });

                // 2. Kirim Log Audit via SOAP XML
                $soapXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>' . env('TEAM_ID', 'TEAM-10') . '</iae:TeamID>
      <iae:ActivityName>AbsensiCreated</iae:ActivityName>
      <iae:LogContent><![CDATA[' . json_encode($absensi) . ']]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>';

                $soapResponse = Http::withoutVerifying()
                    ->withHeaders([
                        'Content-Type' => 'text/xml; charset=utf-8',
                        'Authorization' => 'Bearer ' . $m2mToken
                    ])
                    ->withBody($soapXml, 'text/xml')
                    ->post("{$ssoUrl}/soap/v1/audit");

                $receiptNumber = null;
                if ($soapResponse->successful()) {
                    if (preg_match('/<iae:ReceiptNumber>(.*?)<\/iae:ReceiptNumber>/', $soapResponse->body(), $matches)) {
                        $receiptNumber = $matches[1];
                        // Simpan ReceiptNumber ke absensi lokal
                        $absensi->update(['receipt_number' => $receiptNumber]);
                    }
                } else {
                    Log::error('SOAP Audit Request gagal: ' . $soapResponse->body());
                }

                // 3. Kirim Event Notification ke RabbitMQ via HTTP Publisher Gateway
                $rabbitResponse = Http::withoutVerifying()
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $m2mToken
                    ])
                    ->post("{$ssoUrl}/api/v1/messages/publish", [
                        'routing_key' => 'absensi.created',
                        'message' => [
                            'event' => 'absensi.created',
                            'data' => $absensi->toArray(),
                            'timestamp' => now()->toIso8601String()
                        ]
                    ]);

                if ($rabbitResponse->failed()) {
                    Log::error('RabbitMQ Event Publish gagal: ' . $rabbitResponse->body());
                }

            } catch (\Exception $integrationEx) {
                Log::error('Gagal menjalankan integrasi SOAP/RabbitMQ: ' . $integrationEx->getMessage());
                // Jangan menggagalkan request jika service dosen bermasalah, tapi log error-nya
            }

            return $this->successResponse($absensi, 'Data absensi berhasil ditambahkan', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse('Validasi gagal', 422, $e->errors());
        }
    }
}
