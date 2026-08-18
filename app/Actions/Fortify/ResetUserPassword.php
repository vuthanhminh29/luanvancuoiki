<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Validator::make($input, [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => $this->passwordRules(),
        ])->validate();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $user->forceFill([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password_hash' => Hash::make($input['password']),
        ])->save();
    }
}
