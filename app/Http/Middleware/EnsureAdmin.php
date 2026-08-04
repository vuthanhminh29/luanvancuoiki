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
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $userId = (int) Auth::id();
        $allowedRoles = $roles ?: self::ADMIN_AREA_ROLES;

        if (Auth::user()?->status !== 'ACTIVE') {
            $this->logout($request);

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Tài khoản này không có quyền vào admin.']);
        }

        if (! $this->hasAnyRole($userId, $allowedRoles)) {
            if ($roles && $this->hasAnyRole($userId, self::ADMIN_AREA_ROLES)) {
                return redirect()
                    ->route('admin.dashboard')
                    ->with('error', 'Tài khoản này không có quyền truy cập chức năng này.');
            }

            $this->logout($request);

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Tài khoản này không có quyền vào admin.']);
        }

        return $next($request);
    }

    private function hasAnyRole(int $userId, array $roles): bool
    {
        $userRoles = Cache::remember("users.{$userId}.role_codes", now()->addMinutes(5), fn () => DB::table('user_roles')
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
