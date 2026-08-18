<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    private const ADMIN_AREA_ROLES = ['ADMIN', 'STAFF'];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Auth::check()) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('admin.login');
        }

        // Luong: Gan ket qua xu ly vao bien $userId.
        $userId = (int) Auth::id();
        // Luong: Gan ket qua xu ly vao bien $allowedRoles.
        $allowedRoles = $roles ?: self::ADMIN_AREA_ROLES;

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (Auth::user()?->status !== 'ACTIVE') {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->logout($request);

            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->route('admin.login')
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['email' => 'Tài khoản này không có quyền vào admin.']);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->hasAnyRole($userId, $allowedRoles)) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($roles && $this->hasAnyRole($userId, self::ADMIN_AREA_ROLES)) {
                // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
                return redirect()
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->route('admin.dashboard')
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->with('error', 'Tài khoản này không có quyền truy cập chức năng này.');
            }

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->logout($request);

            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->route('admin.login')
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['email' => 'Tài khoản này không có quyền vào admin.']);
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $next($request);
    }

    private function hasAnyRole(int $userId, array $roles): bool
    {
        // TTL để 60 giây thay vì 5 phút: đây là dữ liệu PHÂN QUYỀN, nên khoảng thời
        // gian cache chính là khoảng thời gian một tài khoản đã bị gỡ quyền vẫn vào
        // được khu vực admin. CustomerAdminController::syncRole/updateStatus có gọi
        // Cache::forget, nhưng mọi đường gỡ quyền khác (sửa thẳng DB, seeder, code
        // sau này) thì không, nên vẫn cần giới hạn cửa sổ này cho nhỏ.
        // Muốn hết hẳn rủi ro thì bỏ cache — mỗi request admin chỉ tốn một truy vấn
        // join có index, mà lưu lượng khu vực admin vốn thấp.
        $userRoles = Cache::remember("users.{$userId}.role_codes", now()->addSeconds(60), fn () => DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->pluck('roles.code')
            ->all());

        return count(array_intersect($roles, $userRoles)) > 0;
    }

    private function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
