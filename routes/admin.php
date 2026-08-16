<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminRouteAliasController;
use App\Http\Controllers\Admin\AppointmentAdminController;
use App\Http\Controllers\Admin\BannerAdminController;
use App\Http\Controllers\Admin\BusinessAdminController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\CustomerAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeLayoutAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PostAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\PromotionAdminController;
use App\Http\Controllers\Admin\ReportAdminController;
use App\Http\Controllers\Admin\ReturnAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\TryOnSnapshotAdminController;
use App\Http\Controllers\Admin\WarehouseAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dang-nhap', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AdminAuthController::class, 'login'])->middleware('throttle:admin-auth')->name('login.store');
    Route::post('/dang-xuat', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['admin', 'throttle:admin'])->group(function () {
        Route::get('/', [AdminRouteAliasController::class, 'home'])->name('dashboard');
        Route::get('/index.php', [AdminRouteAliasController::class, 'index'])->name('php.index');

        Route::get('/san-pham', [ProductAdminController::class, 'index'])->name('products.index');
        Route::get('/san-pham/xuat-excel', [ProductAdminController::class, 'exportExcel'])->name('products.export-excel');
        Route::post('/san-pham/upload-editor', [ProductAdminController::class, 'uploadEditorImage'])->middleware('throttle:uploads')->name('products.upload-editor');
        Route::get('/san-pham/them', [ProductAdminController::class, 'create'])->name('products.create');
        Route::post('/san-pham', [ProductAdminController::class, 'store'])->name('products.store');
        Route::get('/san-pham/thung-rac', [ProductAdminController::class, 'recycle'])->name('products.recycle');
        Route::get('/san-pham/{product}/sua', [ProductAdminController::class, 'edit'])->name('products.edit');
        Route::put('/san-pham/{product}', [ProductAdminController::class, 'update'])->name('products.update');
        Route::patch('/san-pham/{product}/an', [ProductAdminController::class, 'hidden'])->name('products.hidden');
        Route::patch('/san-pham/{product}/khoi-phuc', [ProductAdminController::class, 'restore'])->name('products.restore');

        Route::get('/danh-muc', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::get('/danh-muc/them', [CategoryAdminController::class, 'create'])->name('categories.create');
        Route::post('/danh-muc', [CategoryAdminController::class, 'store'])->name('categories.store');
        Route::get('/danh-muc/{category}/sua', [CategoryAdminController::class, 'edit'])->name('categories.edit');
        Route::put('/danh-muc/{category}', [CategoryAdminController::class, 'update'])->name('categories.update');
        Route::patch('/danh-muc/{category}/an', [CategoryAdminController::class, 'hidden'])->name('categories.hidden');

        Route::get('/don-hang', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('/don-hang/cho-xac-nhan', [OrderAdminController::class, 'unconfirmed'])->name('orders.unconfirmed');
        Route::get('/don-hang/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::put('/don-hang/{order}/trang-thai', [OrderAdminController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/don-hang/{order}/huy', [OrderAdminController::class, 'cancel'])->name('orders.cancel');

        Route::get('/lich-do-mat', [AppointmentAdminController::class, 'index'])->name('appointments.index');
        Route::patch('/lich-do-mat/{appointment}/xac-nhan', [AppointmentAdminController::class, 'confirm'])->name('appointments.confirm');
        Route::patch('/lich-do-mat/{appointment}/huy', [AppointmentAdminController::class, 'cancel'])->name('appointments.cancel');
        Route::patch('/lich-do-mat/{appointment}/hoan-tat', [AppointmentAdminController::class, 'complete'])->name('appointments.complete');
        Route::patch('/lich-do-mat/{appointment}/khong-den', [AppointmentAdminController::class, 'noShow'])->name('appointments.no-show');

        Route::get('/hoan-doi', [ReturnAdminController::class, 'index'])->name('returns.index');
        Route::get('/hoan-doi/{return}', [ReturnAdminController::class, 'show'])->name('returns.show');
        Route::put('/hoan-doi/{return}', [ReturnAdminController::class, 'update'])->name('returns.update');

        Route::get('/kho-hang', [WarehouseAdminController::class, 'index'])->name('warehouses.index');
        Route::get('/kho-hang/phieu', [WarehouseAdminController::class, 'transactions'])->name('warehouses.transactions');
        Route::get('/kho-hang/them-hoa-don', [WarehouseAdminController::class, 'createTransaction'])->name('warehouses.create-transaction');
        Route::post('/kho-hang/phieu', [WarehouseAdminController::class, 'storeTransaction'])->name('warehouses.store-transaction');

        Route::get('/bai-viet', [PostAdminController::class, 'index'])->name('posts.index');
        Route::post('/bai-viet/upload-editor', [PostAdminController::class, 'uploadEditorImage'])->middleware('throttle:uploads')->name('posts.upload-editor');
        Route::get('/bai-viet/them', [PostAdminController::class, 'create'])->name('posts.create');
        Route::post('/bai-viet', [PostAdminController::class, 'store'])->name('posts.store');
        Route::get('/bai-viet/chuyen-muc', [PostAdminController::class, 'categories'])->name('posts.categories');
        Route::get('/bai-viet/chuyen-muc/them', [PostAdminController::class, 'createCategory'])->name('posts.categories.create');
        Route::post('/bai-viet/chuyen-muc', [PostAdminController::class, 'storeCategory'])->name('posts.categories.store');
        Route::get('/bai-viet/chuyen-muc/{category}/sua', [PostAdminController::class, 'editCategory'])->name('posts.categories.edit');
        Route::put('/bai-viet/chuyen-muc/{category}', [PostAdminController::class, 'updateCategory'])->name('posts.categories.update');
        Route::patch('/bai-viet/chuyen-muc/{category}/an', [PostAdminController::class, 'hiddenCategory'])->name('posts.categories.hidden');
        Route::get('/bai-viet/{post}/sua', [PostAdminController::class, 'edit'])->name('posts.edit');
        Route::put('/bai-viet/{post}', [PostAdminController::class, 'update'])->name('posts.update');
        Route::patch('/bai-viet/{post}/duyet', [PostAdminController::class, 'approve'])->name('posts.approve');
        Route::patch('/bai-viet/{post}/an', [PostAdminController::class, 'hidden'])->name('posts.hidden');

        Route::get('/danh-gia', [ReviewAdminController::class, 'index'])->name('reviews.index');
        Route::get('/danh-gia/{review}', [ReviewAdminController::class, 'show'])->name('reviews.show');
        Route::put('/danh-gia/{review}', [ReviewAdminController::class, 'update'])->name('reviews.update');

        Route::middleware('admin:ADMIN')->group(function () {
            Route::get('/bao-cao/san-pham', [ReportAdminController::class, 'products'])->name('reports.products');
            Route::get('/bao-cao/don-hang', [ReportAdminController::class, 'orders'])->name('reports.orders');
            Route::get('/bao-cao/bieu-do-luot-ban', [ReportAdminController::class, 'salesChart'])->name('reports.sales-chart');
            Route::get('/bao-cao/top-luot-ban', [ReportAdminController::class, 'topSales'])->name('reports.top-sales');
            Route::get('/bao-cao/luot-ban-theo-ngay', [ReportAdminController::class, 'dailySales'])->name('reports.daily-sales');

            Route::get('/thanh-vien', [CustomerAdminController::class, 'index'])->name('customers.index');
            Route::get('/thanh-vien/them', [CustomerAdminController::class, 'create'])->name('customers.create');
            Route::post('/thanh-vien', [CustomerAdminController::class, 'store'])->name('customers.store');
            Route::get('/thanh-vien/{user}/sua', [CustomerAdminController::class, 'edit'])->name('customers.edit');
            Route::put('/thanh-vien/{user}', [CustomerAdminController::class, 'update'])->name('customers.update');
            Route::patch('/thanh-vien/{user}/trang-thai', [CustomerAdminController::class, 'updateStatus'])->name('customers.status');

            Route::get('/thu-kinh', [TryOnSnapshotAdminController::class, 'index'])->name('tryon-snapshots.index');

            Route::get('/banner', [BannerAdminController::class, 'index'])->name('banners.index');
            Route::get('/banner/them', [BannerAdminController::class, 'create'])->name('banners.create');
            Route::post('/banner', [BannerAdminController::class, 'store'])->name('banners.store');
            Route::get('/banner/{banner}/sua', [BannerAdminController::class, 'edit'])->name('banners.edit');
            Route::put('/banner/{banner}', [BannerAdminController::class, 'update'])->name('banners.update');
            Route::patch('/banner/{banner}/an', [BannerAdminController::class, 'hidden'])->name('banners.hidden');

            Route::get('/bo-cuc-trang', [HomeLayoutAdminController::class, 'index'])->name('home-layout.index');
            Route::post('/bo-cuc-trang', [HomeLayoutAdminController::class, 'update'])->name('home-layout.update');
            Route::get('/nghiep-vu', [BusinessAdminController::class, 'index'])->name('business.index');
            Route::post('/nghiep-vu', [BusinessAdminController::class, 'store'])->name('business.store');
            Route::get('/khuyen-mai', [PromotionAdminController::class, 'index'])->name('promotions.index');
            Route::post('/khuyen-mai', [PromotionAdminController::class, 'store'])->name('promotions.store');
        });

        Route::get('/{oldRoute}', [AdminRouteAliasController::class, 'path'])
            ->where('oldRoute', 'danh-sach-san-pham|them-san-pham|cap-nhat-san-pham|thung-rac-san-pham|danh-sach-danh-muc|them-danh-muc|cap-nhat-danh-muc|danh-sach-don-hang|danh-sach-don-cho|cap-nhat-don-hang|yeu-cau-hoan-doi|chi-tiet-hoan-doi|danh-sach-bai-viet|them-bai-viet|cap-nhat-bai-viet|danh-muc-bai-viet|cap-nhat-danh-muc-bai-viet|binh-luan|chi-tiet-binh-luan|kho-hang|kho-hang2|them-hoa-don|danh-sach-khach-hang|them-tai-khoan|capnhat-tai-khoan|thong-ke-san-pham|thong-ke-don-hang|bieu-do-luot-ban|top-luot-ban|luot-ban-theo-ngay|xuat-exel|danh-sach-banner|them-banner|cap-nhat-banner|home-layout|nghiep-vu')
            ->name('php.path');
    });
});
