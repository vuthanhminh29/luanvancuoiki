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

        // CSP: giao diện hiện có ~31 khối <script> inline, 244 thuộc tính style=
        // và 47 handler onclick/onsubmit. Vì vậy script-src và style-src BẮT BUỘC
        // phải khai báo tường minh kèm 'unsafe-inline'.
        //
        // Cẩn thận: nếu chỉ đặt default-src mà bỏ trống hai directive này thì
        // default-src trở thành fallback cho chúng, và toàn bộ script/style inline
        // bị chặn -> trang trắng, nút bấm chết. Đó chính là lỗi của bản CSP trước.
        //
        // Các directive còn lại không phụ thuộc inline nhưng vẫn chặn được nhiều
        // đường khai thác thật:
        //  - object-src 'none': chặn nhúng plugin/Flash.
        //  - base-uri 'self': chặn cướp <base> để đổi đích mọi URL tương đối.
        //  - frame-ancestors 'self': chống clickjacking, mạnh hơn X-Frame-Options.
        //  - form-action 'self': chặn form bị chèn gửi dữ liệu ra ngoài.
        //
        // Muốn siết script-src thật (bỏ 'unsafe-inline', dùng nonce) thì phải bóc
        // hết script inline và handler onclick ra file .js trước — xem ghi chú
        // trong docs. Chốt chặn XSS hiện tại là App\Support\HtmlSanitizer.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self' data: https:",
            "script-src 'self' 'unsafe-inline' data: https:",
            "style-src 'self' 'unsafe-inline' data: https:",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]));

        // HSTS chỉ có ý nghĩa khi request đã đi qua HTTPS; đặt trên HTTP có thể
        // khóa nhầm môi trường local đang chạy http.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->isSecure()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->is('admin/*') || $request->is('dang-nhap') || $request->is('dang-ky')) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $response;
    }
}
