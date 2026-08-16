<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Hiển thị dashboard quản trị.
     */
    public function __invoke(): View
    {
        // Luong: Gan ket qua xu ly vao bien $startOfMonth.
        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        // Luong: Gan ket qua xu ly vao bien $startOfToday.
        $startOfToday = now()->startOfDay()->toDateTimeString();

        // Luong: Gan ket qua xu ly vao bien $orderStats.
        $orderStats = Order::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END), 0) as total_revenue")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DELIVERED' AND created_at >= '{$startOfMonth}' THEN total_amount ELSE 0 END), 0) as month_revenue")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COUNT(*) as total_orders')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN created_at >= '{$startOfToday}' THEN 1 ELSE 0 END) as today_orders")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN status IN ('PENDING', 'AWAITING_PAYMENT') THEN 1 ELSE 0 END) as pending_orders")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN status = 'CONFIRMED' THEN 1 ELSE 0 END) as confirmed_orders")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERING' THEN 1 ELSE 0 END) as delivering_orders")
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->first();

        // Luong: Gan ket qua xu ly vao bien $returnStats.
        $returnStats = ReturnRequest::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_returns")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN type = 'RETURN' AND status = 'PENDING' THEN 1 ELSE 0 END) as return_only")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("SUM(CASE WHEN type = 'EXCHANGE' AND status = 'PENDING' THEN 1 ELSE 0 END) as exchange_only")
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->first();

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $totalStock = (float) DB::table('inventories')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('warehouse_id', 1)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->sum('quantity');

        // Luong: Gan ket qua xu ly vao bien $lowStockItems.
        $lowStockItems = ProductVariant::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('colors', 'colors.id', '=', 'product_variants.color_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('lens_sizes', 'lens_sizes.id', '=', 'product_variants.lens_size_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('inventories', 'inventories.variant_id', '=', 'product_variants.id')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereIn('products.status', ['ACTIVE', 'INACTIVE', 'DRAFT'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select([
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'products.name as product_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'product_variants.sku',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'colors.name as color_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'lens_sizes.name as lens_size',
            ])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(SUM(inventories.quantity), 0) as available_stock')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(MAX(inventories.min_stock_level), 10) as min_stock_level')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->groupBy('product_variants.id', 'products.name', 'product_variants.sku', 'colors.name', 'lens_sizes.name')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->havingRaw('available_stock <= min_stock_level')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('available_stock')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(5)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $topCategories.
        $topCategories = Category::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('products', function ($join) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $join->on('products.category_id', '=', 'categories.id')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('products.status', '=', 'ACTIVE');
            })
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select('categories.name')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COUNT(DISTINCT products.id) as product_count')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as sold_quantity')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->groupBy('categories.id', 'categories.name')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByDesc('sold_quantity')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByDesc('product_count')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(5)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $chartRows.
        $chartRows = Order::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('DATE(created_at) as order_date')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COUNT(*) as order_count')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->groupBy(DB::raw('DATE(created_at)'))
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('order_date')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.dashboard', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'orderStats' => $orderStats,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'returnStats' => $returnStats,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'availableStock' => max(0, $totalStock),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'activeProducts' => Product::active()->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'totalVariants' => ProductVariant::count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'activeCategories' => Category::active()->count(),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'activeBrands' => DB::table('brands')->where('status', 'ACTIVE')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'activeCustomers' => User::where('status', 'ACTIVE')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->whereExists(function ($query) {
                    // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                    $query->selectRaw('1')
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->from('user_roles')
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->whereColumn('user_roles.user_id', 'users.id')
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('roles.code', 'USER');
                })
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'lowStockCount' => $lowStockItems->count(),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'latestOrders' => Order::with('user')->latest()->limit(6)->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'lowStockItems' => $lowStockItems,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'topCategories' => $topCategories,
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            'pendingReturns' => ReturnRequest::with(['order', 'user'])->where('status', 'PENDING')->latest('requested_at')->limit(5)->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'chartLabels' => $chartRows->map(fn ($row) => \Carbon\Carbon::parse($row->order_date)->format('d/m'))->all(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'chartOrders' => $chartRows->pluck('order_count')->map(fn ($value) => (int) $value)->all(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'chartRevenue' => $chartRows->pluck('revenue')->map(fn ($value) => (float) $value)->all(),
        ]);
    }
}
