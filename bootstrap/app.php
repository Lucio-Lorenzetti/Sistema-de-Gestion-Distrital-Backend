<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // El contenedor corre detrás del balanceador del hosting (que termina el HTTPS
        // y reenvía por HTTP). Sin confiar en esos proxies, Laravel cree que el pedido
        // es http y arma/valida mal las URLs firmadas (verificación de correo) y las
        // IP de los clientes. No hay forma de llegar al contenedor sin pasar por el proxy.
        $middleware->trustProxies(at: '*');

        // Sin statefulApi() — usamos Bearer tokens, no cookies
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();