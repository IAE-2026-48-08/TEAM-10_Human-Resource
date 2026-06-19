<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class SsoJwtMiddleware
{
    /**
     * Handle an incoming request.
     * Validasi autentikasi via SSO JWT (Bearer) atau static API Key (X-IAE-KEY).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $user = null;

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = $matches[1];

            try {
                // Fetch JWKS keys from SSO server and cache them for 10 minutes
                $jwks = Cache::remember('sso_jwks', 600, function () {
                    $ssoUrl = env('SSO_URL', 'https://iae-sso.virtualfri.id');
                    $response = Http::withoutVerifying()->get("{$ssoUrl}/api/v1/auth/jwks");
                    if ($response->failed()) {
                        throw new \Exception('Gagal mengambil JWKS dari SSO Server.');
                    }
                    return $response->json();
                });

                if (empty($jwks) || !isset($jwks['keys'])) {
                    throw new \Exception('JWKS format tidak valid.');
                }

                // Decode and verify JWT
                $keys = JWK::parseKeySet($jwks);
                $decoded = JWT::decode($jwt, $keys);

                // Determine email and name from decoded claims
                $email = null;
                $name = null;

                if (isset($decoded->token_type) && $decoded->token_type === 'm2m') {
                    $email = $decoded->sub; // e.g. KEY-MHS-238
                    $name = $decoded->app->name ?? 'M2M App';
                } else {
                    $email = $decoded->profile->email ?? $decoded->sub;
                    $name = $decoded->profile->name ?? 'User SSO';
                }

                // Find or create local user
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => bcrypt(\Illuminate\Support\Str::random(16))
                    ]
                );

                // Log the user in to Laravel Auth guard
                Auth::login($user);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Token JWT tidak valid atau gagal diverifikasi: ' . $e->getMessage(),
                    'errors' => null,
                ], 401);
            }
        } else {
            // Fallback ke static API Key (X-IAE-KEY)
            $apiKey = $request->header('X-IAE-KEY');
            $validKey = env('IAE_KEY', '102022400173');

            if (!empty($apiKey) && $apiKey === $validKey) {
                // Find or create default admin system user
                $user = User::firstOrCreate(
                    ['email' => 'system@iae.id'],
                    [
                        'name' => 'System Admin',
                        'password' => bcrypt('SystemAdmin2026!')
                    ]
                );

                Auth::login($user);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. API Key / JWT Token tidak valid atau tidak ditemukan.',
                    'errors' => null,
                ], 401);
            }
        }

        return $next($request);
    }
}
