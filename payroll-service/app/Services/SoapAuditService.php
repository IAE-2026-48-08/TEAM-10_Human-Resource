<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SoapAuditService
{
    protected string $url;
    protected string $teamId;

    public function __construct()
    {
        $this->url    = env('SOAP_URL', 'https://iae-sso.virtualfri.id/soap/v1/audit');
        $this->teamId = 'TEAM-10' . env('SSO_API_KEY', 'KEY-MHS-238');
    }

    public function auditPayroll(array $payrollData, string $token): array
    {
        $logContent = json_encode([
            'employee_id'   => $payrollData['employee_id'],
            'employee_name' => $payrollData['employee_name'],
            'period'        => $payrollData['period_month'] . '/' . $payrollData['period_year'],
            'base_salary'   => $payrollData['base_salary'],
            'deduction'     => $payrollData['deduction'],
            'bonus'         => $payrollData['bonus'],
            'net_salary'    => $payrollData['net_salary'],
            'processed_at'  => $payrollData['processed_at'],
        ]);

        $xmlEnvelope = $this->buildXmlEnvelope($logContent);

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction'   => 'AuditRequest',
                ])
                ->withBody($xmlEnvelope, 'text/xml')
                ->post($this->url);

            if ($response->successful()) {
                $receiptNumber = $this->parseReceiptNumber($response->body());
                return [
                    'success'        => true,
                    'receipt_number' => $receiptNumber,
                ];
            }

            return [
                'success' => false,
                'message' => 'SOAP failed: ' . $response->status() . ' ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SOAP unavailable: ' . $e->getMessage(),
            ];
        }
    }

    private function buildXmlEnvelope(string $logContent): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
    <soap:Body>
        <iae:AuditRequest>
            <iae:TeamID>{$this->teamId}</iae:TeamID>
            <iae:ActivityName>PayrollProcessed</iae:ActivityName>
            <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
        </iae:AuditRequest>
    </soap:Body>
</soap:Envelope>
XML;
    }

    private function parseReceiptNumber(string $xmlResponse): string
    {
        try {
            $xml = simplexml_load_string($xmlResponse);
            $result = $xml->xpath('//*[local-name()="ReceiptNumber"]');
            return (string)($result[0] ?? 'RECEIPT-' . strtoupper(uniqid()));
        } catch (\Exception $e) {
            return 'RECEIPT-' . strtoupper(uniqid());
        }
    }
}