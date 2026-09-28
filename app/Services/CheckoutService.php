<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderVendorGroup;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function create(User $user, int $addressId, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($user, $addressId, $paymentMethod): Order {
            $cart = $user->cart()->first();
            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $items = $cart->items()->orderBy('product_id')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $address = Address::query()->whereKey($addressId)->where('user_id', $user->id)->first();
            if (! $address) {
                throw ValidationException::withMessages(['address_id' => 'Selected address is invalid.']);
            }

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $orderItems = [];
            $totalCents = 0;
            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (! $product || $product->status !== 'active' || $product->category?->status !== 'active' || $product->vendor?->status !== 'active') {
                    throw ValidationException::withMessages(['cart' => 'One or more products are unavailable.']);
                }
                if ($item->quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'One or more products no longer have enough stock.']);
                }

                $price = $this->cents($product->price);
                $subtotal = $price * $item->quantity;
                $totalCents += $subtotal;
                $orderItems[] = [$product, $item->quantity, $price, $subtotal];
            }

            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'total_amount' => $this->money($totalCents),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'shipping_address' => $this->shippingAddress($address),
            ]);

            foreach ($orderItems as [$product, $quantity, $price, $subtotal]) {
                $group = OrderVendorGroup::firstOrCreate(
                    ['order_id' => $order->id, 'vendor_id' => $product->vendor_id],
                    ['status' => 'pending', 'subtotal' => '0.00'],
                );
                $group->increment('subtotal', $this->money($subtotal));
                $order->items()->create([
                    'product_id' => $product->id,
                    'vendor_id' => $product->vendor_id,
                    'order_vendor_group_id' => $group->id,
                    'product_name' => $product->name,
                    'price' => $this->money($price),
                    'quantity' => $quantity,
                    'subtotal' => $this->money($subtotal),
                ]);
                $product->decrement('stock', $quantity);
            }

            $cart->items()->delete();
            return $order;
        });
    }

    private function orderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());
        return $number;
    }

    private function shippingAddress(Address $address): string
    {
        return implode(', ', array_filter([
            $address->recipient_name,
            $address->phone,
            $address->address,
            $address->city,
            $address->province,
            $address->postal_code,
        ]));
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