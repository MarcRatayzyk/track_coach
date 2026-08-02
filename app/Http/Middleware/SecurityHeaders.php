<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), microphone=(self), geolocation=(), payment=(), usb=()',
        );
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            "'wasm-unsafe-eval'",
            'https://*.posthog.com',
            'https://*.i.posthog.com',
        ];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'];
        $connectSrc = array_values(array_unique(array_filter([
            "'self'",
            $this->originFromUrl((string) config('trackcoach.posthog.host')),
            $this->originFromUrl((string) config('trackcoach.posthog.ui_host')),
            'https://*.posthog.com',
            'https://*.i.posthog.com',
            $this->originFromUrl((string) config('filesystems.disks.s3.url')),
            $this->originFromUrl((string) config('filesystems.disks.s3.endpoint')),
            'https://*.r2.cloudflarestorage.com',
            'https://checkout.stripe.com',
            'https://api.stripe.com',
            'https://github.com',
            'https://objects.githubusercontent.com',
            ...$this->reverbConnectSources(),
            ...$this->viteDevSources('connect'),
        ])));

        $scriptSrc = [...$scriptSrc, ...$this->viteDevSources('script')];
        $styleSrc = [...$styleSrc, ...$this->viteDevSources('style')];

        $directives = [
            "default-src 'self'",
            'base-uri \'self\'',
            'object-src \'none\'',
            'frame-ancestors \'self\'',
            'form-action \'self\' https://checkout.stripe.com https://billing.stripe.com https://*.stripe.com',
            'script-src '.implode(' ', array_unique($scriptSrc)),
            'style-src '.implode(' ', array_unique($styleSrc)),
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "media-src 'self' blob: data: https:",
            "worker-src 'self' blob:",
            "child-src 'self' blob:",
            'connect-src '.implode(' ', $connectSrc),
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://checkout.stripe.com https://billing.stripe.com",
        ];

        return implode('; ', $directives);
    }

    /**
     * @return list<string>
     */
    private function reverbConnectSources(): array
    {
        $host = trim((string) config('broadcasting.connections.reverb.options.host'));
        if ($host === '') {
            return ['wss:', 'ws:'];
        }

        $host = preg_replace('#^https?://#', '', $host) ?: $host;

        return [
            "wss://{$host}",
            "ws://{$host}",
            "https://{$host}",
            "http://{$host}",
        ];
    }

    /**
     * @param  'script'|'style'|'connect'  $kind
     * @return list<string>
     */
    private function viteDevSources(string $kind): array
    {
        if (! app()->environment('local')) {
            return [];
        }

        $origins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];

        if ($kind === 'connect') {
            return [
                ...$origins,
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
            ];
        }

        return $origins;
    }

    private function originFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];
        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
