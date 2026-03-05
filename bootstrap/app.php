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
        // Register named middleware for use in routes
        $middleware->alias([
            'validate.twilio' => \App\Http\Middleware\ValidateTwilioSignature::class,
            'resolve.tenant'  => \App\Http\Middleware\ResolveTenant::class,
        ]);

        // Apply tenant resolution to all authenticated API routes
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\ResolveTenant::class,
        ]);

        // Exclude webhook routes from CSRF and from general API middleware
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
