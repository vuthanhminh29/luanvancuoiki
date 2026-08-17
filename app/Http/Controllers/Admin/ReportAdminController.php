<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportAdminController extends Controller
{
    private array $excludedOrderStatuses = ['CANCELLED'];

    /**
     * Hiển thị báo cáo sản phẩm.
     */
    public function products(Request $request): View
    {
        $dateRange = $this->resolveDateRange($request);
        $orderDateCondition = $this->orderDateCondition($dateRange);

        $summary = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM categories WHERE status = 'ACTIVE') AS active_categories,
                (SELECT COUNT(*) FROM products WHERE status = 'ACTIVE') AS active_products,
                (SELECT COUNT(*) FROM product_variants) AS total_variants,
                (SELECT COALESCE(SUM(quantity), 0) FROM inventories) AS available_stock
        ");

        $categoryReports = collect(DB::select("
            SELECT
                c.id,
                c.name AS category_name,
                COUNT(DISTINCT p.id) AS product_count,
                COUNT(DISTINCT pv.id) AS variant_count,
                COALESCE(SUM(inv.available_stock), 0) AS available_stock,
                COALESCE(SUM(CASE WHEN COALESCE(inv.available_stock, 0) <= COALESCE(inv.min_stock_level, 10) THEN 1 ELSE 0 END), 0) AS low_stock_count,
                COALESCE(MIN(COALESCE(pv.variant_price, p.base_price)), 0) AS min_price,
                COALESCE(MAX(COALESCE(pv.variant_price, p.base_price)), 0) AS max_price,
                COALESCE(AVG(COALESCE(pv.variant_price, p.base_price)), 0) AS avg_price,
                COALESCE(sold.sold_quantity, 0) AS sold_quantity,
                COALESCE(sold.revenue, 0) AS revenue
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id AND p.status <> 'DISCONTINUED'
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN (
                SELECT
                    variant_id,
                    COALESCE(SUM(quantity), 0) AS available_stock,
                    COALESCE(MAX(min_stock_level), 10) AS min_stock_level
                FROM inventories
                GROUP BY variant_id
            ) inv ON inv.variant_id = pv.id
            LEFT JOIN (
                SELECT
                    p.category_id,
                    COALESCE(SUM(oi.quantity), 0) AS sold_quantity,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                JOIN orders o ON o.id = oi.order_id
                WHERE o.status NOT IN ('CANCELLED')
                  {$orderDateCondition}
                GROUP BY p.category_id
            ) sold ON sold.category_id = c.id
            WHERE c.status = 'ACTIVE'
            GROUP BY c.id, c.name, sold.sold_quantity, sold.revenue
            ORDER BY revenue DESC, sold_quantity DESC, product_count DESC
        "));

        return view('admin.reports.products', [
            'summary' => $summary,
            'categoryReports' => $categoryReports,
            'maxRevenue' => $this->maxValue($categoryReports, 'revenue'),
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Hiển thị báo cáo đơn hàng.
     */
    public function orders(Request $request): View
    {
        $dateRange = $this->resolveDateRange($request);
        $orderDateCondition = $this->orderDateCondition($dateRange);
        $plainOrderDateCondition = $this->orderDateCondition($dateRange, 'orders');

        $summary = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM orders WHERE 1 = 1 {$plainOrderDateCondition}) AS total_orders,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'DELIVERED' {$plainOrderDateCondition}) AS delivered_revenue,
                (
                    SELECT COALESCE(SUM(oi.quantity), 0)
                    FROM order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    WHERE o.status NOT IN ('CANCELLED')
                      {$orderDateCondition}
                ) AS sold_quantity,
                (SELECT COUNT(*) FROM orders WHERE status IN ('PENDING', 'AWAITING_PAYMENT') {$plainOrderDateCondition}) AS pending_orders
        ");

        $statusReports = collect(DB::select("
            SELECT
                status,
                COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS total_amount
            FROM orders
            WHERE 1 = 1 {$plainOrderDateCondition}
            GROUP BY status
            ORDER BY order_count DESC
        "));

        $productReports = collect(DB::select("
            SELECT
                p.id,
                p.name AS product_name,
                COALESCE(c.name, 'Chưa có danh mục') AS category_name,
                COALESCE(b.name, 'Chưa có thương hiệu') AS brand_name,
                COALESCE(v.total_variants, 0) AS total_variants,
                COALESCE(stock.available_stock, 0) AS available_stock,
                COALESCE(stock.low_variant_count, 0) AS low_variant_count,
                COALESCE(sold.order_count, 0) AS order_count,
                COALESCE(sold.sold_quantity, 0) AS sold_quantity,
                COALESCE(sold.revenue, 0) AS revenue,
                COALESCE(ret.return_quantity, 0) AS return_quantity
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN (
                SELECT product_id, COUNT(*) AS total_variants
                FROM product_variants
                GROUP BY product_id
            ) v ON v.product_id = p.id
            LEFT JOIN (
                SELECT
                    product_id,
                    SUM(available_stock) AS available_stock,
                    SUM(CASE WHEN available_stock <= min_stock_level THEN 1 ELSE 0 END) AS low_variant_count
                FROM (
                    SELECT
                        pv.product_id,
                        pv.id AS variant_id,
                        COALESCE(SUM(i.quantity), 0) AS available_stock,
                        COALESCE(MAX(i.min_stock_level), 10) AS min_stock_level
                    FROM product_variants pv
                    LEFT JOIN inventories i ON i.variant_id = pv.id
                    GROUP BY pv.product_id, pv.id
                ) variant_stock
                GROUP BY product_id
            ) stock ON stock.product_id = p.id
            LEFT JOIN (
                SELECT
                    oi.product_id,
                    COUNT(DISTINCT oi.order_id) AS order_count,
                    COALESCE(SUM(oi.quantity), 0) AS sold_quantity,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.status NOT IN ('CANCELLED')
                  {$orderDateCondition}
                GROUP BY oi.product_id
            ) sold ON sold.product_id = p.id
            LEFT JOIN (
                SELECT
                    oi.product_id,
                    COALESCE(SUM(rri.quantity), 0) AS return_quantity
                FROM return_request_items rri
                JOIN order_items oi ON oi.id = rri.order_item_id
                JOIN return_requests rr ON rr.id = rri.return_request_id
                WHERE rr.status IN ('PENDING', 'APPROVED', 'RECEIVED', 'COMPLETED')
                  {$this->orderDateCondition($dateRange, 'rr')}
                GROUP BY oi.product_id
            ) ret ON ret.product_id = p.id
            WHERE p.status <> 'DISCONTINUED'
            ORDER BY revenue DESC, sold_quantity DESC, p.id DESC
        "));

        return view('admin.reports.orders', [
            'summary' => $summary,
            'statusReports' => $statusReports,
            'productReports' => $productReports,
            'maxStatus' => $this->maxValue($statusReports, 'order_count'),
            'tradedProducts' => $productReports->filter(fn ($row) => (int) $row->sold_quantity > 0)->count(),
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Hiển thị biểu đồ doanh thu.
     */
    public function salesChart(Request $request): View
    {
        $top = $this->resolveTop($request->integer('top', 10), [5, 10, 30]);
        $dateRange = $this->resolveDateRange($request);
        $orderDateCondition = $this->orderDateCondition($dateRange);

        $categorySales = collect(DB::select("
            SELECT
                COALESCE(c.name, 'Chưa có danh mục') AS category_name,
                COUNT(DISTINCT p.id) AS product_count,
                COALESCE(SUM(oi.quantity), 0) AS sold_quantity,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE o.status NOT IN ('CANCELLED')
              {$orderDateCondition}
            GROUP BY c.id, c.name
            ORDER BY revenue DESC, sold_quantity DESC
            LIMIT {$top}
        "));

        $totalSold = $categorySales->sum(fn ($row) => (int) $row->sold_quantity);
        $totalRevenue = $categorySales->sum(fn ($row) => (float) $row->revenue);

        return view('admin.reports.chart', [
            'top' => $top,
            'categorySales' => $categorySales,
            'labels' => $categorySales->pluck('category_name')->all(),
            'sold' => $categorySales->pluck('sold_quantity')->map(fn ($value) => (int) $value)->all(),
            'revenue' => $categorySales->pluck('revenue')->map(fn ($value) => (float) $value)->all(),
            'totalSold' => $totalSold,
            'totalRevenue' => $totalRevenue,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Hiển thị sản phẩm bán chạy.
     */
    public function topSales(Request $request): View
    {
        $top = $this->resolveTop($request->integer('top', 10), [5, 10, 15, 30, 100]);
        $dateRange = $this->resolveDateRange($request);
        $orderDateCondition = $this->orderDateCondition($dateRange);

        $topProducts = collect(DB::select("
            SELECT
                p.id,
                p.name AS product_name,
                COALESCE(c.name, 'Chưa có danh mục') AS category_name,
                COALESCE(b.name, 'Chưa có thương hiệu') AS brand_name,
                COALESCE(SUM(oi.quantity), 0) AS sold_quantity,
                COUNT(DISTINCT oi.order_id) AS order_count,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue,
                COALESCE(stock.available_stock, 0) AS available_stock
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN (
                SELECT
                    pv.product_id,
                    COALESCE(SUM(i.quantity), 0) AS available_stock
                FROM product_variants pv
                LEFT JOIN inventories i ON i.variant_id = pv.id
                GROUP BY pv.product_id
            ) stock ON stock.product_id = p.id
            WHERE o.status NOT IN ('CANCELLED')
              {$orderDateCondition}
            GROUP BY p.id, p.name, c.name, b.name, stock.available_stock
            ORDER BY sold_quantity DESC, revenue DESC
            LIMIT {$top}
        "));

        $totalSold = $topProducts->sum(fn ($row) => (int) $row->sold_quantity);
        $totalRevenue = $topProducts->sum(fn ($row) => (float) $row->revenue);

        return view('admin.reports.top-sales', [
            'top' => $top,
            'topProducts' => $topProducts,
            'labels' => $topProducts->pluck('product_name')->all(),
            'sold' => $topProducts->pluck('sold_quantity')->map(fn ($value) => (int) $value)->all(),
            'revenue' => $topProducts->pluck('revenue')->map(fn ($value) => (float) $value)->all(),
            'totalSold' => $totalSold,
            'totalRevenue' => $totalRevenue,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Hiển thị doanh thu theo ngày.
     */
    public function dailySales(Request $request): View
    {
        $limitDay = $this->resolveTop($request->integer('limit_day', 14), [7, 14, 30, 90, 365]);
        $chartType = in_array($request->query('type_chart'), ['bar', 'line'], true) ? $request->query('type_chart') : 'bar';
        $dateRange = $this->resolveDateRange($request, $limitDay);
        $orderDateCondition = $this->orderDateCondition($dateRange);

        $dailySales = collect(DB::select("
            SELECT
                DATE(o.created_at) AS order_date,
                COUNT(DISTINCT o.id) AS order_count,
                COALESCE(SUM(oi.quantity), 0) AS sold_quantity,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE o.status NOT IN ('CANCELLED')
              {$orderDateCondition}
            GROUP BY DATE(o.created_at)
            ORDER BY order_date ASC
        "));

        $orders = $dailySales->pluck('order_count')->map(fn ($value) => (int) $value)->all();
        $sold = $dailySales->pluck('sold_quantity')->map(fn ($value) => (int) $value)->all();
        $revenue = $dailySales->pluck('revenue')->map(fn ($value) => (float) $value)->all();
        $totalRevenue = array_sum($revenue);

        return view('admin.reports.daily-sales', [
            'limitDay' => $limitDay,
            'chartType' => $chartType,
            'dailySales' => $dailySales,
            'labels' => $dailySales->map(fn ($row) => date('d/m', strtotime($row->order_date)))->all(),
            'orders' => $orders,
            'sold' => $sold,
            'revenue' => $revenue,
            'totalOrders' => array_sum($orders),
            'totalSold' => array_sum($sold),
            'totalRevenue' => $totalRevenue,
            'avgRevenue' => $dailySales->count() > 0 ? $totalRevenue / $dailySales->count() : 0,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Chuẩn hóa số lượng dòng top.
     */
    private function resolveTop(int $value, array $allowed): int
    {
        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    /**
     * Lấy giá trị lớn nhất trong dữ liệu báo cáo.
     */
    private function maxValue(Collection $rows, string $field): float
    {
        return max(1, (float) $rows->max(fn ($row) => (float) $row->{$field}));
    }

    private function resolveDateRange(Request $request, int $defaultDays = 30): array
    {
        $today = CarbonImmutable::today();
        $fallbackFrom = $today->subDays(max(1, $defaultDays) - 1);

        $from = $this->parseDate($request->query('date_from')) ?? $fallbackFrom;
        $to = $this->parseDate($request->query('date_to')) ?? $today;

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'from_datetime' => $from->startOfDay()->toDateTimeString(),
            'to_datetime' => $to->endOfDay()->toDateTimeString(),
            'label' => $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'),
        ];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function orderDateCondition(array $dateRange, string $alias = 'o'): string
    {
        return sprintf(
            "AND %s.created_at BETWEEN '%s' AND '%s'",
            $alias,
            $dateRange['from_datetime'],
            $dateRange['to_datetime']
        );
    }
}
