<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'role.filters' => \App\Http\Middleware\ApplyRoleFilters::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, $request) {
            // Check for 419 Token Mismatch
            $is419 = $e instanceof \Illuminate\Session\TokenMismatchException || 
                     (method_exists($e, 'getStatusCode') && $e->getStatusCode() === 419);

            if ($is419) {
                \Illuminate\Support\Facades\Log::warning('419 Error Detected', [
                    'url' => $request->fullUrl(),
                    'session_id' => session()->getId(),
                    'ip' => $request->ip()
                ]);

                return redirect()->route('login')
                    ->with('error', 'Your session expired due to inactivity. Please login again.');
            }
        });
    })->create();
