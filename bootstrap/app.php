<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\ValidateRequestInput::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'vnpay/ipn',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->routeIs('orders.cancel-confirm.*')) {
                return redirect()
                    ->route('home')
                    ->with('error', 'LiÃªn káº¿t xÃ¡c nháº­n há»§y Ä‘Æ¡n khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ háº¿t háº¡n. Vui lÃ²ng liÃªn há»‡ cá»­a hÃ ng Ä‘á»ƒ Ä‘Æ°á»£c há»— trá»£.');
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'LiÃªn káº¿t xÃ¡c thá»±c khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ háº¿t háº¡n. Vui lÃ²ng sá»­ dá»¥ng liÃªn káº¿t má»›i.']);
        });
    })->create();
