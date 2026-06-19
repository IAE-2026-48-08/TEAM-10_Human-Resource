<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     * Validasi autentikasi via Federated SSO (Bearer JWT) atau API Key (X-IAE-KEY),
     * lalu lakukan pemetaan user ke tabel roles lokal dan otorisasi.
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
                    $response = Http::withoutVerifying()->get('https://iae-sso.virtualfri.id/api/v1/auth/jwks');
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
                $roleName = 'warga'; // Default role

                if (isset($decoded->token_type) && $decoded->token_type === 'm2m') {
                    $email = $decoded->sub; // e.g. KEY-MHS-409
                    $name = $decoded->app->name ?? 'M2M App';
                    $roleName = 'admin'; // M2M tokens map to admin/system role
                } else {
                    $email = $decoded->profile->email ?? $decoded->sub;
                    $name = $decoded->profile->name ?? 'User SSO';

                    // Map based on email pattern
                    if (str_contains(strtolower($email), 'warga')) {
                        $roleName = 'warga';
                    } elseif (str_contains(strtolower($email), 'karyawan') || str_contains(strtolower($email), 'admin')) {
                        $roleName = 'admin';
                    } else {
                        $roleName = 'karyawan';
                    }
                }

                // Find or create local user
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => bcrypt(\Illuminate\Support\Str::random(16))
                    ]
                );

                // Find or create role and map user to it
                $role = Role::firstOrCreate(['name' => $roleName]);
                if (!$user->hasRole($roleName)) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }

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
            // Fallback to API Key (X-IAE-KEY)
            $apiKey = $request->header('X-IAE-KEY');
            $validKey = config('app.api_key');

            if (!empty($apiKey) && $apiKey === $validKey) {
                // Find or create default admin system user
                $user = User::firstOrCreate(
                    ['email' => 'system@iae.id'],
                    [
                        'name' => 'System Admin',
                        'password' => bcrypt('SystemAdmin2026!')
                    ]
                );

                $role = Role::firstOrCreate(['name' => 'admin']);
                if (!$user->hasRole('admin')) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }

                Auth::login($user);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. API Key / JWT Token tidak valid atau tidak ditemukan.',
                    'errors' => null,
                ], 401);
            }
        }

        // Role check (RBAC): Hanya admin yang boleh membuat rekap absensi baru (POST)
        if ($request->isMethod('post') && !$user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. User tidak memiliki hak akses untuk melakukan transaksi ini.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
