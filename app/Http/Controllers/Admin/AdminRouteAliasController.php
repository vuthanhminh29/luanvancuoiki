<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRouteAliasController extends Controller
{
    public function home(Request $request): RedirectResponse|View
    {
        $route = trim((string) $request->query('quanli', ''));

        if ($route !== '') {
            return $this->redirectOldProjectRoute($request, $route);
        }

        return app(DashboardController::class)();
    }

    public function index(Request $request): RedirectResponse|View
    {
        return $this->home($request);
    }

    public function path(Request $request, string $oldRoute): RedirectResponse
    {
        return $this->redirectOldProjectRoute($request, $oldRoute);
    }

    private function redirectOldProjectRoute(Request $request, string $route): RedirectResponse
    {
        $route = trim($route);
        $id = $this->oldProjectId($request);

        $map = [
            'danh-sach-san-pham' => ['admin.products.index'],
            'them-san-pham' => ['admin.products.create'],
            'thung-rac-san-pham' => ['admin.products.recycle'],
            'danh-sach-danh-muc' => ['admin.categories.index'],
            'them-danh-muc' => ['admin.categories.create'],
            'danh-sach-don-hang' => ['admin.orders.index'],
            'danh-sach-don-cho' => ['admin.orders.unconfirmed'],
            'yeu-cau-hoan-doi' => ['admin.returns.index'],
            'danh-sach-bai-viet' => ['admin.posts.index'],
            'them-bai-viet' => ['admin.posts.create'],
            'danh-muc-bai-viet' => ['admin.posts.categories'],
            'danh-sach-khach-hang' => ['admin.customers.index'],
            'them-tai-khoan' => ['admin.customers.create'],
            'binh-luan' => ['admin.reviews.index'],
            'thong-ke-san-pham' => ['admin.reports.products'],
            'thong-ke-don-hang' => ['admin.reports.orders'],
            'bieu-do-luot-ban' => ['admin.reports.sales-chart'],
            'top-luot-ban' => ['admin.reports.top-sales'],
            'luot-ban-theo-ngay' => ['admin.reports.daily-sales'],
            'xuat-exel' => ['admin.products.export-excel'],
            'kho-hang' => ['admin.warehouses.index'],
            'kho-hang2' => ['admin.warehouses.transactions'],
            'them-hoa-don' => ['admin.warehouses.create-transaction'],
            'danh-sach-banner' => ['admin.banners.index'],
            'them-banner' => ['admin.banners.create'],
            'home-layout' => ['admin.home-layout.index'],
            'nghiep-vu' => ['admin.business.index'],
        ];

        $editMap = [
            'cap-nhat-san-pham' => ['admin.products.edit', 'product'],
            'cap-nhat-danh-muc' => ['admin.categories.edit', 'category'],
            'cap-nhat-don-hang' => ['admin.orders.show', 'order'],
            'chi-tiet-hoan-doi' => ['admin.returns.show', 'return'],
            'cap-nhat-bai-viet' => ['admin.posts.edit', 'post'],
            'cap-nhat-danh-muc-bai-viet' => ['admin.posts.categories.edit', 'category'],
            'capnhat-tai-khoan' => ['admin.customers.edit', 'user'],
            'chi-tiet-binh-luan' => ['admin.reviews.show', 'review'],
            'cap-nhat-banner' => ['admin.banners.edit', 'banner'],
        ];

        if (isset($editMap[$route])) {
            [$name, $key] = $editMap[$route];

            if ($key !== null && $id > 0) {
                return redirect()->route($name, [$key => $id]);
            }

            return redirect()->route($this->fallbackRoute($route));
        }

        if (isset($map[$route])) {
            return redirect()->route($map[$route][0]);
        }

        return redirect()->route('admin.dashboard');
    }

    private function oldProjectId(Request $request): int
    {
        foreach (['id', 'id_sp', 'id_dm', 'id_dh', 'id_bv', 'id_bl', 'id_banner', 'id_user', 'id_kh', 'return_id'] as $key) {
            $value = (int) $request->query($key, 0);

            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    private function fallbackRoute(string $route): string
    {
        return match ($route) {
            'cap-nhat-san-pham' => 'admin.products.index',
            'cap-nhat-danh-muc' => 'admin.categories.index',
            'cap-nhat-don-hang' => 'admin.orders.index',
            'chi-tiet-hoan-doi' => 'admin.returns.index',
            'cap-nhat-bai-viet', 'cap-nhat-danh-muc-bai-viet' => 'admin.posts.index',
            'capnhat-tai-khoan' => 'admin.customers.index',
            'chi-tiet-binh-luan' => 'admin.reviews.index',
            'cap-nhat-banner' => 'admin.banners.index',
            default => 'admin.dashboard',
        };
    }
}
