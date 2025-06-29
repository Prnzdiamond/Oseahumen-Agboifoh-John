<?php
// app/Http/Middleware/ValidateOrigin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get allowed origins from environment
        $allowedOrigins = array_filter(explode(',', env('ALLOWED_ORIGINS', '')));

        // If no origins configured, block all requests
        if (empty($allowedOrigins)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $origin = $request->header('Origin');

        // For preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            if ($origin && in_array($origin, $allowedOrigins)) {
                return response('', 200)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                    ->header('Access-Control-Max-Age', '86400');
            }
            return response('', 403);
        }

        // Validate origin for actual requests
        $referer = $request->header('Referer');

        if (
            !$this->isValidOrigin($origin, $allowedOrigins) &&
            !$this->isValidReferer($referer, $allowedOrigins)
        ) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Process the request
        $response = $next($request);

        // Add CORS headers to response
        if ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        }

        return $response;
    }

    private function isValidOrigin(?string $origin, array $allowedOrigins): bool
    {
        if (!$origin)
            return false;
        return in_array($origin, $allowedOrigins);
    }

    private function isValidReferer(?string $referer, array $allowedOrigins): bool
    {
        if (!$referer)
            return false;

        foreach ($allowedOrigins as $allowedOrigin) {
            if (str_starts_with($referer, $allowedOrigin)) {
                return true;
            }
        }

        return false;
    }
}
