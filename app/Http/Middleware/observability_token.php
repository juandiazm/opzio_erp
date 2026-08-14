<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class observability_token
{
    public function handle(Request $request, Closure $next)
    {
        if (config('services.observability.loopback_only') && ! $this->isLoopback($request->ip())) {
            return response()->json(['message' => 'Loopback access required.'], 403);
        }

        $token = config('services.observability.token');
        $providedToken = $request->header('X-Opzio-Observer-Token');

        if (empty($token) || empty($providedToken) || ! hash_equals($token, $providedToken)) {
            return response()->json(['message' => 'Invalid observer token.'], 401);
        }

        $contentLength = (int) $request->header('Content-Length', 0);
        $maxPayloadBytes = (int) config('services.observability.max_payload_bytes');
        $bodyLength = strlen($request->getContent());

        if ($maxPayloadBytes > 0 && max($contentLength, $bodyLength) > $maxPayloadBytes) {
            return response()->json(['message' => 'Payload too large.'], 413);
        }

        return $next($request);
    }

    private function isLoopback(?string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1'], true);
    }
}