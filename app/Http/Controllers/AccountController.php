<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
    public function index(): View
    {
        $user = Auth::user();

        return view('account.index', [
            'user' => $user,
            'addresses' => $user->addresses()->orderByDesc('is_default')->latest()->get(),
            'orders' => $user->orders()->latest()->limit(5)->get(),
            'tryOnSnapshots' => TryOnSnapshot::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->paginate(6)
                ->withQueryString(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => Auth::user()->orders()
                ->with('items.product')
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function showOrder(Order $order): View
    {
        abort_unless($order->user_id === Auth::id(), 403);

        return view('account.orders.show', [
            'order' => $order->load(['items.product', 'returnRequests.reason', 'returnRequests.items']),
            'countedReturnStatuses' => ['PENDING', 'APPROVED', 'RECEIVED', 'COMPLETED'],
            'isReturnWindowOpen' => $order->delivered_at === null || $order->delivered_at->gte(now()->subDays(7)),
        ]);
    }

    public function invoice(Order $order): View
    {
        abort_unless($order->user_id === Auth::id(), 403);

        return view('account.orders.invoice', [
            'order' => $order->load(['user', 'items']),
            'backUrl' => route('account.orders.show', $order),
            'invoiceEmailSent' => session('invoice_email_sent'),
        ]);
    }

    public function emailInvoice(Order $order, OrderInvoiceEmailService $invoiceEmail): RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);

        return redirect()
            ->route('account.orders.invoice', $order)
            ->with('invoice_email_sent', $invoiceEmail->send($order));
    }

    public function editProfile(): View
    {
        return view('account.profile', [
            'user' => Auth::user(),
            'cities' => $this->cities(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            'gender' => ['nullable', 'in:MALE,FEMALE,OTHER'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'full_name.required' => 'Họ tên không được để trống.',
            'full_name.max' => 'Họ tên tối đa 100 ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'date_of_birth.date' => 'Ngày sinh không hợp lệ.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại.',
            'date_of_birth.after_or_equal' => 'Ngày sinh không hợp lệ.',
            'avatar.image' => 'Ảnh đại diện không hợp lệ.',
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'avatar.max' => 'Ảnh đại diện tối đa 5MB.',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload'), $fileName);
            $data['avatar_url'] = $fileName;
        }

        $user->forceFill([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? 'OTHER',
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
        ])->save();

        return redirect()->route('account.index')->with('success', 'Cập nhật hồ sơ thành công.');
    }

    public function editPassword(): View
    {
        return view('account.password', [
            'user' => Auth::user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ], [
            'current_password.required' => 'Mật khẩu hiện tại không được để trống.',
            'password.required' => 'Mật khẩu mới không được để trống.',
            'password.confirmed' => 'Xác nhận mật khẩu không trùng khớp.',
            'password.min' => 'Mật khẩu mới tối thiểu 8 ký tự.',
        ]);

        $user = Auth::user();

        if (! $this->passwordMatches($data['current_password'], (string) $user->password_hash)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        $user->forceFill([
            'password_hash' => Hash::make($data['password']),
        ])->save();

        return redirect()->route('account.index')->with('success', 'Đổi mật khẩu thành công.');
    }

    public function createAddress(): View|RedirectResponse
    {
        if (Auth::user()->addresses()->count() >= 2) {
            return redirect()->route('account.index')->with('error', 'Bạn chỉ có thể lưu tối đa 2 địa chỉ.');
        }

        return view('account.address-form', [
            'address' => new UserAddress(),
            'cities' => $this->cities(),
            'mode' => 'create',
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->addresses()->count() >= 2) {
            return redirect()->route('account.index')->with('error', 'Bạn chỉ có thể lưu tối đa 2 địa chỉ.');
        }

        $data = $this->validateAddress($request);
        $makeDefault = $request->boolean('is_default') || ! $user->addresses()->exists();

        DB::transaction(function () use ($user, $data, $makeDefault): void {
            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([
                'recipient_name' => $data['recipient_name'],
                'phone' => $data['phone'],
                'province_name' => $data['province_name'],
                'address_detail' => $data['address_detail'],
                'is_default' => $makeDefault,
            ]);
        });

        return redirect()->route('account.index')->with('success', 'Thêm địa chỉ thành công.');
    }

    // Mở form sửa địa chỉ.
    // Route gọi hàm này: GET /tai-khoan/dia-chi/{address}/sua.
    // Laravel tự lấy UserAddress theo id trên URL và truyền vào $address.
    public function editAddress(UserAddress $address): View
    {
        // Kiểm tra địa chỉ này có thuộc user đang đăng nhập không.
        // Nếu không kiểm tra, khách có thể sửa URL để sửa địa chỉ của người khác.
        $this->ensureOwnAddress($address);

        return view('account.address-form', [
            // Gửi địa chỉ hiện tại sang view để form điền sẵn.
            'address' => $address,
            'cities' => $this->cities(),

            // mode=edit giúp view biết phải gửi PUT update thay vì POST create.
            'mode' => 'edit',
        ]);
    }

    // Lưu thay đổi địa chỉ.
    // Route gọi hàm này: PUT /tai-khoan/dia-chi/{address}.
    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        // Chỉ chủ địa chỉ mới được cập nhật địa chỉ này.
        $this->ensureOwnAddress($address);

        // Validate dữ liệu địa chỉ.
        $data = $this->validateAddress($request);

        // Nếu người dùng tick mặc định thì $makeDefault = true.
        // Nếu đây là địa chỉ duy nhất còn lại thì cũng bắt buộc làm mặc định.
        // whereKeyNot($address->id) nghĩa là tìm địa chỉ khác id hiện tại.
        $makeDefault = $request->boolean('is_default') || ! Auth::user()->addresses()->whereKeyNot($address->id)->exists();

        DB::transaction(function () use ($address, $data, $makeDefault): void {
            // Nếu địa chỉ đang sửa được đặt mặc định, các địa chỉ khác của user bỏ mặc định.
            if ($makeDefault) {
                Auth::user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            // Cập nhật dữ liệu địa chỉ hiện tại.
            $address->update([
                'recipient_name' => $data['recipient_name'],
                'phone' => $data['phone'],
                'province_name' => $data['province_name'],
                'address_detail' => $data['address_detail'],
                'is_default' => $makeDefault,
            ]);
        });

        return redirect()->route('account.index')->with('success', 'Cập nhật địa chỉ thành công.');
    }

    // Xóa địa chỉ.
    // Route gọi hàm này: DELETE /tai-khoan/dia-chi/{address}.
    public function destroyAddress(UserAddress $address): RedirectResponse
    {
        // Chặn xóa địa chỉ của người khác.
        $this->ensureOwnAddress($address);

        DB::transaction(function () use ($address): void {
            // Ghi nhớ địa chỉ sắp xóa có phải địa chỉ mặc định không.
            $wasDefault = $address->is_default;

            // Xóa địa chỉ khỏi database.
            $address->delete();

            // Nếu địa chỉ bị xóa là mặc định thì phải chọn một địa chỉ khác làm mặc định.
            if ($wasDefault) {
                // Lấy địa chỉ còn lại mới nhất của user.
                $remaining = Auth::user()->addresses()->latest()->first();

                // Dấu ?-> nghĩa là nếu $remaining khác null thì mới update.
                // Nếu không còn địa chỉ nào thì không làm gì.
                $remaining?->update(['is_default' => true]);
            }
        });

        return redirect()->route('account.index')->with('success', 'Xóa địa chỉ thành công.');
    }

    // Helper validate địa chỉ, dùng chung cho storeAddress() và updateAddress().
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
    private function ensureOwnAddress(UserAddress $address): void
    {
        // Nếu address.user_id khác user đang đăng nhập thì trả lỗi 403.
        abort_unless($address->user_id === Auth::id(), 403);
    }

    // Helper kiểm tra mật khẩu.
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
