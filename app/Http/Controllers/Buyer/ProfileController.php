<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = request()->user();

        return Inertia::render('Buyer/Profile', [
            'profile' => [
                ...$user->only(['id', 'name', 'email', 'phone']),
                'avatar_url' => $user->avatar ? Storage::disk('product_images')->url($user->avatar) : null,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->safe()->except(['avatar']);
        $oldAvatar = $user->avatar;
        $newAvatar = null;

        try {
            if ($request->hasFile('avatar')) {
                $newAvatar = $request->file('avatar')->store('avatars', 'product_images');
                $validated['avatar'] = $newAvatar;
            }

            $user->update($validated);

            if ($newAvatar && $oldAvatar) {
                Storage::disk('product_images')->delete($oldAvatar);
            }
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('product_images')->delete($newAvatar);
            }

            throw $exception;
        }

        return redirect()->route('buyer.profile.show')->with('success', 'Profile updated.');
    }
}