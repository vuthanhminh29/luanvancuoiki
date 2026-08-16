<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Luong: Gan ket qua xu ly vao bien $response.
        $response = $next($request);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->is('admin/*') || $request->is('dang-nhap') || $request->is('dang-ky')) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $response;
    }
}
