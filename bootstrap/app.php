<?php

use App\Http\Middleware\CheckAcl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['acl' => CheckAcl::class]);

        // The Yii app saved form input untouched: '' stayed '' (MySQL, in
        // non-strict mode, stores it as 0 in numeric columns) and nothing was
        // trimmed. Keep that so saved data matches what Yii would have saved.
        $middleware->remove([ConvertEmptyStringsToNull::class, TrimStrings::class]);

        $middleware->redirectGuestsTo('/site/login');
        $middleware->redirectUsersTo('/dashboard/index');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
