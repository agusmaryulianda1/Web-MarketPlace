<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderVendorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_checkout_creates_order_reduces_stock_and_clears_cart(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->address($buyer);
        [$product] = $this->product(['price' => '12.50', 'stock' => 4]);
        $buyer->cart()->create()->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($buyer)->post(route('buyer.checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'cod',
        ])->assertRedirect(route('buyer.orders.show', Order::latest('id')->first()));

        $order = Order::latest('id')->firstOrFail();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $buyer->id, 'total_amount' => '25.00', 'payment_status' => 'pending', 'order_status' => 'pending']);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id, 'vendor_id' => $product->vendor_id, 'price' => '12.50', 'quantity' => 2, 'subtotal' => '25.00']);
        $this->assertDatabaseHas('order_vendor_groups', ['order_id' => $order->id, 'vendor_id' => $product->vendor_id, 'status' => 'pending', 'subtotal' => '25.00']);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'order_vendor_group_id' => OrderVendorGroup::where('order_id', $order->id)->value('id')]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_rejects_another_buyers_address_without_mutating_cart_or_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        [$product] = $this->product(['stock' => 3]);
        $buyer->cart()->create()->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        $address = $this->address($otherBuyer);

        $this->actingAs($buyer)->post(route('buyer.checkout.store'), ['address_id' => $address->id, 'payment_method' => 'cod'])->assertSessionHasErrors('address_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_rolls_back_when_stock_is_insufficient(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->address($buyer);
        [$product] = $this->product(['stock' => 1]);
        $buyer->cart()->create()->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($buyer)->post(route('buyer.checkout.store'), ['address_id' => $address->id, 'payment_method' => 'bank_transfer'])->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_guest_vendor_and_admin_cannot_checkout_or_create_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->address($buyer);
        [$product] = $this->product();
        $buyer->cart()->create()->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $payload = ['address_id' => $address->id, 'payment_method' => 'cod'];

        $this->post(route('buyer.checkout.store'), $payload)->assertRedirect(route('login'));

        foreach (['vendor', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('buyer.checkout.store'), $payload)
                ->assertForbidden();
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    private function address(User $user): Address
    {
        return $user->addresses()->create(['recipient_name' => 'Buyer', 'phone' => '123', 'address' => 'Street', 'is_default' => true]);
    }

    private function product(array $attributes = []): array
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $vendorModel = $vendor->vendor()->create(['status' => 'active']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech-'.uniqid(), 'status' => 'active']);
        return [$vendorModel->products()->create(array_merge(['category_id' => $category->id, 'name' => 'P', 'slug' => 'p-'.uniqid(), 'price' => 10, 'stock' => 3, 'status' => 'active'], $attributes))];
    }
}