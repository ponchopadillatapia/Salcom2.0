<?php

use App\Http\Middleware\ApiTokenAuth;
use App\Http\Middleware\AutenticacionAdmin;
use App\Http\Middleware\AutenticacionCliente;
use App\Http\Middleware\AutenticacionProveedor;
use App\Http\Middleware\SecurityHeaders;
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
    ->withMiddleware(function (Middleware $middleware): void {
        // Security headers en todas las respuestas
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
<<<<<<< HEAD
            'auth.proveedor' => \App\Http\Middleware\AutenticacionProveedor::class,
            'auth.cliente'   => \App\Http\Middleware\AutenticacionCliente::class,
            'auth.admin'     => \App\Http\Middleware\AutenticacionAdmin::class,
            'auth.api_token' => \App\Http\Middleware\ApiTokenAuth::class,
            'admin.rol'      => \App\Http\Middleware\AdminRol::class,
=======
            'auth.proveedor' => AutenticacionProveedor::class,
            'auth.cliente' => AutenticacionCliente::class,
            'auth.admin' => AutenticacionAdmin::class,
            'auth.api_token' => ApiTokenAuth::class,
>>>>>>> 01bde6eadcfcbeb478f212748b85c458b0e4205f
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
