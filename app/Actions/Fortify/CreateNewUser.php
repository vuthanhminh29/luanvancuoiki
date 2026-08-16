<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

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
            'name' => $input['name'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $input['email'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => Hash::make($input['password']),
        ]);
    }
}
