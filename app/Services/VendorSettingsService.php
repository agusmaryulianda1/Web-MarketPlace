<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorSettingsService
{
    public function update(User $user, array $attributes): User
    {
        $avatar = $attributes['avatar'] ?? null;
        unset($attributes['avatar']);

        $attributes = array_intersect_key($attributes, array_flip(['name', 'email', 'phone']));
        $oldAvatar = $user->avatar;
        $newAvatar = null;

        try {
            if ($avatar instanceof UploadedFile) {
                $newAvatar = $avatar->store('users/'.$user->id.'/avatar', 'product_images');
            }

            DB::transaction(function () use ($user, $attributes, $newAvatar): void {
                $user->update([
                    ...$attributes,
                    ...($newAvatar ? ['avatar' => $newAvatar] : []),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('product_images')->delete($newAvatar);
            }

            throw $exception;
        }

        if ($newAvatar && $oldAvatar) {
            Storage::disk('product_images')->delete($oldAvatar);
        }

        return $user->refresh();
    }
}