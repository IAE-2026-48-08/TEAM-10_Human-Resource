<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class SoapController extends Controller
{
    private string $ssoUrl = 'https://iae-sso.virtualfri.id';

    #[OA\Post(
        path: '/api/v1/audit/send',
        summary: 'Kirim audit ke SOAP server',
        tags: ['SOAP Audit'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'activity_name', type: 'string', example: 'EmployeeDataAccess'),
                    new OA\Property(property: 'transaction_data', type: 'string', example: '{"action":"GET","resource":"employees"}'),
                    new OA\Property(property: 'bearer_token', type: 'string', example: 'eyJ...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Audit sent'),
            new OA\Response(response: 500, description: 'SOAP error')
        ]
    )]
    public function sendAudit(Request $request)
    {
        // Gunakan token dari request kalau ada, kalau tidak ambil M2M baru
        $token = $request->input('bearer_token');

        if (!$token) {
            $tokenResponse = Http::withOptions(['verify' => false, 'timeout' => 30])
                ->post("{$this->ssoUrl}/api/v1/auth/token", [
                    'api_key' => 'KEY-MHS-153'
                ]);

            if (!$tokenResponse->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get M2M token',
                    'errors' => null
                ], 500);
            }

            $token = $tokenResponse->json()['token'];
        }

        $activityName = $request->input('activity_name', 'EmployeeDataAccess');
        $transactionData = $request->input('transaction_data', json_encode([
            'action' => 'GET',
            'resource' => 'employees',
            'timestamp' => now()->toISOString()
        ]));

        $soapXml = $this->buildSoapEnvelope($activityName, $transactionData);

        $soapResponse = Http::withOptions(['verify' => false, 'timeout' => 30])
            ->withToken($token)
            ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8'])
            ->withBody($soapXml, 'text/xml')
            ->post("{$this->ssoUrl}/soap/v1/audit");

        $receiptNumber = $this->parseReceiptNumber($soapResponse->body());

        return response()->json([
            'status' => 'success',
            'message' => 'Audit sent successfully',
            'data' => [
                'receipt_number' => $receiptNumber,
                'soap_response' => $soapResponse->body()
            ],
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    private function buildSoapEnvelope(string $activityName, string $transactionData): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <AuditRequest>
            <TeamID>TEAM-10</TeamID>
            <ActivityName>' . htmlspecialchars($activityName) . '</ActivityName>
            <LogContent><![CDATA[' . $transactionData . ']]></LogContent>
        </AuditRequest>
    </soapenv:Body>
</soapenv:Envelope>';
}

    private function parseReceiptNumber(string $xmlBody): ?string
{
    try {
        // Cari pattern IAE-LOG-xxxx di response
        preg_match('/IAE-LOG-[A-Z0-9-]+/', $xmlBody, $matches);
        return $matches[0] ?? null;
    } catch (\Exception $e) {
        Log::error('SOAP parse error: ' . $e->getMessage());
        return null;
    }
}
}