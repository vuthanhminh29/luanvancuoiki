<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Hiển thị form đăng nhập.
     */
    public function showLogin(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('auth.login');
    }

    /**
     * Kiểm tra thông tin và đăng nhập người dùng.
     */
    public function login(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $credentials = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => ['required', 'email', 'max:255'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => ['required', 'string', 'max:255'],
        ]);

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $user = User::where('email', $credentials['email'])->first();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $user || ! $this->passwordMatches($credentials['password'], (string) $user->password_hash)) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($user->status !== 'ACTIVE') {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['email' => 'Tài khoản đã bị khóa.'])->onlyInput('email');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $user->email_verified_at) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['email' => 'Vui lòng kiểm tra Gmail và bấm link xác thực trước khi đăng nhập.'])->onlyInput('email');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (Hash::needsRehash((string) $user->password_hash)) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $user->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_hash' => Hash::make($credentials['password']),
            ])->save();
        }

        // Luong: Dang nhap nguoi dung vao phien hien tai.
        Auth::login($user);
        // Luong: Cap nhat trang thai session sau thao tac xac thuc.
        $request->session()->regenerate();

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->intended(route('account.index'));
    }

    /**
     * Hiển thị form đăng ký.
     */
    public function showRegister(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('auth.register', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cities' => $this->cities(),
        ]);
    }

    /**
     * Tạo tài khoản mới và gửi email xác thực.
     */
    public function register(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name' => ['required', 'string', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'phone' => ['nullable', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'province_name' => ['required', 'string', 'max:100', Rule::in($this->cities())],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address_detail' => ['required', 'string', 'max:255'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name.required' => 'Họ tên không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.required' => 'Email không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.email' => 'Email không đúng định dạng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.unique' => 'Email này đã được sử dụng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'province_name.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'province_name.in' => 'Tỉnh/Thành phố không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address_detail.required' => 'Địa chỉ chi tiết không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.required' => 'Mật khẩu không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = null;

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
            $user = DB::transaction(function () use ($data): User {
                // Luong: Tao ban ghi moi tu du lieu da chuan bi.
                $newUser = User::create([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'full_name' => $data['full_name'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'email' => $data['email'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'phone' => $data['phone'] ?? null,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'password_hash' => Hash::make($data['password']),
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'provider' => 'LOCAL',
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'status' => 'ACTIVE',
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'email_verified_at' => null,
                ]);

                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                $customerRoleId = DB::table('roles')->where('code', 'USER')->value('id');
                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($customerRoleId) {
                    // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                    DB::table('user_roles')->insert([
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'user_id' => $newUser->id,
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'role_id' => $customerRoleId,
                    ]);
                }

                // Luong: Tao ban ghi moi tu du lieu da chuan bi.
                UserAddress::create([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'user_id' => $newUser->id,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'recipient_name' => $newUser->full_name,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'phone' => $data['phone'] ?? null,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'province_name' => $data['province_name'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'address_detail' => $data['address_detail'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'is_default' => true,
                ]);

                // Luong: Tra ve ket qua cuoi cung cua ham.
                return $newUser;
            });

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->sendEmailVerificationLink($user);
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (\Throwable $exception) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($user && ! $user->email_verified_at) {
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('user_roles')->where('user_id', $user->id)->delete();
                // Luong: Xoa ban ghi phu hop voi dieu kien xu ly.
                UserAddress::where('user_id', $user->id)->delete();
                // Luong: Xoa ban ghi phu hop voi dieu kien xu ly.
                $user->delete();
            }

            // Luong: Ghi log de theo doi va chan doan qua trinh xu ly.
            Log::error('Registration verification email could not be sent.', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email' => $data['email'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'message' => $exception->getMessage(),
            ]);

            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput($request->except('password', 'password_confirmation'))
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['email' => 'Chưa gửi được email xác thực. Vui lòng kiểm tra cấu hình SMTP Gmail rồi thử lại.']);
        }

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('login')->with('status', 'Đăng ký thành công. Vui lòng kiểm tra Gmail và bấm link xác thực để kích hoạt tài khoản.');
    }

    /**
     * Xác thực email và kích hoạt tài khoản.
     */
    public function verifyEmail(Request $request, User $user, string $hash): RedirectResponse
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! hash_equals($hash, sha1($user->email))) {
            // Luong: Dung request va tra ve trang loi tuong ung.
            abort(403);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $user->email_verified_at) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // Luong: Dang nhap nguoi dung vao phien hien tai.
        Auth::login($user);
        // Luong: Cap nhat trang thai session sau thao tac xac thuc.
        $request->session()->regenerate();

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.index')->with('success', 'Xác thực email thành công. Tài khoản của bạn đã được kích hoạt.');
    }

    /**
     * Hiển thị form quên mật khẩu.
     */
    public function showForgotPassword(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('auth.forgot-password');
    }

    /**
     * Gửi link đặt lại mật khẩu qua email.
     */
    public function sendResetPasswordLink(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => ['required', 'email', 'max:255'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.required' => 'Email không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.email' => 'Email không đúng định dạng.',
        ]);

        // Tìm user theo email vừa nhập.
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $user = User::where('email', $data['email'])->first();

        // Nếu user không tồn tại thì bỏ qua, cuối hàm vẫn trả thông báo chung.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($user) {
            // Tạo token ngẫu nhiên dài 72 ký tự để đưa vào link khôi phục mật khẩu.
            // Luong: Gan ket qua xu ly vao bien $token.
            $token = Str::random(72);

            // Đánh dấu các token cũ chưa dùng của user này là đã dùng, để chỉ link mới nhất còn hiệu lực.
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            DB::table('password_reset_tokens')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('user_id', $user->id)
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->whereNull('used_at')
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                ->update(['used_at' => now()]);

            // Lưu token vào database ở dạng hash sha256.
            // Không lưu token thật để nếu database lộ thì người khác không dùng token được.
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            DB::table('password_reset_tokens')->insert([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'user_id' => $user->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'token_hash' => hash('sha256', $token),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'expires_at' => now()->addMinutes(60),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'used_at' => null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => now(),
            ]);

            // Tạo URL đặt lại mật khẩu, gửi token thật qua email cho người dùng.
            // Luong: Gan ket qua xu ly vao bien $url.
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

            // Luong: Bat dau khoi xu ly co the phat sinh loi.
            try {
                // Mail::raw() gửi email dạng text đơn giản, không dùng template Blade.
                // Luong: Gui email dang text theo noi dung da tao.
                Mail::raw(
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    "Xin chào {$user->full_name},\n\nBạn vừa yêu cầu khôi phục mật khẩu.\nNhấn vào liên kết sau để đặt mật khẩu mới:\n{$url}\n\nLiên kết có hiệu lực trong 60 phút. Nếu bạn không yêu cầu, hãy bỏ qua email này.",
                    // Luong: Dinh nghia callback ngan gon cho thao tac hien tai.
                    fn ($message) => $message
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->to($user->email)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->subject('Khôi phục mật khẩu ' . config('app.name'))
                );
            // Luong: Bat va xu ly loi phat sinh trong khoi try.
            } catch (\Throwable $exception) {
                // Luong: Ghi log de theo doi va chan doan qua trinh xu ly.
                Log::error('Password reset email could not be sent.', [
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'email' => $user->email,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'reset_url' => $url,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'message' => $exception->getMessage(),
                ]);

                // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
                return back()
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->withInput($request->only('email'))
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->withErrors(['email' => 'Chưa gửi được email. Vui lòng kiểm tra cấu hình SMTP Gmail trong file .env.']);
            }
        }

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('status', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết khôi phục mật khẩu.');
    }

    /**
     * Hiển thị form đặt lại mật khẩu khi token hợp lệ.
     */
    public function showResetPassword(Request $request, string $token): View|RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $email.
        $email = (string) $request->query('email', '');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->validResetToken($email, $token)) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('password.request')->withErrors([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email' => 'Liên kết khôi phục mật khẩu không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('auth.reset-password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $email,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'token' => $token,
        ]);
    }

    /**
     * Cập nhật mật khẩu mới từ link khôi phục.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => ['required', 'email', 'max:255'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'token' => ['required', 'string'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.required' => 'Email không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email.email' => 'Email không đúng định dạng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'token.required' => 'Thiếu mã khôi phục mật khẩu.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.required' => 'Mật khẩu mới không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.confirmed' => 'Xác nhận mật khẩu không trùng khớp.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $resetRow.
        $resetRow = $this->validResetToken($data['email'], $data['token']);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $resetRow) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email' => 'Liên kết khôi phục mật khẩu không hợp lệ hoặc đã hết hạn.',
            ])->withInput($request->only('email'));
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $user = User::where('email', $data['email'])->firstOrFail();

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function () use ($user, $data, $resetRow): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $user->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_hash' => Hash::make($data['password']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'failed_login_count' => 0,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'locked_until' => null,
            ])->save();

            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            DB::table('password_reset_tokens')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('id', $resetRow->id)
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                ->update(['used_at' => now()]);
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('login')->with('success', 'Đổi mật khẩu thành công. Bạn có thể đăng nhập bằng mật khẩu mới.');
    }

    /**
     * Đăng xuất người dùng khỏi hệ thống.
     */
    public function logout(Request $request): RedirectResponse
    {
        // Luong: Dang xuat nguoi dung khoi phien hien tai.
        Auth::logout();
        // Luong: Cap nhat trang thai session sau thao tac xac thuc.
        $request->session()->invalidate();
        // Luong: Cap nhat trang thai session sau thao tac xac thuc.
        $request->session()->regenerateToken();

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('home');
    }

    /**
     * Kiểm tra mật khẩu có khớp với hash không.
     */
    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            return Hash::check($password, $hash);
        } catch (\RuntimeException) {
            return password_verify($password, $hash);
        }
    }

    /**
     * Gửi link xác thực email cho người dùng.
     */
    private function sendEmailVerificationLink(User $user): void
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'user' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        Mail::raw(
            "Xin chào {$user->full_name},\n\nBạn vừa đăng ký tài khoản tại " . config('app.name') . ".\nNhấn vào liên kết sau để xác thực email và kích hoạt tài khoản:\n{$url}\n\nLiên kết có hiệu lực trong 60 phút. Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.",
            fn ($message) => $message
                ->to($user->email)
                ->subject('Xác thực email đăng ký ' . config('app.name'))
        );
    }

    // Danh sách tỉnh/thành cho form đăng ký và địa chỉ mặc định.
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

    /**
     * Kiểm tra token đặt lại mật khẩu còn hợp lệ.
     */
    private function validResetToken(string $email, string $token): ?object
    {
        // Thiếu email hoặc token thì chắc chắn không hợp lệ.
        if ($email === '' || $token === '') {
            return null;
        }

        // Tìm user theo email để lấy user_id.
        $user = User::where('email', $email)->first();

        // Không có user thì token không thể hợp lệ.
        if (! $user) {
            return null;
        }

        // Tìm token theo user_id, token_hash, chưa dùng và expires_at còn lớn hơn thời điểm hiện tại.
        return DB::table('password_reset_tokens')
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }
}
