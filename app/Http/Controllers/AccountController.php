<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCancellationService;
use App\Services\OrderInvoiceEmailService;
use App\Models\TryOnSnapshot;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Hiển thị trang tài khoản của người dùng.
     */
    public function index(): View
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = Auth::user();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'user' => $user,
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'addresses' => $user->addresses()->orderByDesc('is_default')->latest()->get(),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'orders' => $user->orders()->latest()->limit(5)->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'tryOnSnapshots' => TryOnSnapshot::query()
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('user_id', $user->id)
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->latest('id')
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->paginate(6)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->withQueryString(),
        ]);
    }

    /**
     * Hiển thị đơn hàng của người dùng và các bộ lọc.
     */
    public function orders(Request $request): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.orders.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'orders' => Auth::user()->orders()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with([
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    'items.product',
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    'returnRequests' => fn ($query) => $query->with('items')->latest('requested_at'),
                ])
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->when($request->filled('status'), function ($query) use ($request) {
                    // Luong: Gan ket qua xu ly vao bien $status.
                    $status = $request->status;

                    // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                    if ($status === 'RETURN_PENDING') {
                        // Luong: Tra ve ket qua cuoi cung cua ham.
                        return $query->where(function ($inner) {
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            $inner->where('status', 'RETURN_PENDING')
                                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                ->orWhereHas('returnRequests', fn ($return) => $return->whereIn('status', ['PENDING', 'APPROVED', 'RECEIVED']));
                        });
                    }

                    // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                    if (in_array($status, ['RETURNED', 'EXCHANGED'], true)) {
                        // Luong: Gan ket qua xu ly vao bien $type.
                        $type = $status === 'EXCHANGED' ? 'EXCHANGE' : 'RETURN';

                        // Luong: Tra ve ket qua cuoi cung cua ham.
                        return $query->where(function ($inner) use ($status, $type) {
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            $inner->where('status', $status)
                                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                ->orWhereHas('returnRequests', fn ($return) => $return->where('status', 'COMPLETED')->where('type', $type));
                        });
                    }

                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return $query->where('status', $status);
                })
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->latest()
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->paginate(12)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->withQueryString(),
        ]);
    }

    /**
     * Hiển thị chi tiết đơn hàng của người dùng.
     */
    public function showOrder(Order $order): View
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($order->user_id === Auth::id(), 403);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.orders.show', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order' => $order->load([
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'items.product',
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                'returnRequests' => fn ($query) => $query->with(['reason', 'items'])->latest('requested_at'),
            ]),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'countedReturnStatuses' => ['PENDING', 'APPROVED', 'RECEIVED', 'COMPLETED'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'isReturnWindowOpen' => $order->delivered_at === null || $order->delivered_at->gte(now()->subDays(7)),
        ]);
    }

    /**
     * Hiển thị hóa đơn của đơn hàng.
     */
    public function invoice(Order $order): View
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($order->user_id === Auth::id(), 403);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.orders.invoice', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order' => $order->load(['user', 'items']),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backUrl' => route('account.orders.show', $order),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'invoiceEmailSent' => session('invoice_email_sent'),
        ]);
    }

    /**
     * Gửi hóa đơn đơn hàng qua email.
     */
    public function emailInvoice(Order $order, OrderInvoiceEmailService $invoiceEmail): RedirectResponse
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($order->user_id === Auth::id(), 403);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->route('account.orders.invoice', $order)
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('invoice_email_sent', $invoiceEmail->send($order));
    }

    public function cancelOrder(Request $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'cancel_reason.max' => 'Lý do hủy đơn tối đa 500 ký tự.',
        ]);

        $result = $cancellations->requestCancellationFromCustomer($order, $data['cancel_reason'] ?? null);

        if ($result === OrderCancellationService::CUSTOMER_REVIEW_REQUESTED) {
            return back()->with('success', 'Đã gửi yêu cầu hủy đơn đến admin. Cửa hàng sẽ kiểm tra và xử lý sớm.');
        }

        if ($result !== true) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Đơn hàng đã được hủy và email thông báo đã được gửi cho bạn.');
    }

    /**
     * Hiển thị form sửa hồ sơ cá nhân.
     */
    public function editProfile(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.profile', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'user' => Auth::user(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cities' => $this->cities(),
        ]);
    }

    /**
     * Cập nhật hồ sơ cá nhân của người dùng.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = Auth::user();

        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name' => ['required', 'string', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'phone' => ['nullable', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'gender' => ['nullable', 'in:MALE,FEMALE,OTHER'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name.required' => 'Họ tên không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name.max' => 'Họ tên tối đa 100 ký tự.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'gender.in' => 'Giới tính không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date_of_birth.date' => 'Ngày sinh không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date_of_birth.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date_of_birth.after_or_equal' => 'Ngày sinh không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'avatar.image' => 'Ảnh đại diện không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận JPG, PNG hoặc WEBP.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'avatar.max' => 'Ảnh đại diện tối đa 5MB.',
        ]);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->hasFile('avatar')) {
            // Luong: Gan ket qua xu ly vao bien $file.
            $file = $request->file('avatar');
            // Luong: Gan ket qua xu ly vao bien $fileName.
            $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $file->move(public_path('upload'), $fileName);
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $data['avatar_url'] = $fileName;
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $user->forceFill([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name' => $data['full_name'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'phone' => $data['phone'] ?? null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'gender' => $data['gender'] ?? 'OTHER',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date_of_birth' => $data['date_of_birth'] ?? null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
        ])->save();

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.index')->with('success', 'Cập nhật hồ sơ thành công.');
    }

    /**
     * Hiển thị form đổi mật khẩu.
     */
    public function editPassword(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'user' => Auth::user(),
        ]);
    }

    /**
     * Đổi mật khẩu cho người dùng.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'current_password' => ['required', 'string', 'max:255'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'current_password.required' => 'Mật khẩu hiện tại không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.required' => 'Mật khẩu mới không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.confirmed' => 'Xác nhận mật khẩu không trùng khớp.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.min' => 'Mật khẩu mới tối thiểu 8 ký tự.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = Auth::user();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->passwordMatches($data['current_password'], (string) $user->password_hash)) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $user->forceFill([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password_hash' => Hash::make($data['password']),
        ])->save();

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.index')->with('success', 'Đổi mật khẩu thành công.');
    }

    /**
     * Hiển thị form thêm địa chỉ.
     */
    public function createAddress(): View|RedirectResponse
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (Auth::user()->addresses()->count() >= 2) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('account.index')->with('error', 'Bạn chỉ có thể lưu tối đa 2 địa chỉ.');
        }

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.address-form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address' => new UserAddress(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cities' => $this->cities(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'mode' => 'create',
        ]);
    }

    /**
     * Lưu địa chỉ mới của người dùng.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = Auth::user();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($user->addresses()->count() >= 2) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('account.index')->with('error', 'Bạn chỉ có thể lưu tối đa 2 địa chỉ.');
        }

        // Luong: Gan ket qua xu ly vao bien $data.
        $data = $this->validateAddress($request);
        // Luong: Gan ket qua xu ly vao bien $makeDefault.
        $makeDefault = $request->boolean('is_default') || ! $user->addresses()->exists();

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function () use ($user, $data, $makeDefault): void {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($makeDefault) {
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                $user->addresses()->update(['is_default' => false]);
            }

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $user->addresses()->create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'recipient_name' => $data['recipient_name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'phone' => $data['phone'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'province_name' => $data['province_name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'address_detail' => $data['address_detail'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'is_default' => $makeDefault,
            ]);
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.index')->with('success', 'Thêm địa chỉ thành công.');
    }

    // Mở form sửa địa chỉ.
    // Route gọi hàm này: GET /tai-khoan/dia-chi/{address}/sua.
    // Laravel tự lấy UserAddress theo id trên URL và truyền vào $address.
    /**
     * Hiển thị form sửa địa chỉ.
     */
    public function editAddress(UserAddress $address): View
    {
        // Kiểm tra địa chỉ này có thuộc user đang đăng nhập không.
        // Nếu không kiểm tra, khách có thể sửa URL để sửa địa chỉ của người khác.
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->ensureOwnAddress($address);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('account.address-form', [
            // Gửi địa chỉ hiện tại sang view để form điền sẵn.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address' => $address,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cities' => $this->cities(),

            // mode=edit giúp view biết phải gửi PUT update thay vì POST create.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'mode' => 'edit',
        ]);
    }

    // Lưu thay đổi địa chỉ.
    // Route gọi hàm này: PUT /tai-khoan/dia-chi/{address}.
    /**
     * Cập nhật địa chỉ của người dùng.
     */
    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        // Chỉ chủ địa chỉ mới được cập nhật địa chỉ này.
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->ensureOwnAddress($address);

        // Validate dữ liệu địa chỉ.
        // Luong: Gan ket qua xu ly vao bien $data.
        $data = $this->validateAddress($request);

        // Nếu người dùng tick mặc định thì $makeDefault = true.
        // Nếu đây là địa chỉ duy nhất còn lại thì cũng bắt buộc làm mặc định.
        // whereKeyNot($address->id) nghĩa là tìm địa chỉ khác id hiện tại.
        // Luong: Bo sung dieu kien loc du lieu cho truy van.
        $makeDefault = $request->boolean('is_default') || ! Auth::user()->addresses()->whereKeyNot($address->id)->exists();

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function () use ($address, $data, $makeDefault): void {
            // Nếu địa chỉ đang sửa được đặt mặc định, các địa chỉ khác của user bỏ mặc định.
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($makeDefault) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                Auth::user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            // Cập nhật dữ liệu địa chỉ hiện tại.
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            $address->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'recipient_name' => $data['recipient_name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'phone' => $data['phone'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'province_name' => $data['province_name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'address_detail' => $data['address_detail'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'is_default' => $makeDefault,
            ]);
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.index')->with('success', 'Cập nhật địa chỉ thành công.');
    }

    // Xóa địa chỉ.
    // Route gọi hàm này: DELETE /tai-khoan/dia-chi/{address}.
    /**
     * Xóa địa chỉ của người dùng.
     */
    public function destroyAddress(UserAddress $address): RedirectResponse
    {
        // Chặn xóa địa chỉ của người khác.
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->ensureOwnAddress($address);

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function () use ($address): void {
            // Ghi nhớ địa chỉ sắp xóa có phải địa chỉ mặc định không.
            // Luong: Gan ket qua xu ly vao bien $wasDefault.
            $wasDefault = $address->is_default;

            // Xóa địa chỉ khỏi database.
            // Luong: Xoa ban ghi phu hop voi dieu kien xu ly.
            $address->delete();

            // Nếu địa chỉ bị xóa là mặc định thì phải chọn một địa chỉ khác làm mặc định.
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($wasDefault) {
                // Lấy địa chỉ còn lại mới nhất của user.
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                $remaining = Auth::user()->addresses()->latest()->first();

                // Dấu ?-> nghĩa là nếu $remaining khác null thì mới update.
                // Nếu không còn địa chỉ nào thì không làm gì.
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                $remaining?->update(['is_default' => true]);
            }
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.index')->with('success', 'Xóa địa chỉ thành công.');
    }

    // Helper validate địa chỉ, dùng chung cho storeAddress() và updateAddress().
    /**
     * Kiểm tra dữ liệu địa chỉ.
     */
    private function validateAddress(Request $request): array
    {
        return $request->validate([
            // Tên người nhận bắt buộc, tối đa 100 ký tự.
            'recipient_name' => ['required', 'string', 'max:100'],

            // Số điện thoại bắt buộc, đúng đầu số di động Việt Nam và đủ 10 số.
            'phone' => ['required', 'regex:/^(03|05|07|08|09)\d{8}$/'],

            // Tỉnh/thành bắt buộc, tối đa 100 ký tự.
            // Rule in:... chỉ cho chọn tỉnh/thành nằm trong danh sách cities().
            'province_name' => ['required', 'string', 'max:100', 'in:' . implode(',', $this->cities())],

            // Địa chỉ chi tiết bắt buộc, tối đa 255 ký tự.
            'address_detail' => ['required', 'string', 'max:255'],
        ], [
            'recipient_name.required' => 'Họ tên người nhận không được để trống.',
            'recipient_name.max' => 'Họ tên người nhận tối đa 100 ký tự.',
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'province_name.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'province_name.in' => 'Tỉnh/Thành phố không hợp lệ.',
            'address_detail.required' => 'Địa chỉ chi tiết không được để trống.',
            'address_detail.max' => 'Địa chỉ chi tiết tối đa 255 ký tự.',
        ]);
    }

    // Helper kiểm tra quyền sở hữu địa chỉ.
    /**
     * Chặn thao tác trên địa chỉ của người khác.
     */
    private function ensureOwnAddress(UserAddress $address): void
    {
        // Nếu address.user_id khác user đang đăng nhập thì trả lỗi 403.
        abort_unless($address->user_id === Auth::id(), 403);
    }

    // Helper kiểm tra mật khẩu.
    /**
     * Kiểm tra mật khẩu có khớp với hash không.
     */
    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            // Hash::check() là cách chuẩn của Laravel để so mật khẩu thô với hash.
            return Hash::check($password, $hash);
        } catch (\RuntimeException) {
            // Fallback cho trường hợp dữ liệu cũ không đúng format hash Laravel.
            return password_verify($password, $hash);
        }
    }

    // Danh sách tỉnh/thành cho form địa chỉ.
    /**
     * Trả về danh sách tỉnh/thành.
     */
    private function cities(): array
    {
        return [
            'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
            'An Giang', 'Bà Rịa Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
            'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước',
            'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông',
            'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang',
            'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 'Hậu Giang', 'Hòa Bình',
            'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu',
            'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định',
            'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên',
            'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị',
            'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên',
            'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang',
            'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
        ];
    }
}
