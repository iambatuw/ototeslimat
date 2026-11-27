<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);
        $nonce = Vite::cspNonce();

        // Geliştirmede Vite sunucusuna izin verilir; canlıda yalnızca kendi alan adı.
        $dev = app()->environment('local') ? ' http://localhost:* ws://localhost:* http://127.0.0.1:* ws://127.0.0.1:*' : '';

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$dev}",
            "style-src 'self' 'nonce-{$nonce}'{$dev}",
            "img-src 'self' data: https:", // oyun kapakları başka bir siteden (https) gelebilir
            "font-src 'self' data:{$dev}",
            "connect-src 'self'{$dev}",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
