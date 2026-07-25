<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Active l'isolation cross-origin (SharedArrayBuffer) pour FFmpeg.wasm
 * lors de la compression vidéo côté navigateur.
 */
class EnableCrossOriginIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'credentialless');

        return $response;
    }
}
