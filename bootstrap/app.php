<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\SetWebLocale::class]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $message = match ($status) {
                401 => 'Autentikasi diperlukan.',
                403 => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
                404 => 'Resource tidak ditemukan.',
                422 => 'Data yang dikirim tidak valid.',
                429 => 'Terlalu banyak permintaan. Coba lagi nanti.',
                default => $status >= 500 ? 'Terjadi kesalahan pada server.' : 'Permintaan tidak dapat diproses.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $exception instanceof ValidationException ? $exception->errors() : null,
            ], $status, $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : []);
        });
    })->create();
