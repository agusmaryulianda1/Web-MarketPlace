<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function create(User $user, array $attributes): Product
    {
        $vendor = $user->vendor;
        abort_unless($vendor, 403);
        $this->ensureActiveCategory($attributes['category_id']);
        $image = $attributes['image'] ?? null;
        unset($attributes['image']);
        $attributes['slug'] = $this->uniqueSlug($attributes['name']);
        $path = null;

        try {
            if ($image instanceof UploadedFile) {
                $path = Storage::disk('product_images')->putFile('products/'.$vendor->id, $image);
            }
            return DB::transaction(function () use ($vendor, $attributes, $path): Product {
                $product = $vendor->products()->create($attributes);
                if ($path) {
                    $product->images()->create(['path' => $path, 'is_primary' => true]);
                }
                return $product->load('images');
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('product_images')->delete($path);
            throw $exception;
        }
    }

    public function update(User $user, Product $product, array $attributes): Product
    {
        abort_unless($user->vendor?->id === $product->vendor_id, 403);
        $this->ensureActiveCategory($attributes['category_id']);
        $image = $attributes['image'] ?? null;
        unset($attributes['image']);

        if ($attributes['name'] !== $product->name) {
            $attributes['slug'] = $this->uniqueSlug($attributes['name'], $product);
        }

        $oldPath = $product->images()->where('is_primary', true)->value('path');
        $newPath = null;
        try {
            if ($image instanceof UploadedFile) {
                $newPath = Storage::disk('product_images')->putFile('products/'.$product->vendor_id, $image);
            }
            DB::transaction(function () use ($product, $attributes, $newPath): void {
                $product->update($attributes);
                if ($newPath) {
                    $product->images()->update(['is_primary' => false]);
                    $product->images()->create(['path' => $newPath, 'is_primary' => true]);
                }
            });
            if ($newPath && $oldPath) Storage::disk('product_images')->delete($oldPath);
            return $product->refresh()->load('images');
        } catch (\Throwable $exception) {
            if ($newPath) Storage::disk('product_images')->delete($newPath);
            throw $exception;
        }
    }

    public function delete(User $user, Product $product): void
    {
        abort_unless($user->vendor?->id === $product->vendor_id, 403);
        if ($product->orderItems()->exists() || $product->cartItems()->exists()) {
            throw ValidationException::withMessages(['product' => 'Produk yang masih digunakan dalam transaksi atau keranjang pembeli tidak dapat dihapus. Nonaktifkan produk untuk menghentikan penjualan.']);
        }
        $paths = $product->images()->pluck('path')->all();
        DB::transaction(function () use ($product): void {
            $product->images()->delete();
            $product->delete();
        });
        foreach ($paths as $path) Storage::disk('product_images')->delete($path);
    }

    public function changeStatus(User $user, Product $product, string $status): Product
    {
        abort_unless($user->role === 'admin' || $user->vendor?->id === $product->vendor_id, 403);
        $product->update(['status' => $status]);

        return $product->refresh();
    }

    public function updateStock(User $user, Product $product, int $stock): Product
    {
        abort_unless($user->vendor?->id === $product->vendor_id, 403);
        $product->update(['stock' => $stock]);

        return $product->refresh();
    }

    private function ensureActiveCategory(int $categoryId): void
    {
        if (! \App\Models\Category::whereKey($categoryId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Selected category is inactive or invalid.']);
        }
    }

    private function uniqueSlug(string $name, ?Product $ignore = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)->when($ignore, fn ($query) => $query->whereKey('!=', $ignore->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}