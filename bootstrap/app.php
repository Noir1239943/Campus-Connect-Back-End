<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pure bearer-token auth (see AuthController + Sanctum's PersonalAccessToken
        // guard) — intentionally NOT calling ->statefulApi() here. That method makes
        // Sanctum treat requests from any domain in config('sanctum.stateful') as
        // cookie-based SPA sessions, which then require a CSRF token via the 'web'
        // middleware group. Since the frontend never calls /sanctum/csrf-cookie and
        // only sends "Authorization: Bearer <token>", enabling it causes exactly the
        // "CSRF token mismatch" error you'll see on login otherwise.
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
