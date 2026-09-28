<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorSettingsRequest;
use App\Http\Requests\Vendor\UpdateVendorPasswordRequest;
use App\Models\User;
use App\Services\VendorSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Vendor/Settings', [
            'settings' => $this->settingsData($request->user()),
        ]);
    }

    public function update(
        UpdateVendorSettingsRequest $request,
        VendorSettingsService $settingsService,
    ): RedirectResponse {
        $settingsService->update($request->user(), $request->validated());

        return redirect()->route('vendor.settings.show')->with('success', 'Pengaturan akun berhasil diperbarui.');
    }

    public function updatePassword(UpdateVendorPasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('new_password')]);

        return redirect()->route('vendor.settings.show')->with('success', 'Password berhasil diperbarui.');
    }

    private function settingsData(User $user): array
    {
        return [
            ...$user->only(['id', 'name', 'email', 'phone']),
            'avatar_url' => $user->avatar ? Storage::disk('product_images')->url($user->avatar) : null,
        ];
    }
}