<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\BackOfficeAccess::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

if (getenv('BOARD_STATE_DIR')) {
    $app->useEnvironmentPath(getenv('BOARD_STATE_DIR'));
}

return $app;
