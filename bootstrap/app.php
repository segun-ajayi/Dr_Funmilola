<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\MobileApiEnvelope;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();
        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'role' => EnsureRole::class,
            'mobile.envelope' => MobileApiEnvelope::class,
            'abilities' => CheckAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/v1/*')) return response()->json(['error'=>['code'=>'validation_failed','message'=>'Please check the highlighted fields.','fields'=>$exception->errors()]],422);
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/v1/*')) return response()->json(['error'=>['code'=>'unauthenticated','message'=>'Please sign in to continue.']],401);
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (!$request->is('api/v1/*')) return null;
            $status=$exception->getStatusCode();$code=match($status){403=>'forbidden',404=>'not_found',409=>'conflict',410=>'gone',429=>'rate_limited',default=>'request_failed'};
            return response()->json(['error'=>['code'=>$code,'message'=>$exception->getMessage()?:'The request could not be completed.']],$status);
        });
    })->create();
