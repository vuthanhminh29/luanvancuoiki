<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClientRouteAliasController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\VnPayController;
use Illuminate\Support\Facades\Route;

// Route trang chá»§ phÃ­a khÃ¡ch hÃ ng, trá» qua controller alias Ä‘á»ƒ gom logic chuyá»ƒn hÆ°á»›ng cÅ©/má»›i.
Route::get('/', [ClientRouteAliasController::class, 'home'])->name('home');
Route::get('/index.php', [ClientRouteAliasController::class, 'index'])->name('php.client.index');

// NhÃ³m route Ä‘á»c dá»¯ liá»‡u cÃ´ng khai: thá»­ kÃ­nh, danh sÃ¡ch sáº£n pháº©m vÃ  chi tiáº¿t sáº£n pháº©m.
Route::get('/thu-kinh/model-check', [ProductController::class, 'tryOnModelCheck'])->middleware('throttle:web-read')->name('tryon.model-check');
Route::get('/thu-kinh', [ProductController::class, 'tryOn'])->middleware('throttle:web-read')->name('tryon');
Route::get('/san-pham', [ProductController::class, 'index'])->middleware('throttle:web-read')->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->middleware('throttle:web-read')->name('products.show');

// Route ná»™i dung tÄ©nh/cÃ´ng khai: bÃ i viáº¿t, liÃªn há»‡ vÃ  há»— trá»£ khÃ¡ch hÃ ng.
Route::get('/bai-viet', [BlogController::class, 'index'])->middleware('throttle:web-read')->name('blog.index');
Route::get('/bai-viet/{post:slug}', [BlogController::class, 'show'])->middleware('throttle:web-read')->name('blog.show');
Route::get('/lien-he', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/ho-tro', [PageController::class, 'support'])->middleware('throttle:web-read')->name('pages.support');

// Route nháº­n káº¿t quáº£ thanh toÃ¡n VNPAY sau khi khÃ¡ch quay láº¡i hoáº·c VNPAY gá»i IPN.
Route::get('/vnpay/return', [VnPayController::class, 'return'])->name('vnpay.return');
Route::match(['get', 'post'], '/vnpay/ipn', [VnPayController::class, 'ipn'])->name('vnpay.ipn');

// Route xÃ¡c thá»±c email dÃ¹ng URL signed Ä‘á»ƒ trÃ¡nh sá»­a user/hash trong link.
Route::get('/xac-thuc-email/{user}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:auth'])
    ->name('verification.verify');

// Route giá» hÃ ng: xem, thÃªm, cáº­p nháº­t sá»‘ lÆ°á»£ng vÃ  xÃ³a biáº¿n thá»ƒ khá»i giá».
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang', [CartController::class, 'store'])->middleware('throttle:cart')->name('cart.store');
Route::put('/gio-hang', [CartController::class, 'update'])->middleware('throttle:cart')->name('cart.update');
Route::delete('/gio-hang/{variant}', [CartController::class, 'destroy'])->middleware('throttle:cart')->name('cart.destroy');

// Chá»‰ khÃ¡ch chÆ°a Ä‘Äƒng nháº­p má»›i Ä‘Æ°á»£c vÃ o cÃ¡c trang Ä‘Äƒng nháº­p, Ä‘Äƒng kÃ½ vÃ  quÃªn máº­t kháº©u.
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.store');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register.store');
    Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/quen-mat-khau', [AuthController::class, 'sendResetPasswordLink'])->middleware('throttle:auth')->name('password.email');
    Route::get('/khoi-phuc-mat-khau/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/khoi-phuc-mat-khau', [AuthController::class, 'resetPassword'])->middleware('throttle:auth')->name('password.update');
});

// CÃ¡c route bÃªn dÆ°á»›i báº¯t buá»™c Ä‘Äƒng nháº­p vÃ¬ cÃ³ dá»¯ liá»‡u cÃ¡ nhÃ¢n, Ä‘Æ¡n hÃ ng hoáº·c thao tÃ¡c mua hÃ ng.
Route::middleware('auth')->group(function () {
    // ÄÄƒng xuáº¥t há»— trá»£ cáº£ GET vÃ  POST Ä‘á»ƒ tÆ°Æ¡ng thÃ­ch giao diá»‡n cÅ©/má»›i.
    Route::get('/dang-xuat', [AuthController::class, 'logout'])->name('logout.get');
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');

    // Khu vá»±c tÃ i khoáº£n: há»“ sÆ¡, máº­t kháº©u, Ä‘á»‹a chá»‰ vÃ  lá»‹ch sá»­ Ä‘Æ¡n hÃ ng.
    Route::get('/tai-khoan', [AccountController::class, 'index'])->name('account.index');
    Route::get('/tai-khoan/ho-so', [AccountController::class, 'editProfile'])->name('account.profile.edit');
    Route::put('/tai-khoan/ho-so', [AccountController::class, 'updateProfile'])->middleware('throttle:user-actions')->name('account.profile.update');
    Route::get('/tai-khoan/doi-mat-khau', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/tai-khoan/doi-mat-khau', [AccountController::class, 'updatePassword'])->middleware('throttle:user-actions')->name('account.password.update');
    Route::get('/tai-khoan/dia-chi/them', [AccountController::class, 'createAddress'])->name('account.addresses.create');
    Route::post('/tai-khoan/dia-chi', [AccountController::class, 'storeAddress'])->middleware('throttle:user-actions')->name('account.addresses.store');
    Route::get('/tai-khoan/dia-chi/{address}/sua', [AccountController::class, 'editAddress'])->name('account.addresses.edit');
    Route::put('/tai-khoan/dia-chi/{address}', [AccountController::class, 'updateAddress'])->middleware('throttle:user-actions')->name('account.addresses.update');
    Route::delete('/tai-khoan/dia-chi/{address}', [AccountController::class, 'destroyAddress'])->middleware('throttle:user-actions')->name('account.addresses.destroy');
    Route::get('/tai-khoan/don-hang', [AccountController::class, 'orders'])->name('account.orders.index');
    Route::get('/tai-khoan/don-hang/{order}', [AccountController::class, 'showOrder'])->name('account.orders.show');

    // User Ä‘Ã£ mua hÃ ng cÃ³ thá»ƒ gá»­i Ä‘Ã¡nh giÃ¡ cho sáº£n pháº©m.
    Route::post('/san-pham/{product:slug}/danh-gia', [ProductController::class, 'storeReview'])->middleware('throttle:user-actions')->name('products.reviews.store');

    // Luá»“ng thanh toÃ¡n vÃ  Ã¡p/xÃ³a mÃ£ giáº£m giÃ¡ trong phiÃªn checkout.
    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/thanh-toan', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
    Route::post('/thanh-toan/ma-giam-gia', [CheckoutController::class, 'applyPromotion'])->middleware('throttle:checkout')->name('checkout.promotion.apply');
    Route::post('/thanh-toan/ma-giam-gia/xoa', [CheckoutController::class, 'removePromotion'])->middleware('throttle:checkout')->name('checkout.promotion.remove');

    // Luá»“ng yÃªu cáº§u hoÃ n Ä‘á»•i theo Ä‘Æ¡n hÃ ng cá»§a khÃ¡ch.
    Route::get('/hoan-doi', [ReturnRequestController::class, 'index'])->name('returns.index');
    Route::get('/hoan-doi/don-hang/{order}', [ReturnRequestController::class, 'create'])->name('returns.create');
    Route::post('/hoan-doi/don-hang/{order}', [ReturnRequestController::class, 'store'])->middleware('throttle:user-actions')->name('returns.store');
    Route::get('/hoan-doi/{return}', [ReturnRequestController::class, 'show'])->name('returns.show');
});

// Route alias cho cÃ¡c Ä‘Æ°á»ng dáº«n PHP cÅ©, giÃºp link cÅ© váº«n má»Ÿ Ä‘Æ°á»£c trang má»›i.
Route::get('/{oldRoute}', [ClientRouteAliasController::class, 'path'])
    ->where('oldRoute', 'trang-chu|cua-hang|chitietsanpham|danh-muc-san-pham|thanh-toan-2|thanh-toan-dia-chi2|edit-address|remove-address|cam-on|don-hang|chi-tiet-don-hang|yeu-cau-hoan-doi|phan-hoi-hoan-doi|thong-tin-tai-khoan|ho-so|them-dia-chi|doi-mat-khau|quen-mat-khau|khoi-phuc-mat-khau|chi-tiet-bai-viet|danh-muc-bai-viet|tim-kiem|ho-tro-khach-hang|chinh-sach-van-chuyen|chinh-sach-thanh-toan|chinh-sach-doi-tra|thanh-toan-momo|thanh-toan-momo-address|thanh-toan-momo-address-2')
    ->name('php.client.path');

// Náº¡p thÃªm toÃ n bá»™ route quáº£n trá»‹ tá»« routes/admin.php.
require __DIR__ . '/admin.php';

// Route dashboard máº·c Ä‘á»‹nh cá»§a Jetstream/Sanctum náº¿u pháº§n nÃ y cÃ²n Ä‘Æ°á»£c dÃ¹ng trong há»‡ thá»‘ng.
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
