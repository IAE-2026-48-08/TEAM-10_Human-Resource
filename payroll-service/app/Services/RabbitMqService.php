<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RabbitMqService
{
    protected string $url;

    public function __construct()
    {
        $this->url = env('RABBITMQ_URL', 'https://iae-sso.virtualfri.id/api/v1/messages/publish');
    }

    public function publishPayrollProcessed(array $payrollData, string $token): array
    {
        $message = [
            'event'     => 'payroll.processed',
            'timestamp' => now('Asia/Jakarta')->toIso8601String(),
            'data'      => [
                'employee_id'    => $payrollData['employee_id'],
                'employee_name'  => $payrollData['employee_name'],
                'period_month'   => $payrollData['period_month'],
                'period_year'    => $payrollData['period_year'],
                'net_salary'     => $payrollData['net_salary'],
                'receipt_number' => $payrollData['receipt_number'] ?? null,
                'status'         => 'processed',
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->post($this->url, $message);

            if ($response->successful()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'message' => 'RabbitMQ failed: ' . $response->status() . ' ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'RabbitMQ unavailable: ' . $e->getMessage(),
            ];
        }
    }
}