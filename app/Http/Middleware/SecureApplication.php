<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SecureApplication
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldForceHttps()) {
            URL::forceScheme('https');

            if (! $request->secure()) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // Report-only first so staging can observe without breaking Livewire/CDN assets.
        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: blob: https:; font-src 'self' https://fonts.gstatic.com data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; connect-src 'self'",
        );

        if ($this->shouldForceHttps() || $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function shouldForceHttps(): bool
    {
        if ((bool) config('app.force_https')) {
            return true;
        }

        return app()->environment('production') && str_starts_with((string) config('app.url'), 'https://');
    }
}
