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
    public function __invoke(): View
    {
        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        $startOfToday = now()->startOfDay()->toDateTimeString();

        $orderStats = Order::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END), 0) as total_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DELIVERED' AND created_at >= '{$startOfMonth}' THEN total_amount ELSE 0 END), 0) as month_revenue")
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN created_at >= '{$startOfToday}' THEN 1 ELSE 0 END) as today_orders")
            ->selectRaw("SUM(CASE WHEN status IN ('PENDING', 'AWAITING_PAYMENT') THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN status = 'CONFIRMED' THEN 1 ELSE 0 END) as confirmed_orders")
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERING' THEN 1 ELSE 0 END) as delivering_orders")
            ->first();

        $returnStats = ReturnRequest::query()
            ->selectRaw("SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_returns")
            ->selectRaw("SUM(CASE WHEN type = 'RETURN' AND status = 'PENDING' THEN 1 ELSE 0 END) as return_only")
            ->selectRaw("SUM(CASE WHEN type = 'EXCHANGE' AND status = 'PENDING' THEN 1 ELSE 0 END) as exchange_only")
            ->first();

        $stock = Inventory::query()
            ->whereHas('warehouse', fn ($query) => $query->where('status', 'ACTIVE')->where('type', '!=', \App\Services\InventoryService::QUARANTINE_TYPE))
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_stock')
            ->first();

        $lowStockItems = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('colors', 'colors.id', '=', 'product_variants.color_id')
            ->leftJoin('lens_sizes', 'lens_sizes.id', '=', 'product_variants.lens_size_id')
            ->leftJoin('inventories', 'inventories.variant_id', '=', 'product_variants.id')
            ->whereIn('products.status', ['ACTIVE', 'INACTIVE', 'DRAFT'])
            ->select([
                'products.name as product_name',
                'product_variants.sku',
                'colors.name as color_name',
                'lens_sizes.name as lens_size',
            ])
            ->selectRaw('COALESCE(SUM(inventories.quantity), 0) as available_stock')
            ->selectRaw('COALESCE(MAX(inventories.min_stock_level), 10) as min_stock_level')
            ->groupBy('product_variants.id', 'products.name', 'product_variants.sku', 'colors.name', 'lens_sizes.name')
            ->havingRaw('available_stock <= min_stock_level')
            ->orderBy('available_stock')
            ->limit(5)
            ->get();

        $topCategories = Category::query()
            ->leftJoin('products', function ($join) {
                $join->on('products.category_id', '=', 'categories.id')
                    ->where('products.status', '=', 'ACTIVE');
            })
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->select('categories.name')
            ->selectRaw('COUNT(DISTINCT products.id) as product_count')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as sold_quantity')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('sold_quantity')
            ->orderByDesc('product_count')
            ->limit(5)
            ->get();

        $chartRows = Order::query()
            ->selectRaw('DATE(created_at) as order_date')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('order_date')
            ->get();

        return view('admin.dashboard', [
            'orderStats' => $orderStats,
            'returnStats' => $returnStats,
            'availableStock' => max(0, (float) $stock->total_stock),
            'activeProducts' => Product::active()->count(),
            'totalVariants' => ProductVariant::count(),
            'activeCategories' => Category::active()->count(),
            'activeBrands' => DB::table('brands')->where('status', 'ACTIVE')->count(),
            'activeCustomers' => User::where('status', 'ACTIVE')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('user_roles')
                        ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                        ->whereColumn('user_roles.user_id', 'users.id')
                        ->where('roles.code', 'USER');
                })
                ->count(),
            'lowStockCount' => $lowStockItems->count(),
            'latestOrders' => Order::with('user')->latest()->limit(6)->get(),
            'lowStockItems' => $lowStockItems,
            'topCategories' => $topCategories,
            'pendingReturns' => ReturnRequest::with(['order', 'user'])->where('status', 'PENDING')->latest('requested_at')->limit(5)->get(),
            'chartLabels' => $chartRows->map(fn ($row) => \Carbon\Carbon::parse($row->order_date)->format('d/m'))->all(),
            'chartOrders' => $chartRows->pluck('order_count')->map(fn ($value) => (int) $value)->all(),
            'chartRevenue' => $chartRows->pluck('revenue')->map(fn ($value) => (float) $value)->all(),
        ]);
    }
}
