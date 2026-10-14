<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RestrictInternalApiAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();
        $isLocal = in_array($clientIp, ['127.0.0.1', '::1', 'localhost']);
        $expectedToken = env('INTERNAL_API_SECRET');
        $providedToken = $request->header('X-Service-Token') ?: $request->input('service_token');

        if (! $isLocal && (! $expectedToken || $providedToken !== $expectedToken)) {
            Log::warning("Percobaan injeksi API internal dari IP eksternal: {$clientIp} pada endpoint: /".$request->path());

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Akses ke API internal ini dibatasi.',
            ], 403);
        }

        return $next($request);
    }
}
