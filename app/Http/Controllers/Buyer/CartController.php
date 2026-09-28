<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\AddCartItemRequest;
use App\Http\Requests\Buyer\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(): Response
    {
        return Inertia::render('Buyer/Cart/Index', ['cart' => $this->data(request()->user())]);
    }

    public function store(AddCartItemRequest $request)
    {
        $this->cartService->add($request->user(), $request->validated('product_id'), $request->validated('quantity'));
        return redirect()->route('buyer.cart.index')->with('success', 'Product added to cart.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $this->cartService->update($request->user(), $cartItem, $request->validated('quantity'));
        return redirect()->route('buyer.cart.index');
    }

    public function destroy(CartItem $cartItem)
    {
        abort_unless($cartItem->cart->user_id === request()->user()->id, 404);
        $cartItem->delete();
        return redirect()->route('buyer.cart.index');
    }

    private function data($user): array
    {
        $cart = $this->cartService->cart($user)->load([
            'items.product.vendor.store',
            'items.product.category',
            'items.product.images',
        ]);

        $items = $cart->items->map(function ($item): array {
            $product = $item->product;
            $primaryImage = $product?->images->firstWhere('is_primary', true) ?? $product?->images->first();
            $price = $product?->price;
            $subtotal = $price === null ? null : $this->money($this->cents($price) * $item->quantity);

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $product?->name,
                'price' => $price,
                'quantity' => $item->quantity,
                'subtotal' => $subtotal,
                'stock' => $product?->stock,
                'status' => $product?->status,
                'vendor' => $product?->vendor?->only(['id', 'status']),
                'store' => $product?->vendor?->store?->only(['id', 'name', 'slug', 'is_open']),
                'images' => $product?->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => Storage::disk('product_images')->url($image->path),
                    'is_primary' => $image->is_primary,
                ])->values()->all() ?? [],
                'image_url' => $primaryImage ? Storage::disk('product_images')->url($primaryImage->path) : null,
                'category' => $product?->category?->only(['id', 'name', 'slug']),
            ];
        })->values()->all();

        $totalCents = $cart->items->sum(fn ($item) => $item->product?->price === null
            ? 0
            : $this->cents($item->product->price) * $item->quantity);

        return ['id' => $cart->id, 'items' => $items, 'total' => $this->money($totalCents)];
    }

    private function cents(string|int|float $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}