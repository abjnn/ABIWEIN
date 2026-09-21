<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Ajoute les protections HTTP communes à toutes les pages ABIWEIN. */
class AddSecurityHeaders
{
    /** Applique les en-têtes après la construction de la réponse Laravel. */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ces en-têtes empêchent l’intégration en iframe et l’interprétation de faux types de fichiers.
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // La politique autorise les ressources locales et la police officielle, mais aucun script tiers.
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // Une page authentifiée ne doit pas rester dans le cache partagé d’un ordinateur de ferme.
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        // HSTS est envoyé uniquement sous HTTPS afin de ne pas casser le serveur local en HTTP.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /** Construit une politique compatible avec Vite local sans ouvrir la production. */
    private function contentSecurityPolicy(): string
    {
        // En développement seulement, Vite peut servir les ressources depuis son port local.
        $localScripts = app()->isLocal() ? ' http://127.0.0.1:* http://localhost:*' : '';
        $localConnections = app()->isLocal() ? ' http://127.0.0.1:* http://localhost:* ws://127.0.0.1:* ws://localhost:*' : '';

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data:",
            "font-src 'self' https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "script-src 'self'{$localScripts}",
            "connect-src 'self'{$localConnections}",
        ]).';';
    }
}
