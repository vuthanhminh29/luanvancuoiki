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
        Paginator::useBootstrapFive();

        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl((string) config('app.url'));
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view): void {
            $view->with('headerProductLinks', $this->headerProductLinks());
        });

        RateLimiter::for('web-read', fn (Request $request) => Limit::perMinute(180)->by($request->ip()));

        RateLimiter::for('auth', function (Request $request) {
            $email = strtolower((string) $request->input('email', 'guest'));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('admin-auth', function (Request $request) {
            $email = strtolower((string) $request->input('email', 'admin'));

            return Limit::perMinute(5)->by('admin|' . $email . '|' . $request->ip());
        });

        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(30)->by($this->rateLimitKey($request, 'cart')));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(6)->by($this->rateLimitKey($request, 'checkout')));
        RateLimiter::for('user-actions', fn (Request $request) => Limit::perMinute(12)->by($this->rateLimitKey($request, 'user')));
        RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(120)->by($this->rateLimitKey($request, 'admin')));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by($this->rateLimitKey($request, 'upload')));
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
            $linkFor('Gọng kính', ['gong-kinh']),
        ]));
    }

    private function rateLimitKey(Request $request, string $scope): string
    {
        return $scope . '|' . ($request->user()?->getAuthIdentifier() ?? $request->ip());
    }
}
