<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderVendorGroup;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerOrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_sees_only_own_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $other = User::factory()->create(['role' => 'buyer']);
        $order = $this->order($buyer, 'OWN');
        $otherOrder = $this->order($other, 'OTHER');
        $this->actingAs($buyer)->get(route('buyer.orders.index'))->assertInertia(fn ($page) => $page->where('orders.data.0.order_number', 'OWN'));
        $this->actingAs($buyer)->get(route('buyer.orders.show', $otherOrder))->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_order_payload_contains_safe_item_store_image_and_shipping_data(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = $vendorUser->vendor()->create(['status' => 'active']);
        $vendor->store()->create(['name' => 'Toko Test', 'slug' => 'toko-test']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech-'.uniqid(), 'status' => 'active']);
        $product = $vendor->products()->create(['category_id' => $category->id, 'name' => 'Produk Test', 'slug' => 'produk-'.uniqid(), 'price' => 25, 'stock' => 3, 'status' => 'active']);
        ProductImage::create(['product_id' => $product->id, 'path' => 'products/test.jpg', 'is_primary' => true]);
        $address = $buyer->addresses()->create(['recipient_name' => 'Buyer Test', 'phone' => '08123456789', 'address' => 'Jalan Test', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'postal_code' => '12345', 'is_default' => true]);
        $order = $this->order($buyer, 'PAYLOAD');
        $order->update(['address_id' => $address->id]);
        $group = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => 'pending', 'subtotal' => 50]);
        $order->items()->create(['product_id' => $product->id, 'vendor_id' => $vendor->id, 'order_vendor_group_id' => $group->id, 'product_name' => 'Produk Test', 'price' => 25, 'quantity' => 2, 'subtotal' => 50]);

        $this->actingAs($buyer)->get(route('buyer.orders.index'))->assertInertia(fn ($page) => $page
            ->where('orders.data.0.order_number', 'PAYLOAD')
            ->where('orders.data.0.vendor_groups.0.items.0.product_name', 'Produk Test')
            ->where('orders.data.0.vendor_groups.0.vendor.name', 'Toko Test')
            ->where('orders.data.0.vendor_groups.0.items.0.image_url', fn ($url) => str_contains($url, 'products/test.jpg'))
            ->missing('orders.data.0.user_id'));

        $this->actingAs($buyer)->get(route('buyer.orders.show', $order))->assertInertia(fn ($page) => $page
            ->where('order.shipping.recipient_name', 'Buyer Test')
            ->where('order.shipping.postal_code', '12345')
            ->where('order.vendor_groups.0.vendor.name', 'Toko Test')
            ->missing('order.user_id'));
    }

    public function test_buyer_with_no_orders_receives_empty_paginated_collection(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)->get(route('buyer.orders.index'))->assertInertia(fn ($page) => $page
            ->where('orders.data', [])
            ->where('orders.total', 0));
    }

    private function order(User $user, string $number): Order
    {
        return Order::create(['order_number' => $number, 'user_id' => $user->id, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'pending', 'shipping_address' => 'Street']);
    }
}