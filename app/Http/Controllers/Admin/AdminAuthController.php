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

    /**
     * Hiển thị form đăng nhập admin.
     */
    public function showLogin(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.auth.login');
    }

    /**
     * Kiểm tra và đăng nhập admin.
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
            return back()->withErrors(['email' => 'Sai email hoặc mật khẩu.'])->onlyInput('email');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($user->status !== 'ACTIVE' || ! $this->canAccessAdmin($user->id)) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['email' => 'Tài khoản này không có quyền vào admin.'])->onlyInput('email');
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
        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Đăng xuất khỏi trang admin.
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
        return redirect()->route('admin.login');
    }

    /**
     * Kiểm tra user có quyền vào admin không.
     */
    private function canAccessAdmin(int $userId): bool
    {
        $userRoles = Cache::remember("users.{$userId}.role_codes", now()->addMinutes(5), fn () => DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->pluck('roles.code')
            ->all());

        return count(array_intersect(self::ADMIN_AREA_ROLES, $userRoles)) > 0;
    }

    /**
     * Kiểm tra mật khẩu admin có đúng không.
     */
    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            return Hash::check($password, $hash);
        } catch (\RuntimeException) {
            return password_verify($password, $hash);
        }
    }
}
