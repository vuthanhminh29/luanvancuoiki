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
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            \App\Http\Middleware\ValidateRequestInput::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens([
            'vnpay/ipn',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Mặc định Laravel trả JSON cho mọi request có Accept: application/json.
        // Dự án siết lại còn 'api/*' để form thường luôn được redirect kèm lỗi
        // thay vì đổ JSON ra màn hình. Nhưng endpoint chatbot nằm trong nhóm
        // web (cần session + CSRF) mà lại được gọi bằng fetch: nếu không mở
        // ngoại lệ ở đây thì lỗi validate hoặc lỗi 429 trả về 302 -> fetch đi
        // theo redirect, nhận HTML, rồi vỡ ở response.json().
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->routeIs('chatbot.*'),
        );

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->routeIs('orders.cancel-confirm.*')) {
                return redirect()
                    ->route('home')
                    ->with('error', 'Liên kết xác nhận hủy đơn không hợp lệ hoặc đã hết hạn. Vui lòng liên hệ cửa hàng để được hỗ trợ.');
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Liên kết xác thực không hợp lệ hoặc đã hết hạn. Vui lòng sử dụng liên kết mới.']);
        });
    })->create();
