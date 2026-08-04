<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const ADMIN_AREA_ROLES = ['ADMIN', 'STAFF'];

    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $this->passwordMatches($credentials['password'], (string) $user->password_hash)) {
            return back()->withErrors(['email' => 'Sai email hoặc mật khẩu.'])->onlyInput('email');
        }

        if ($user->status !== 'ACTIVE' || ! $this->canAccessAdmin($user->id)) {
            return back()->withErrors(['email' => 'Tài khoản này không có quyền vào admin.'])->onlyInput('email');
        }

        if (Hash::needsRehash((string) $user->password_hash)) {
            $user->forceFill([
                'password_hash' => Hash::make($credentials['password']),
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function canAccessAdmin(int $userId): bool
    {
        $userRoles = Cache::remember("users.{$userId}.role_codes", now()->addMinutes(5), fn () => DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->pluck('roles.code')
            ->all());

        return count(array_intersect(self::ADMIN_AREA_ROLES, $userRoles)) > 0;
    }

    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            return Hash::check($password, $hash);
        } catch (\RuntimeException) {
            return password_verify($password, $hash);
        }
    }
}
