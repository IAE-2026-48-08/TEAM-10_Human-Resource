<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class RabbitMqController extends Controller
{
    private string $ssoUrl = 'https://iae-sso.virtualfri.id';

    #[OA\Post(
        path: '/api/v1/messages/publish',
        summary: 'Publish event ke RabbitMQ',
        tags: ['RabbitMQ'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'event_type', type: 'string', example: 'EmployeeCreated'),
                    new OA\Property(property: 'payload', type: 'string', example: '{"employee_id":1}'),
                    new OA\Property(property: 'bearer_token', type: 'string', example: 'eyJ...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Event published'),
            new OA\Response(response: 500, description: 'Publish error')
        ]
    )]
    public function publish(Request $request)
    {
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

        $eventType = $request->input('event_type', 'EmployeeDataAccess');
        $payload = $request->input('payload', json_encode([
            'service' => 'Data-Karyawan-Service',
            'team' => 'TEAM-10',
            'nim' => '102022400090',
            'timestamp' => now()->toISOString()
        ]));

        $messageBody = json_encode([
            'event_type' => $eventType,
            'team_id' => 'TEAM-10',
            'service_name' => 'Data-Karyawan-Service',
            'payload' => $payload,
            'timestamp' => now()->toISOString()
        ]);

        $response = Http::withOptions(['verify' => false, 'timeout' => 30])
            ->withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->ssoUrl}/api/v1/messages/publish", [
                'message' => $messageBody
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Event published to RabbitMQ',
            'data' => $response->json(),
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }
}