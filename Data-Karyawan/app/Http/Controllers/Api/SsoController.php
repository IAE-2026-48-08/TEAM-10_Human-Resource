<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocalUser;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class SsoController extends Controller
{
    private string $ssoUrl = 'https://iae-sso.virtualfri.id';

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login user via SSO',
        tags: ['SSO'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'warga30@ktp.iae.id'),
                    new OA\Property(property: 'password', type: 'string', example: 'KtpDigital2026!')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $response = Http::withOptions(['verify' => false, 'timeout' => 30])
            ->post("{$this->ssoUrl}/api/v1/auth/token", [
                'email' => $request->email,
                'password' => $request->password
            ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'SSO login failed',
                'errors' => $response->json()
            ], 401);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not found in SSO response',
                'errors' => null
            ], 401);
        }

        $payload = $this->verifyJwt($accessToken);

        if (!$payload) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JWT token',
                'errors' => null
            ], 401);
        }

        $localUser = LocalUser::updateOrCreate(
            ['email' => $payload->email ?? $request->email],
            [
                'name' => $payload->name ?? $payload->email ?? $request->email,
                'sso_sub' => $payload->sub ?? null,
                'role' => $this->mapRole($payload)
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'access_token' => $accessToken,
                'local_user' => $localUser,
            ],
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/login-m2m',
        summary: 'Login M2M via API Key',
        tags: ['SSO'],
        responses: [
            new OA\Response(response: 200, description: 'Token retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function loginM2M()
    {
        $response = Http::withOptions(['verify' => false, 'timeout' => 30])
            ->post("{$this->ssoUrl}/api/v1/auth/token", [
                'api_key' => 'KEY-MHS-153'
            ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'M2M login failed',
                'errors' => $response->json()
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'M2M token retrieved',
            'data' => $response->json(),
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    #[OA\Get(
        path: '/api/v1/auth/health',
        summary: 'SSO Health Check',
        tags: ['SSO'],
        responses: [
            new OA\Response(response: 200, description: 'SSO is up')
        ]
    )]
    public function health()
    {
        $response = Http::withOptions(['verify' => false, 'timeout' => 30])
            ->get("{$this->ssoUrl}/health");

        return response()->json([
            'status' => 'success',
            'message' => 'SSO health check',
            'data' => $response->json(),
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    private function verifyJwt(string $token): mixed
    {
        try {
            $jwksResponse = Http::withOptions(['verify' => false, 'timeout' => 30])
                ->get("{$this->ssoUrl}/api/v1/auth/jwks");
            $jwks = $jwksResponse->json();

            $keys = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keys);

            return $decoded;
        } catch (\Exception $e) {
            Log::error('JWT verification failed: ' . $e->getMessage());
            return null;
        }
    }

    private function mapRole(object $payload): string
    {
        $ssoRole = $payload->role ?? $payload->roles ?? null;

        return match(true) {
            str_contains(strtolower((string)$ssoRole), 'admin') => 'admin',
            str_contains(strtolower((string)$ssoRole), 'operator') => 'operator',
            default => 'viewer'
        };
    }
}