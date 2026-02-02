<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        
        // Register custom middleware alias
        $middleware->alias([
            'set.permission.team' => \App\Http\Middleware\SetPermissionTeam::class,
        ]);
        
        // Append SetPermissionTeam middleware after auth:sanctum runs
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\SetPermissionTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // For AJAX/JSON requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเข้าสู่ระบบก่อน'
                ], 401);
            }

            // For direct browser requests (like PDF export), redirect to frontend login
            $frontendUrl = config('app.frontend_url');
            return redirect($frontendUrl . '/login');
        });
    })->create();

