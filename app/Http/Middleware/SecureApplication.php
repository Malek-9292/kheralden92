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
