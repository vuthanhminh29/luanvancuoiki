<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClientRouteAliasController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderCancellationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\StyleAdvisorController;
use App\Http\Controllers\VnPayController;
use Illuminate\Support\Facades\Route;

// Route trang chủ phía khách hàng, trỏ qua controller alias để gom logic chuyển hướng cũ/mới.
Route::get('/', [ClientRouteAliasController::class, 'home'])->name('home');
Route::get('/index.php', [ClientRouteAliasController::class, 'index'])->name('php.client.index');

// Nhóm route đọc dữ liệu công khai: thử kính, danh sách sản phẩm và chi tiết sản phẩm.
Route::get('/thu-kinh/model-check', [ProductController::class, 'tryOnModelCheck'])->middleware('throttle:web-read')->name('tryon.model-check');
Route::get('/thu-kinh', [ProductController::class, 'tryOn'])->middleware('throttle:web-read')->name('tryon');
Route::get('/san-pham', [ProductController::class, 'index'])->middleware('throttle:web-read')->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->middleware('throttle:web-read')->name('products.show');

// Route nội dung tĩnh/công khai: bài viết, liên hệ và hỗ trợ khách hàng.
Route::get('/bai-viet', [BlogController::class, 'index'])->middleware('throttle:web-read')->name('blog.index');
Route::get('/bai-viet/{post:slug}', [BlogController::class, 'show'])->middleware('throttle:web-read')->name('blog.show');
Route::get('/lien-he', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/ho-tro', [PageController::class, 'support'])->middleware('throttle:web-read')->name('pages.support');

// Công cụ tư vấn: tìm dáng kính theo khuôn mặt.
Route::get('/tim-dang-kinh', [StyleAdvisorController::class, 'faceShape'])->middleware('throttle:web-read')->name('style.face-shape');

// Trợ lý tư vấn AI. Mở cho cả khách chưa đăng nhập vì widget nằm ở mọi trang,
// nhưng phải có throttle riêng: mỗi lượt nhắn là một lần gọi API AI tính phí.
Route::post('/tro-ly-tu-van/chat', [ChatbotController::class, 'chat'])->middleware('throttle:chatbot')->name('chatbot.chat');

// Đặt lịch đo thị lực tại cửa hàng. Mở cho cả khách chưa đăng nhập.
Route::get('/dat-lich-do-mat', [AppointmentController::class, 'create'])->middleware('throttle:web-read')->name('appointments.create');
Route::post('/dat-lich-do-mat', [AppointmentController::class, 'store'])->middleware('throttle:user-actions')->name('appointments.store');
Route::get('/lich-hen/khung-gio-khong-kha-dung', [AppointmentController::class, 'unavailableSlots'])->middleware('throttle:web-read')->name('appointments.unavailable-slots');
Route::get('/tra-cuu-lich-hen', [AppointmentController::class, 'lookup'])->middleware('throttle:web-read')->name('appointments.lookup');
Route::patch('/tra-cuu-lich-hen/{appointment}/doi-lich', [AppointmentController::class, 'reschedule'])->middleware('throttle:user-actions')->name('appointments.reschedule');

// Route nhận kết quả thanh toán VNPAY sau khi khách quay lại hoặc VNPAY gọi IPN.
// Hai route này công khai và có thay đổi trạng thái đơn hàng, nên phải có throttle:
// nếu không, kẻ tấn công có thể dò mã đơn (ORD + thời gian + 3 ký tự) không giới hạn.
Route::get('/vnpay/return', [VnPayController::class, 'return'])->middleware('throttle:payment-callback')->name('vnpay.return');
Route::match(['get', 'post'], '/vnpay/ipn', [VnPayController::class, 'ipn'])->middleware('throttle:payment-callback')->name('vnpay.ipn');

// Route xác thực email dùng URL signed để tránh sửa user/hash trong link.
Route::get('/xac-thuc-email/{user}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:auth'])
    ->name('verification.verify');

// Route giỏ hàng: xem, thêm, cập nhật số lượng và xóa biến thể khỏi giỏ.
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang', [CartController::class, 'store'])->middleware('throttle:cart')->name('cart.store');
Route::put('/gio-hang', [CartController::class, 'update'])->middleware('throttle:cart')->name('cart.update');
Route::delete('/gio-hang/{variant}', [CartController::class, 'destroy'])->middleware('throttle:cart')->name('cart.destroy');

// Route xác nhận hủy đơn hàng từ email, có signed middleware.
Route::get('/don-hang/{order}/xac-nhan-huy/{token}', [OrderCancellationController::class, 'show'])
    ->middleware(['signed', 'throttle:user-actions'])
    ->name('orders.cancel-confirm.show');
Route::post('/don-hang/{order}/xac-nhan-huy/{token}', [OrderCancellationController::class, 'confirm'])
    ->middleware(['signed', 'throttle:user-actions'])
    ->name('orders.cancel-confirm.submit');

// Chỉ khách chưa đăng nhập mới được vào các trang đăng nhập, đăng ký và quên mật khẩu.
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

// Các route bên dưới bắt buộc đăng nhập vì có dữ liệu cá nhân, đơn hàng hoặc thao tác mua hàng.
Route::middleware('auth')->group(function () {
    // Đăng xuất là thao tác thay đổi session nên chỉ nhận POST.
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');

    // Khu vực tài khoản: hồ sơ, mật khẩu, địa chỉ và lịch sử đơn hàng.
    Route::get('/tai-khoan', [AccountController::class, 'index'])->name('account.index');
    // Trang sửa hồ sơ: trước đây route này chỉ redirect về /tai-khoan nên
    // AccountController::editProfile/updateProfile thành code chết, khách không sửa được hồ sơ.
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
    Route::post('/tai-khoan/don-hang/{order}/huy', [AccountController::class, 'cancelOrder'])->middleware('throttle:user-actions')->name('account.orders.cancel');
    Route::get('/tai-khoan/don-hang/{order}/hoa-don', [AccountController::class, 'invoice'])->name('account.orders.invoice');
    Route::post('/tai-khoan/don-hang/{order}/hoa-don/gui-email', [AccountController::class, 'emailInvoice'])->middleware('throttle:user-actions')->name('account.orders.invoice.email');

    // User đã mua hàng có thể gửi đánh giá cho sản phẩm.
    Route::post('/san-pham/{product:slug}/danh-gia', [ProductController::class, 'storeReview'])->middleware('throttle:user-actions')->name('products.reviews.store');
    // Lưu ảnh chụp, email, tên khách và kính/model khách vừa thử.
    Route::post('/thu-kinh/luu-ket-qua', [ProductController::class, 'storeTryOnSnapshot'])->middleware('throttle:user-actions')->name('tryon.snapshots.store');

    // Luồng thanh toán và áp/xóa mã giảm giá trong phiên checkout.
    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/thanh-toan', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
    Route::post('/thanh-toan/ma-giam-gia', [CheckoutController::class, 'applyPromotion'])->middleware('throttle:checkout')->name('checkout.promotion.apply');
    Route::post('/thanh-toan/ma-giam-gia/xoa', [CheckoutController::class, 'removePromotion'])->middleware('throttle:checkout')->name('checkout.promotion.remove');

    // Luồng yêu cầu hoàn đổi theo đơn hàng của khách.
    Route::get('/hoan-doi', [ReturnRequestController::class, 'index'])->name('returns.index');
    Route::get('/hoan-doi/don-hang/{order}', [ReturnRequestController::class, 'create'])->name('returns.create');
    Route::post('/hoan-doi/don-hang/{order}', [ReturnRequestController::class, 'store'])->middleware('throttle:user-actions')->name('returns.store');
    Route::get('/hoan-doi/{return}', [ReturnRequestController::class, 'show'])->name('returns.show');
});

// Route alias cho các đường dẫn PHP cũ, giúp link cũ vẫn mở được trang mới.
Route::get('/{oldRoute}', [ClientRouteAliasController::class, 'path'])
    ->where('oldRoute', 'trang-chu|cua-hang|chitietsanpham|danh-muc-san-pham|thanh-toan-2|thanh-toan-dia-chi2|edit-address|remove-address|cam-on|don-hang|chi-tiet-don-hang|yeu-cau-hoan-doi|phan-hoi-hoan-doi|thong-tin-tai-khoan|ho-so|them-dia-chi|doi-mat-khau|quen-mat-khau|khoi-phuc-mat-khau|chi-tiet-bai-viet|danh-muc-bai-viet|tim-kiem|ho-tro-khach-hang|chinh-sach-van-chuyen|chinh-sach-thanh-toan|chinh-sach-doi-tra|thanh-toan-momo|thanh-toan-momo-address|thanh-toan-momo-address-2')
    ->name('php.client.path');

// Nạp thêm toàn bộ route quản trị từ routes/admin.php.
require __DIR__ . '/admin.php';

// Route dashboard mặc định của Jetstream/Sanctum nếu phần này còn được dùng trong hệ thống.
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
