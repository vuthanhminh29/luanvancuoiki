<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Validator::make($input, [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'name' => ['required', 'string', 'max:255'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (isset($input['photo'])) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $user->updateProfilePhoto($input['photo']);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($input['email'] !== $user->email &&
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $user instanceof MustVerifyEmail) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->updateVerifiedUser($user, $input);
        // Luong: Xu ly truong hop con lai cua nhanh dieu kien.
        } else {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $user->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => $input['name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
