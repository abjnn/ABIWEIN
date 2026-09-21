<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Refuse une route privée lorsque le rôle de l’utilisateur n’est pas autorisé. */
class EnsureUserHasRole
{
    /**
     * MODIFICATION : les rôles transmis après "role:" dans routes/web.php
     * sont comparés ici au rôle enum de l’utilisateur connecté.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
