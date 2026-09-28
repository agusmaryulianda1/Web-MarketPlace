<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_sees_only_own_items_in_multi_vendor_order(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser('owner@example.test');
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $order = $this->order($buyer);
        $ownProduct = $vendor->vendor->products()->create($this->productData($category->id, 'Own Product'));
        $otherProduct = $other->vendor->products()->create($this->productData($category->id, 'Other Product'));
        OrderItem::create($this->itemData($order, $ownProduct, $vendor->vendor->id, 'Own Product'));
        OrderItem::create($this->itemData($order, $otherProduct, $other->vendor->id, 'Other Product'));

        $this->actingAs($vendor)
            ->get(route('vendor.orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.vendor_group.subtotal', fn ($total) => (float) $total === 100.0)
                ->where('orders.data.0.vendor_group.items', fn ($items) => count($items) === 1
                    && $items[0]['product_name'] === 'Own Product'
                    && ! array_key_exists('vendor_id', $items[0]))
                ->missing('orders.data.0.total_amount')
                ->missing('orders.data.0.user_id')
                ->missing('orders.data.0.address_id')
                ->missing('orders.data.0.shipping_address'));

        $this->actingAs($vendor)
            ->get(route('vendor.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.vendor_group.subtotal', fn ($total) => (float) $total === 100.0)
                ->where('order.vendor_group.items', fn ($items) => count($items) === 1
                    && $items[0]['product_name'] === 'Own Product')
                ->missing('order.total_amount')
                ->missing('order.user_id')
                ->missing('order.address_id')
                ->missing('order.shipping_address'));
    }

    public function test_each_vendor_receives_only_its_own_multi_vendor_items(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser('owner@example.test');
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $order = $this->order($buyer);
        $ownProduct = $vendor->vendor->products()->create($this->productData($category->id, 'Own Product'));
        $otherProduct = $other->vendor->products()->create($this->productData($category->id, 'Other Product'));
        OrderItem::create($this->itemData($order, $ownProduct, $vendor->vendor->id, 'Own Product'));
        OrderItem::create($this->itemData($order, $otherProduct, $other->vendor->id, 'Other Product'));

        $this->actingAs($other)
            ->get(route('vendor.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.vendor_group.subtotal', fn ($total) => (float) $total === 100.0)
                ->where('order.vendor_group.items', fn ($items) => count($items) === 1
                    && $items[0]['product_name'] === 'Other Product'));
    }

    public function test_vendor_cannot_view_order_without_own_item_or_mutate_order(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $order = $this->order($buyer);
        $product = $other->vendor->products()->create($this->productData($category->id, 'Other Product'));
        OrderItem::create($this->itemData($order, $product, $other->vendor->id, 'Other Product'));

        $this->actingAs($vendor)->get(route('vendor.orders.show', $order))->assertForbidden();
        $this->actingAs($vendor)->put(route('vendor.orders.show', $order), [
            'total_amount' => 0,
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ])->assertMethodNotAllowed();
    }

    public function test_vendor_can_filter_own_orders_by_payment_and_order_status(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $vendor->vendor->products()->create($this->productData($category->id, 'Own Product'));
        $otherProduct = $other->vendor->products()->create($this->productData($category->id, 'Other Product'));
        $match = $this->order($buyer, ['payment_status' => 'paid', 'order_status' => 'processing']);
        $wrongPayment = $this->order($buyer, ['order_status' => 'processing']);
        $wrongStatus = $this->order($buyer, ['payment_status' => 'paid', 'order_status' => 'completed']);
        $otherMatch = $this->order($buyer, ['payment_status' => 'paid', 'order_status' => 'processing']);
        OrderItem::create($this->itemData($match, $product, $vendor->vendor->id, 'Match'));
        OrderItem::create($this->itemData($wrongPayment, $product, $vendor->vendor->id, 'Wrong Payment'));
        OrderItem::create($this->itemData($wrongStatus, $product, $vendor->vendor->id, 'Wrong Status'));
        OrderItem::create($this->itemData($otherMatch, $otherProduct, $other->vendor->id, 'Other Match'));

        $this->actingAs($vendor)->get(route('vendor.orders.index', [
            'payment_status' => 'paid',
            'order_status' => 'processing',
        ]))->assertOk()->assertInertia(fn ($page) => $page
            ->where('filters.payment_status', 'paid')
            ->where('filters.order_status', 'processing')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $match->id)
            ->where('orders.data.0.vendor_group.items.0.product_name', 'Match'));
    }

    public function test_vendor_order_filters_reject_values_outside_allowlist(): void
    {
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->get(route('vendor.orders.index', ['payment_status' => 'refunded']))
            ->assertSessionHasErrors('payment_status');
        $this->actingAs($vendor)->get(route('vendor.orders.index', ['order_status' => 'returned']))
            ->assertSessionHasErrors('order_status');
    }

    public function test_vendor_orders_paginate_ten_and_preserve_filters_in_links(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $vendor->vendor->products()->create($this->productData($category->id, 'Own Product'));

        foreach (range(1, 11) as $number) {
            $order = $this->order($buyer, ['payment_status' => 'paid', 'order_status' => 'processing']);
            OrderItem::create($this->itemData($order, $product, $vendor->vendor->id, 'Product '.$number));
        }

        $this->actingAs($vendor)->get(route('vendor.orders.index', [
            'payment_status' => 'paid',
            'order_status' => 'processing',
        ]))->assertOk()->assertInertia(fn ($page) => $page
            ->has('orders.data', 10)
            ->where('orders.per_page', 10)
            ->where('orders.total', 11)
            ->where('orders.last_page', 2)
            ->where('orders.links', fn ($links) => collect($links)->whereNotNull('url')->every(fn ($link) => str_contains($link['url'], 'payment_status=paid') && str_contains($link['url'], 'order_status=processing'))));
    }

    public function test_vendor_order_routes_require_vendor_role(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->get(route('vendor.orders.index'))->assertRedirect('/login');
        $this->actingAs($buyer)->get(route('vendor.orders.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('vendor.orders.index'))->assertForbidden();
    }

    private function order(User $buyer, array $overrides = []): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.fake()->unique()->numerify('#####'),
            'user_id' => $buyer->id,
            'total_amount' => '200.00',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_address' => 'Address',
            ...$overrides,
        ]);
    }

    private function productData(int $categoryId, string $name): array
    {
        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.fake()->unique()->numerify('###'),
            'price' => '100.00',
            'stock' => 10,
            'status' => 'active',
        ];
    }

    private function itemData(Order $order, Product $product, int $vendorId, string $name): array
    {
        $group = OrderVendorGroup::firstOrCreate(['order_id' => $order->id, 'vendor_id' => $vendorId], ['status' => $order->order_status, 'subtotal' => '100.00']);
        return [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_id' => $vendorId,
            'order_vendor_group_id' => $group->id,
            'product_name' => $name,
            'price' => '100.00',
            'quantity' => 1,
            'subtotal' => '100.00',
        ];
    }

    private function vendorUser(string $email = 'vendor@example.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);

        return $user->refresh();
    }
}