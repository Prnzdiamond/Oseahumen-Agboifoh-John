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
        // IMPORTANT: read config(), never env(). Production deploys run
        // `php artisan optimize` (config:cache), after which env() returns null.
        // A null-returning env() here previously (a) let a null bypass secret
        // match a missing header and skip the allowlist entirely, and
        // (b) emptied the allowlist so every header-bearing request 403'd.
        // See config/services.php 'frontend'.
        $allowedOrigins = array_filter(
            explode(',', config('services.frontend.allowed_origins', ''))
        );

        // Trusted server-to-server callers (Nuxt SSR, sitemap generation) send a
        // shared secret instead of a browser Origin. Constant-time compare, and
        // require BOTH sides to be non-empty so a missing/blank token can never
        // authenticate — closes the previous `null === null` bypass.
        $serverToken = (string) config('services.frontend.server_token', '');
        $sentToken = (string) $request->header('X-Server-Token', '');

        if ($serverToken !== '' && $sentToken !== '' && hash_equals($serverToken, $sentToken)) {
            return $next($request);
        }

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
            ! $this->isValidOrigin($origin, $allowedOrigins) &&
            ! $this->isValidReferer($referer, $allowedOrigins)
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
        if (! $origin) {
            return false;
        }

        return in_array($origin, $allowedOrigins);
    }

    private function isValidReferer(?string $referer, array $allowedOrigins): bool
    {
        if (! $referer) {
            return false;
        }

        foreach ($allowedOrigins as $allowedOrigin) {
            if (str_starts_with($referer, $allowedOrigin)) {
                return true;
            }
        }

        return false;
    }
}
