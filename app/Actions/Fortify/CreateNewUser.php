<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

/**
 * LƯU Ý: các route của Fortify/Jetstream đang bị tắt bằng Fortify::ignoreRoutes()
 * và Jetstream::ignoreRoutes() (xem FortifyServiceProvider/JetstreamServiceProvider),
 * luồng đăng ký/đăng nhập thật nằm ở AuthController với các route tiếng Việt.
 *
 * Class này vẫn được giữ vì provider có bind tới nó. Nó từng ghi vào cột
 * 'name'/'password' - hai cột KHÔNG tồn tại trong schema (schema dùng full_name và
 * password_hash) và cũng không nằm trong $fillable, nên nếu ai đó bật lại route
 * Fortify thì sẽ tạo ra user không có mật khẩu. Đã sửa lại cho khớp schema.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Validator::make($input, [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'name' => ['required', 'string', 'max:255'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => $this->passwordRules(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return User::create([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'full_name' => $input['name'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $input['email'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password_hash' => Hash::make($input['password']),
            'status' => 'ACTIVE',
        ]);
    }
}
