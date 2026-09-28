<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\Store\CreateStoreRequest;
use App\Http\Requests\Vendor\Store\UpdateStoreRequest;
use App\Http\Requests\Vendor\Store\UpdateStoreStatusRequest;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function show(): Response
    {
        $store = request()->user()->vendor?->store;

        if ($store) {
            Gate::authorize('view', $store);
        }

        return Inertia::render('Vendor/Store', [
            'store' => $store ? $this->storeData($store) : null,
        ]);
    }

    public function store(CreateStoreRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        Gate::authorize('create', Store::class);

        if (! $vendor) {
            abort(403);
        }

        if ($vendor->store) {
            throw ValidationException::withMessages(['store' => 'Toko Anda sudah tersedia.']);
        }

        $validated = $request->validated();
        $logo = $validated['logo'] ?? null;
        $banner = $validated['banner'] ?? null;
        unset($validated['logo'], $validated['banner']);
        $logoPath = $this->upload($logo, $vendor->id, 'logo');
        $bannerPath = $this->upload($banner, $vendor->id, 'banner');

        try {
            DB::transaction(function () use ($vendor, $validated, $logoPath, $bannerPath): void {
                $vendor->store()->create([...$validated, 'logo' => $logoPath, 'banner' => $bannerPath, 'is_open' => false]);
            });
        } catch (\Throwable $exception) {
            $this->deleteFile($logoPath);
            $this->deleteFile($bannerPath);
            throw $exception;
        }

        return redirect()->route('vendor.store.show')->with('success', 'Toko berhasil dibuat.');
    }

    public function update(UpdateStoreRequest $request): RedirectResponse
    {
        $store = $request->user()->vendor?->store;
        abort_unless($store, 404);
        Gate::authorize('update', $store);

        $validated = $request->validated();
        $logo = $validated['logo'] ?? null;
        $banner = $validated['banner'] ?? null;
        unset($validated['logo'], $validated['banner']);
        $oldLogoPath = $store->logo;
        $oldBannerPath = $store->banner;
        $vendorId = $request->user()->vendor->id;
        $newLogoPath = $this->upload($logo, $vendorId, 'logo');
        $newBannerPath = $this->upload($banner, $vendorId, 'banner');

        try {
            DB::transaction(function () use ($store, $validated, $newLogoPath, $newBannerPath): void {
                $store->update([
                    ...$validated,
                    ...($newLogoPath ? ['logo' => $newLogoPath] : []),
                    ...($newBannerPath ? ['banner' => $newBannerPath] : []),
                ]);
            });
        } catch (\Throwable $exception) {
            $this->deleteFile($newLogoPath);
            $this->deleteFile($newBannerPath);
            throw $exception;
        }

        if ($newLogoPath && $oldLogoPath) $this->deleteFile($oldLogoPath);
        if ($newBannerPath && $oldBannerPath) $this->deleteFile($oldBannerPath);

        return redirect()->route('vendor.store.show')->with('success', 'Toko berhasil diperbarui.');
    }

    public function updateStatus(UpdateStoreStatusRequest $request): RedirectResponse
    {
        $store = $request->user()->vendor?->store;
        abort_unless($store, 404);
        Gate::authorize('update', $store);

        $store->update(['is_open' => $request->validated('is_open')]);

        return redirect()->route('vendor.store.show')->with('success', 'Status operasional berhasil diperbarui.');
    }

    private function upload(?UploadedFile $file, int $vendorId, string $type): ?string
    {
        return $file?->store('stores/'.$vendorId.'/'.$type, 'product_images');
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('product_images')->delete($path);
        }
    }

    private function storeData(Store $store): array
    {
        return [
            ...$store->only(['id', 'name', 'slug', 'description', 'phone', 'is_open']),
            'logo_url' => $store->logo ? Storage::disk('product_images')->url($store->logo) : null,
            'banner_url' => $store->banner ? Storage::disk('product_images')->url($store->banner) : null,
        ];
    }
}