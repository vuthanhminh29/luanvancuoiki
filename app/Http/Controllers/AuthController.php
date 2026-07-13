<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $this->passwordMatches($credentials['password'], (string) $user->password_hash)) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        if ($user->status !== 'ACTIVE') {
            return back()->withErrors(['email' => 'Tài khoản đã bị khóa.'])->onlyInput('email');
        }

        if (! $user->email_verified_at) {
            return back()->withErrors(['email' => 'Vui lòng kiểm tra Gmail và bấm link xác thực trước khi đăng nhập.'])->onlyInput('email');
        }

        if (Hash::needsRehash((string) $user->password_hash)) {
            $user->forceFill([
                'password_hash' => Hash::make($credentials['password']),
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ]);

        $user = null;

        try {
            $user = DB::transaction(function () use ($data): User {
                $newUser = User::create([
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password_hash' => Hash::make($data['password']),
                    'provider' => 'LOCAL',
                    'status' => 'ACTIVE',
                    'email_verified_at' => null,
                ]);

                $customerRoleId = DB::table('roles')->where('code', 'USER')->value('id');
                if ($customerRoleId) {
                    DB::table('user_roles')->insert([
                        'user_id' => $newUser->id,
                        'role_id' => $customerRoleId,
                    ]);
                }

                return $newUser;
            });

            $this->sendEmailVerificationLink($user);
        } catch (\Throwable $exception) {
            if ($user && ! $user->email_verified_at) {
                DB::table('user_roles')->where('user_id', $user->id)->delete();
                $user->delete();
            }

            Log::error('Registration verification email could not be sent.', [
                'email' => $data['email'],
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Chưa gửi được email xác thực. Vui lòng kiểm tra cấu hình SMTP Gmail rồi thử lại.']);
        }

        return redirect()->route('login')->with('status', 'Đăng ký thành công. Vui lòng kiểm tra Gmail và bấm link xác thực để kích hoạt tài khoản.');
    }

    public function verifyEmail(Request $request, User $user, string $hash): RedirectResponse
    {
        if (! hash_equals($hash, sha1($user->email))) {
            abort(403);
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('success', 'Xác thực email thành công. Tài khoản của bạn đã được kích hoạt.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetPasswordLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $token = Str::random(72);

            DB::table('password_reset_tokens')
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            DB::table('password_reset_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(60),
                'used_at' => null,
                'created_at' => now(),
            ]);

            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

            try {
                Mail::raw(
                    "Xin chào {$user->full_name},\n\nBạn vừa yêu cầu khôi phục mật khẩu.\nNhấn vào liên kết sau để đặt mật khẩu mới:\n{$url}\n\nLiên kết có hiệu lực trong 60 phút. Nếu bạn không yêu cầu, hãy bỏ qua email này.",
                    fn ($message) => $message
                        ->to($user->email)
                        ->subject('Khôi phục mật khẩu ' . config('app.name'))
                );
            } catch (\Throwable $exception) {
                Log::error('Password reset email could not be sent.', [
                    'email' => $user->email,
                    'reset_url' => $url,
                    'message' => $exception->getMessage(),
                ]);

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Chưa gửi được email. Vui lòng kiểm tra cấu hình SMTP Gmail trong file .env.']);
            }
        }

        return back()->with('status', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết khôi phục mật khẩu.');
    }

    public function showResetPassword(Request $request, string $token): View|RedirectResponse
    {
        $email = (string) $request->query('email', '');

        if (! $this->validResetToken($email, $token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Liên kết khôi phục mật khẩu không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        return view('auth.reset-password', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'token.required' => 'Thiếu mã khôi phục mật khẩu.',
            'password.required' => 'Mật khẩu mới không được để trống.',
            'password.confirmed' => 'Xác nhận mật khẩu không trùng khớp.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);

        $resetRow = $this->validResetToken($data['email'], $data['token']);

        if (! $resetRow) {
            return back()->withErrors([
                'email' => 'Liên kết khôi phục mật khẩu không hợp lệ hoặc đã hết hạn.',
            ])->withInput($request->only('email'));
        }

        $user = User::where('email', $data['email'])->firstOrFail();

        DB::transaction(function () use ($user, $data, $resetRow): void {
            $user->forceFill([
                'password_hash' => Hash::make($data['password']),
                'failed_login_count' => 0,
                'locked_until' => null,
            ])->save();

            DB::table('password_reset_tokens')
                ->where('id', $resetRow->id)
                ->update(['used_at' => now()]);
        });

        return redirect()->route('login')->with('success', 'Đổi mật khẩu thành công. Bạn có thể đăng nhập bằng mật khẩu mới.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            return Hash::check($password, $hash);
        } catch (\RuntimeException) {
            return password_verify($password, $hash);
        }
    }

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

    private function validResetToken(string $email, string $token): ?object
    {
        if ($email === '' || $token === '') {
            return null;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return null;
        }

        return DB::table('password_reset_tokens')
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }
}
