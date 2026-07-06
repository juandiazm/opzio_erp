<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * API Token Authentication Middleware
 * 
 * Validates incoming API requests from external systems (e.g., nini_admin_app)
 * using a shared secret token passed in the X-Api-Token header.
 */
class api_token_middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Api-Token');

        if (empty($token)) {
            return response()->json([
                'status' => 0,
                'message' => 'Token de autenticación no proporcionado',
                'data' => null,
            ], 401);
        }

        $validToken = config('services.nini_integration.api_token');

        if (empty($validToken)) {
            info('api_token_middleware: NINI_INTEGRATION_API_TOKEN not configured');
            return response()->json([
                'status' => 0,
                'message' => 'Integración no configurada en el servidor',
                'data' => null,
            ], 500);
        }

        if (!hash_equals($validToken, $token)) {
            info('api_token_middleware: Invalid token attempt from IP ' . $request->ip());
            return response()->json([
                'status' => 0,
                'message' => 'Token de autenticación inválido',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
