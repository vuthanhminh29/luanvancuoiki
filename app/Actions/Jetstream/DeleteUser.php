<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $user->deleteProfilePhoto();
        // Luong: Xoa ban ghi phu hop voi dieu kien xu ly.
        $user->tokens->each->delete();
        // Luong: Xoa ban ghi phu hop voi dieu kien xu ly.
        $user->delete();
    }
}
