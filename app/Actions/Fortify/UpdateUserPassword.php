<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Validator::make($input, [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'current_password' => ['required', 'string', 'current_password:web'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => $this->passwordRules(),
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $user->forceFill([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
