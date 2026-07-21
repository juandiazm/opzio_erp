<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Web Integration API Token Middleware
 *
 * Validates incoming API requests from opzio_web using a shared secret
 * passed in the X-Api-Token header.
 */
class web_api_token_middleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Api-Token');

        if (empty($token)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Token de autenticación no proporcionado',
            ], 401);
        }

        $validToken = config('services.web_integration.api_token');

        if (empty($validToken)) {
            info('web_api_token_middleware: WEB_INTEGRATION_API_TOKEN not configured');
            return response()->json([
                'status'  => 0,
                'message' => 'Integración no configurada en el servidor',
            ], 500);
        }

        if (!hash_equals($validToken, $token)) {
            info('web_api_token_middleware: Invalid token from IP ' . $request->ip());
            return response()->json([
                'status'  => 0,
                'message' => 'Token de autenticación inválido',
            ], 403);
        }

        return $next($request);
    }
}
