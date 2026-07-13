<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
        ]);
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

    public function editAddress(UserAddress $address): View
    {
        $this->ensureOwnAddress($address);

        return view('account.address-form', [
            'address' => $address,
            'cities' => $this->cities(),
            'mode' => 'edit',
        ]);
    }

    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        $this->ensureOwnAddress($address);

        $data = $this->validateAddress($request);
        $makeDefault = $request->boolean('is_default') || ! Auth::user()->addresses()->whereKeyNot($address->id)->exists();

        DB::transaction(function () use ($address, $data, $makeDefault): void {
            if ($makeDefault) {
                Auth::user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

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

    public function destroyAddress(UserAddress $address): RedirectResponse
    {
        $this->ensureOwnAddress($address);

        DB::transaction(function () use ($address): void {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $remaining = Auth::user()->addresses()->latest()->first();
                $remaining?->update(['is_default' => true]);
            }
        });

        return redirect()->route('account.index')->with('success', 'Xóa địa chỉ thành công.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            'province_name' => ['required', 'string', 'max:100', 'in:' . implode(',', $this->cities())],
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

    private function ensureOwnAddress(UserAddress $address): void
    {
        abort_unless($address->user_id === Auth::id(), 403);
    }

    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            return Hash::check($password, $hash);
        } catch (\RuntimeException) {
            return password_verify($password, $hash);
        }
    }

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
