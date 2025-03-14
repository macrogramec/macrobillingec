<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\CheckScopes;
use App\Http\Middleware\ClientRateLimiting;
use App\Http\Middleware\Cors;
use App\Http\Middleware\ApiLogging;
use App\Http\Middleware\ContentSecurityPolicy;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->use([
            \App\Http\Middleware\ContentSecurityPolicy::class
        ]);
        $middleware->alias([
            'scope' => CheckScopes::class,
    	    'client.limit' => ClientRateLimiting::class,
	    'cors' => Cors::class,
	    'api.logging' => ApiLogging::class,
            // Otros middlewares si los tienes
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
        $exceptions->renderable(function (MethodNotAllowedHttpException $e) {
            if (request()->is('api/facturacion/*')) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Error de validación',
                    'error' => 'La clave de acceso debe contener exactamente 49 dígitos numéricos'
                ], 400);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e) {
            if (request()->is('api/facturacion/*')) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Error de validación',
                    'error' => 'La clave de acceso debe contener exactamente 49 dígitos numéricos'
                ], 400);
            }
        });
    })->create();
