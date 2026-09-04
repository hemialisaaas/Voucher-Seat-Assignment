<?php

use App\Exceptions\VoucherAlreadyExistsException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom domain exception -> its own render() method handles the response,
        // but we register it here too so it's explicit and easy to find.
        $exceptions->render(function (VoucherAlreadyExistsException $e) {
            return $e->render();
        });

        // Ensure validation errors from Form Requests return a consistent,
        // predictable JSON shape for the React frontend to consume.
        $exceptions->render(function (ValidationException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        });
    })->create();
