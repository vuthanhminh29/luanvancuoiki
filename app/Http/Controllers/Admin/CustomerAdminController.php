<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerAdminController extends Controller
{
    private const CUSTOMER_ROLE = 'USER';
    private const STAFF_ROLES = ['ADMIN', 'STAFF'];

    public function index(Request $request): View
    {
        $roles = DB::table('roles')->orderBy('is_system')->orderBy('name')->get();
        $keyword = trim((string) $request->query('keyword'));
        $role = trim((string) $request->query('role'));
        $status = trim((string) $request->query('status'));
        if (! in_array($status, ['ACTIVE', 'LOCKED'], true)) {
            $status = '';
        }

        $usersQuery = User::query()
            ->select('users.*')
            ->withCount('orders')
            ->withSum(['orders as delivered_total' => fn ($query) => $query->where('status', 'DELIVERED')], 'total_amount')
            ->latest('users.id');

        if ($keyword !== '') {
            $usersQuery->where(function ($query) use ($keyword) {
                $query->where('full_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('id', (int) $keyword);
            });
        }

        if ($status !== '') {
            $usersQuery->where('status', $status);
        }

        if ($role !== '') {
            $usersQuery->whereExists(function ($query) use ($role) {
                $query->selectRaw('1')
                    ->from('user_roles')
                    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->whereColumn('user_roles.user_id', 'users.id')
                    ->where('roles.code', $role);
            });
        }

        $users = $usersQuery->paginate(20)->withQueryString();
        $roleMap = $this->roleMap($users->getCollection()->pluck('id')->all());

        $summary = [
            'total' => User::count(),
            'active' => User::where('status', 'ACTIVE')->count(),
            'customers' => $this->countUsersByRole(self::CUSTOMER_ROLE),
            'staff' => $this->countStaffUsers(),
            'locked' => User::where('status', 'LOCKED')->count(),
        ];

        return view('admin.customers.index', compact(
            'users',
            'roles',
            'roleMap',
            'summary',
            'keyword',
            'role',
            'status'
        ));
    }

    public function create(): View
    {
        return view('admin.shared.form', [
            'title' => 'Thêm tài khoản',
            'subtitle' => 'Tạo tài khoản khách hàng mới',
            'action' => route('admin.customers.store'),
            'submitLabel' => 'Lưu tài khoản',
            'formStyle' => 'ma',
            'fields' => $this->customerFields(),
            'backRoute' => route('admin.customers.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCustomer($request);
        $roleCode = $data['role_code'];
        unset($data['role_code']);

        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);

        $user = User::create($data);
        $this->syncRole($user, $roleCode);

        return redirect()->route('admin.customers.index')->with('success', 'Đã thêm tài khoản.');
    }

    public function edit(User $user): View
    {
        return view('admin.shared.form', [
            'title' => 'Cập nhật tài khoản',
            'subtitle' => $user->full_name,
            'action' => route('admin.customers.update', $user),
            'method' => 'PUT',
            'submitLabel' => 'Lưu tài khoản',
            'formStyle' => 'ma',
            'fields' => $this->customerFields($user),
            'backRoute' => route('admin.customers.index'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateCustomer($request, $user);
        $roleCode = $data['role_code'];
        unset($data['role_code']);

        if (! empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        }

        unset($data['password']);
        $user->update($data);
        $this->syncRole($user, $roleCode);

        return redirect()->route('admin.customers.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:ACTIVE,LOCKED'],
        ]);

        $user->update(['status' => $data['status']]);
        Cache::forget("users.{$user->id}.role_codes");

        return back()->with('success', 'Đã cập nhật trạng thái thành viên.');
    }

    private function validateCustomer(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'regex:/@gmail\\.com$/', Rule::unique('users', 'email')->ignore($user ? $user->id : null)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^0[0-9]{9}$/'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'gender' => ['nullable', 'in:MALE,FEMALE,OTHER'],
            'date_of_birth' => ['nullable', 'date'],
            'status' => ['required', 'in:ACTIVE,LOCKED'],
            'role_code' => ['required', 'exists:roles,code'],
        ]);
    }

    private function customerFields(?User $user = null): array
    {
        return [
            ['name' => 'full_name', 'label' => 'Họ tên', 'required' => true, 'value' => $user ? $user->full_name : null],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'value' => $user ? $user->email : null],
            ['name' => 'phone', 'label' => 'Số điện thoại', 'value' => $user ? $user->phone : null],
            ['name' => 'password', 'label' => $user ? 'Mật khẩu mới' : 'Mật khẩu', 'type' => 'password', 'required' => ! $user],
            ['name' => 'gender', 'label' => 'Giới tính', 'type' => 'select', 'value' => ($user && $user->gender) ? $user->gender : 'OTHER', 'options' => ['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'OTHER' => 'Khác']],
            ['name' => 'date_of_birth', 'label' => 'Ngày sinh', 'type' => 'date', 'value' => ($user && $user->date_of_birth) ? $user->date_of_birth->format('Y-m-d') : null],
            ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'required' => true, 'value' => ($user && $user->status) ? $user->status : 'ACTIVE', 'options' => ['ACTIVE' => 'Hoạt động', 'LOCKED' => 'Bị khóa']],
            ['name' => 'role_code', 'label' => 'Vai trò', 'type' => 'select', 'required' => true, 'value' => $this->currentRoleCode($user), 'options' => $this->roleOptions()],
        ];
    }

    private function roleMap(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('user_roles.user_id', $userIds)
            ->select('user_roles.user_id', 'roles.code', 'roles.name')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($items) => $items->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])->all())
            ->all();
    }

    private function countUsersByRole(string $roleCode): int
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('roles.code', $roleCode)
            ->distinct('user_roles.user_id')
            ->count('user_roles.user_id');
    }

    private function countStaffUsers(): int
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.code', self::STAFF_ROLES)
            ->distinct('user_roles.user_id')
            ->count('user_roles.user_id');
    }

    private function roleOptions(): array
    {
        return DB::table('roles')
            ->orderBy('id')
            ->pluck('name', 'code')
            ->all();
    }

    private function currentRoleCode(?User $user): string
    {
        if (! $user) {
            return self::CUSTOMER_ROLE;
        }

        return (string) DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->orderBy('roles.id')
            ->value('roles.code') ?: self::CUSTOMER_ROLE;
    }

    private function syncRole(User $user, string $roleCode): void
    {
        $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

        if (! $roleId) {
            return;
        }

        DB::table('user_roles')->where('user_id', $user->id)->delete();
        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);

        Cache::forget("users.{$user->id}.role_codes");
    }
}
