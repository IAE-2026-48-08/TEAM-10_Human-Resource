<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
     public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-IAE-KEY');

        if (!$apiKey || $apiKey !== config('app.iae_key')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing X-IAE-KEY header',
                'errors'  => null,
            ], 401);
        }

        return $next($request);
    }
}
