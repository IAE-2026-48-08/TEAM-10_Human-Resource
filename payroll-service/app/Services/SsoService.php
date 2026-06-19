<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SsoService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('SSO_URL', 'https://iae-sso.virtualfri.id');
    }

    public function loginWithApiKey(): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/v1/auth/token", [
                'api_key' => env('SSO_API_KEY', 'KEY-MHS-238'),
                'nim'     => env('IAE_KEY', '102022400173'),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'token'   => $response->json('token') ?? $response->json('access_token'),
                    'data'    => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'SSO login failed',
                'status'  => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SSO unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function loginWithCredentials(): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/v1/auth/token", [
                'email'    => env('SSO_EMAIL', 'warga34@ktp.iae.id'),
                'password' => env('SSO_PASSWORD', 'KtpDigital2026!'),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'token'   => $response->json('token') ?? $response->json('access_token'),
                    'data'    => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'SSO login failed',
                'status'  => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SSO unavailable: ' . $e->getMessage(),
            ];
        }
    }
}