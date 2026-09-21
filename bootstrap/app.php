<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render termine HTTPS devant Apache ; ces en-têtes permettent à Laravel de reconnaître l'URL sécurisée.
        $middleware->trustProxies(at: '*');

        // Cette protection ajoute les en-têtes de sécurité à toutes les réponses web.
        $middleware->web(append: [AddSecurityHeaders::class]);

        // Les alias gardent les routes lisibles tout en centralisant les contrôles d’accès.
        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
