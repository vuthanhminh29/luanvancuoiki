<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel mặc định render phân trang bằng view Tailwind, nhưng dự án chạy Bootstrap.
        // Không khai báo dòng này thì các lớp responsive của Tailwind không tồn tại,
        // khối "Previous/Next" cho mobile và dãy số trang cho desktop hiện ra CÙNG LÚC,
        // kèm mấy con số bị bọc thành ô vuông rời rạc.
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Paginator::useBootstrapFive();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            URL::forceRootUrl((string) config('app.url'));
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            URL::forceScheme('https');
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        View::composer('layouts.app', function ($view): void {
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            $view->with('headerProductLinks', $this->headerProductLinks());
        });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('web-read', fn (Request $request) => Limit::perMinute(180)->by($request->ip()));

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('auth', function (Request $request) {
            // Luong: Gan ket qua xu ly vao bien $email.
            $email = strtolower((string) $request->input('email', 'guest'));

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('admin-auth', function (Request $request) {
            // Luong: Gan ket qua xu ly vao bien $email.
            $email = strtolower((string) $request->input('email', 'admin'));

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return Limit::perMinute(5)->by('admin|' . $email . '|' . $request->ip());
        });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(30)->by($this->rateLimitKey($request, 'cart')));
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(6)->by($this->rateLimitKey($request, 'checkout')));
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('user-actions', fn (Request $request) => Limit::perMinute(12)->by($this->rateLimitKey($request, 'user')));
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(120)->by($this->rateLimitKey($request, 'admin')));
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by($this->rateLimitKey($request, 'upload')));

        // Callback thanh toán VNPay: khách thật chỉ quay về vài lần cho mỗi đơn,
        // nên giới hạn theo IP đủ để chặn việc dò mã đơn hàng hàng loạt.
        // Để rộng tay một chút vì VNPay có thể gọi lại IPN nhiều lần khi chưa nhận
        // được phản hồi thành công.
        RateLimiter::for('payment-callback', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }

    private function headerProductLinks(): array
    {
        $categories = Cache::remember('layout.header_categories.v2', now()->addMinutes(10), function (): array {
            try {
                return Category::active()
                    ->withCount(['products as active_products_count' => fn ($query) => $query->active()])
                    ->get(['id', 'slug'])
                    ->map(fn (Category $category): array => [
                        'id' => (int) $category->id,
                        'slug' => (string) $category->slug,
                        'active_products_count' => (int) $category->active_products_count,
                    ])
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });

        if ($categories === []) {
            return [];
        }

        $linkFor = function (string $label, array $slugPrefixes) use ($categories): ?array {
            $ids = collect($categories)
                ->filter(function (array $category) use ($slugPrefixes): bool {
                    if ((int) $category['active_products_count'] < 1) {
                        return false;
                    }

                    $slug = (string) $category['slug'];

                    foreach ($slugPrefixes as $prefix) {
                        if ($slug === $prefix || str_starts_with($slug, $prefix . '-')) {
                            return true;
                        }
                    }

                    return false;
                })
                ->pluck('id')
                ->values()
                ->all();

            if ($ids === []) {
                return null;
            }

            return [
                'label' => $label,
                'url' => route('products.index', ['category' => $ids]),
            ];
        };

        return array_values(array_filter([
            $linkFor('Kính mát', ['kinh-mat']),
            $linkFor('Kính thời trang', ['kinh-thoi-trang']),
            //$linkFor('Gọng kính', ['gong-kinh']),
        ]));
    }

    private function rateLimitKey(Request $request, string $scope): string
    {
        return $scope . '|' . ($request->user()?->getAuthIdentifier() ?? $request->ip());
    }
}
