<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function cart(User $user): Cart
    {
        return $user->cart()->firstOrCreate([]);
    }

    public function add(User $user, int $productId, int $quantity): CartItem
    {
        $product = $this->activeProduct($productId);
        $cart = $this->cart($user);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $finalQuantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $this->ensureStock($product, $finalQuantity);
        $item->quantity = $finalQuantity;
        $item->save();
        return $item->load('product');
    }

    public function update(User $user, CartItem $item, int $quantity): CartItem
    {
        abort_unless($item->cart->user_id === $user->id, 404);
        $product = $this->activeProduct($item->product_id);
        $this->ensureStock($product, $quantity);
        $item->update(['quantity' => $quantity]);
        return $item->refresh()->load('product');
    }

    private function activeProduct(int $id): Product
    {
        $product = Product::query()->whereKey($id)->where('status', 'active')
            ->whereHas('category', fn ($q) => $q->where('status', 'active'))
            ->whereHas('vendor', fn ($q) => $q->where('status', 'active'))->first();
        abort_unless($product, 422, 'Product is unavailable.');
        return $product;
    }

    private function ensureStock(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages(['quantity' => 'Requested quantity exceeds available stock.']);
        }
    }
}