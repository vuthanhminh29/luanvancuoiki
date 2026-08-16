<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientRouteAliasController extends Controller
{
    /**
     * Xử lý route trang chủ cũ.
     */
    public function home(Request $request): RedirectResponse|View
    {
        // Luong: Gan ket qua xu ly vao bien $route.
        $route = trim((string) $request->query('url', ''));

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($route !== '') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $this->redirectOldProjectRoute($request, $route);
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return app(HomeController::class)();
    }

    /**
     * Xử lý route cũ phía khách.
     */
    public function index(Request $request): RedirectResponse|View
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->home($request);
    }

    /**
     * Chuyển đường dẫn cũ sang route mới.
     */
    public function path(Request $request, string $oldRoute): RedirectResponse
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->redirectOldProjectRoute($request, $oldRoute);
    }

    /**
     * Chuyển route dự án cũ sang route mới.
     */
    private function redirectOldProjectRoute(Request $request, string $route): RedirectResponse
    {
        $route = trim($route);

        if ($route === '' || $route === 'trang-chu') {
            return $this->redirectPath($request, '/');
        }

        if ($route === 'chitietsanpham') {
            $product = Product::find((int) $request->query('id_sp', $request->query('id', 0)));

            return $product
                ? $this->redirectPath($request, '/san-pham/' . $product->slug)
                : $this->redirectPath($request, '/san-pham');
        }

        if ($route === 'chi-tiet-bai-viet') {
            $post = Post::find((int) $request->query('id_bv', $request->query('id', 0)));

            return $post
                ? $this->redirectPath($request, '/bai-viet/' . $post->slug)
                : $this->redirectPath($request, '/bai-viet');
        }

        if ($route === 'edit-address') {
            $addressId = (int) $request->query('id', 0);

            return $addressId > 0
                ? $this->redirectPath($request, '/tai-khoan/dia-chi/' . $addressId . '/sua')
                : $this->redirectPath($request, '/tai-khoan');
        }

        if ($route === 'remove-address') {
            return $this->redirectPath($request, '/tai-khoan');
        }

        $map = [
            'cua-hang' => '/san-pham',
            'danh-muc-san-pham' => '/san-pham',
            'thu-kinh' => '/thu-kinh',
            'lien-he' => '/lien-he',
            'gio-hang' => '/gio-hang',
            'thanh-toan' => '/thanh-toan',
            'thanh-toan-2' => '/thanh-toan',
            'thanh-toan-dia-chi2' => '/thanh-toan',
            'cam-on' => '/thanh-toan',
            'don-hang' => '/tai-khoan/don-hang',
            'chi-tiet-don-hang' => '/tai-khoan/don-hang',
            'yeu-cau-hoan-doi' => '/hoan-doi',
            'phan-hoi-hoan-doi' => '/hoan-doi',
            'dang-nhap' => '/dang-nhap',
            'dang-ky' => '/dang-ky',
            'thong-tin-tai-khoan' => '/tai-khoan',
            'ho-so' => '/tai-khoan',
            'them-dia-chi' => '/tai-khoan/dia-chi/them',
            'doi-mat-khau' => '/tai-khoan/doi-mat-khau',
            'quen-mat-khau' => '/dang-nhap',
            'khoi-phuc-mat-khau' => '/dang-nhap',
            'bai-viet' => '/bai-viet',
            'danh-muc-bai-viet' => '/bai-viet',
            'tim-kiem' => '/san-pham',
            'ho-tro-khach-hang' => '/ho-tro',
            'chinh-sach-van-chuyen' => '/ho-tro',
            'chinh-sach-thanh-toan' => '/ho-tro',
            'chinh-sach-doi-tra' => '/ho-tro',
            'thanh-toan-momo' => '/thanh-toan',
            'thanh-toan-momo-address' => '/thanh-toan',
            'thanh-toan-momo-address-2' => '/thanh-toan',
        ];

        if (isset($map[$route])) {
            return $this->redirectPath($request, $map[$route]);
        }

        return $this->redirectPath($request, '/');
    }

    /**
     * Chuyển path cũ sang path mới.
     */
    private function redirectPath(Request $request, string $path): RedirectResponse
    {
        return redirect()->away($request->getSchemeAndHttpHost() . $path);
    }
}
