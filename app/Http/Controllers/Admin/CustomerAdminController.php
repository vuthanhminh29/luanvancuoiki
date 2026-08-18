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

    /**
     * Hiển thị danh sách khách hàng.
     */
    public function index(Request $request): View
    {
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $roles = DB::table('roles')->orderBy('is_system')->orderBy('name')->get();
        // Luong: Gan ket qua xu ly vao bien $keyword.
        $keyword = trim((string) $request->query('keyword'));
        // Luong: Gan ket qua xu ly vao bien $role.
        $role = trim((string) $request->query('role'));
        // Luong: Gan ket qua xu ly vao bien $status.
        $status = trim((string) $request->query('status'));
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! in_array($status, ['ACTIVE', 'LOCKED'], true)) {
            // Luong: Gan ket qua xu ly vao bien $status.
            $status = '';
        }

        // Luong: Gan ket qua xu ly vao bien $usersQuery.
        $usersQuery = User::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select('users.*')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withCount('orders')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->withSum(['orders as delivered_total' => fn ($query) => $query->where('status', 'DELIVERED')], 'total_amount')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('users.id');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($keyword !== '') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $usersQuery->where(function ($query) use ($keyword) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where('full_name', 'like', "%{$keyword}%")
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('email', 'like', "%{$keyword}%")
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('id', (int) $keyword);
            });
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($status !== '') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $usersQuery->where('status', $status);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($role !== '') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $usersQuery->whereExists(function ($query) use ($role) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $query->selectRaw('1')
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->from('user_roles')
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->whereColumn('user_roles.user_id', 'users.id')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('roles.code', $role);
            });
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $users = $usersQuery->paginate(20)->withQueryString();
        // Luong: Gan ket qua xu ly vao bien $roleMap.
        $roleMap = $this->roleMap($users->getCollection()->pluck('id')->all());

        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'total' => User::count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'active' => User::where('status', 'ACTIVE')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'customers' => $this->countUsersByRole(self::CUSTOMER_ROLE),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'staff' => $this->countStaffUsers(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'locked' => User::where('status', 'LOCKED')->count(),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.customers.index', compact(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'users',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'roles',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'roleMap',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'summary',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'keyword',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'role',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'status'
        ));
    }

    /**
     * Hiển thị form thêm khách hàng.
     */
    public function create(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm tài khoản',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => 'Tạo tài khoản khách hàng mới',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.customers.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu tài khoản',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'ma',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->customerFields(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.customers.index'),
        ]);
    }

    /**
     * Lưu khách hàng mới.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $data.
        $data = $this->validateCustomer($request);
        // Luong: Gan ket qua xu ly vao bien $roleCode.
        $roleCode = $data['role_code'];
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        unset($data['role_code']);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        $data['password_hash'] = Hash::make($data['password']);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        unset($data['password']);

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        $user = User::create($data);
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->syncRole($user, $roleCode);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.customers.index')->with('success', 'Đã thêm tài khoản.');
    }

    /**
     * Hiển thị form sửa khách hàng.
     */
    public function edit(User $user): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật tài khoản',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => $user->full_name,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.customers.update', $user),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu tài khoản',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'ma',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->customerFields($user),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.customers.index'),
        ]);
    }

    /**
     * Cập nhật khách hàng.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $data.
        $data = $this->validateCustomer($request, $user);
        // Luong: Gan ket qua xu ly vao bien $roleCode.
        $roleCode = $data['role_code'];
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        unset($data['role_code']);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! empty($data['password'])) {
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $data['password_hash'] = Hash::make($data['password']);
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        unset($data['password']);
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $user->update($data);
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->syncRole($user, $roleCode);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.customers.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    /**
     * Xử lý updateStatus cho khách hàng.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status' => ['required', 'in:ACTIVE,LOCKED'],
        ]);

        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $user->update(['status' => $data['status']]);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Cache::forget("users.{$user->id}.role_codes");

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã cập nhật trạng thái thành viên.');
    }

    /**
     * Kiểm tra dữ liệu khách hàng.
     */
    private function validateCustomer(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'regex:/@gmail\\.com$/', Rule::unique('users', 'email')->ignore($user ? $user->id : null)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^0[0-9]{9}$/'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'gender' => ['nullable', 'in:MALE,FEMALE,OTHER'],
            'date_of_birth' => ['nullable', 'date'],
            'status' => ['required', 'in:ACTIVE,LOCKED'],
            'role_code' => ['required', 'exists:roles,code'],
        ]);
    }

    /**
     * Lấy dữ liệu mặc định cho form khách hàng.
     */
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

    /**
     * Lấy vai trò của danh sách người dùng.
     */
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

    /**
     * Đếm người dùng theo vai trò.
     */
    private function countUsersByRole(string $roleCode): int
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('roles.code', $roleCode)
            ->distinct('user_roles.user_id')
            ->count('user_roles.user_id');
    }

    /**
     * Đếm số tài khoản nhân sự.
     */
    private function countStaffUsers(): int
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.code', self::STAFF_ROLES)
            ->distinct('user_roles.user_id')
            ->count('user_roles.user_id');
    }

    /**
     * Lấy danh sách vai trò có thể chọn.
     */
    private function roleOptions(): array
    {
        return DB::table('roles')
            ->orderBy('id')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * Lấy vai trò hiện tại của người dùng.
     */
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

    /**
     * Cập nhật vai trò của người dùng.
     */
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
