<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->append(\App\Http\Middleware\NoIndexHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 401);
        });

        $exceptions->render(function (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 401);
        });

        $exceptions->render(function (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token ausente ou inválido',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 401);
        });

        $exceptions->render(function (UnauthorizedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não autenticado',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 401);
        });
    })->create();
