<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_read_orders(): void
    {
        [$admin, $buyer, $vendor, $order] = $this->fixture();
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->actingAs($buyer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertInertia(fn ($page) => $page->where('orders.data.0.id', $order->id));
        $this->actingAs($buyer)->get(route('admin.orders.show', $order))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.orders.show', $order))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk();
    }

    public function test_index_search_filters_paginates_and_serializes_safe_data(): void
    {
        [$admin, $buyer, , $order] = $this->fixture(['order_number' => 'ORD-SEARCH', 'payment_method' => 'bank_transfer'], 'Search Store');
        $buyer->update(['name' => 'Buyer Search']);
        foreach (range(1, 10) as $number) $this->fixture(['order_number' => "ORD-PAGE-{$number}"]);
        $this->actingAs($admin)->get(route('admin.orders.index', ['search' => 'ORD-SEARCH', 'payment_status' => 'pending', 'order_status' => 'pending', 'payment_method' => 'bank_transfer']))->assertInertia(fn ($page) => $page->where('orders.total', 1)->where('orders.data.0.order_number', 'ORD-SEARCH')->where('filters.search', 'ORD-SEARCH')->missing('orders.data.0.user.password')->missing('orders.data.0.user_id'));
        $this->actingAs($admin)->get(route('admin.orders.index', ['search' => 'Buyer Search']))->assertInertia(fn ($page) => $page->where('orders.data.0.id', $order->id));
        $this->actingAs($admin)->get(route('admin.orders.index', ['search' => 'Search Store']))->assertInertia(fn ($page) => $page->where('orders.data.0.id', $order->id));
        $this->actingAs($admin)->get(route('admin.orders.index', ['page' => 2, 'payment_status' => 'pending']))->assertInertia(fn ($page) => $page->where('orders.per_page', 10)->where('orders.current_page', 2));
    }

    public function test_admin_payment_mutation_writes_history_and_preserves_invariants(): void
    {
        [$admin, $buyer, $vendor, $order, $group, $item, $product] = $this->fixture();
        $before = $order->only(['total_amount', 'order_status', 'payment_method']);
        $this->actingAs($admin)->patch(route('admin.orders.payment-status', $order), ['status' => 'paid', 'reason' => 'Verified'])->assertRedirect();
        $this->assertDatabaseHas('payment_status_histories', ['order_id' => $order->id, 'actor_user_id' => $admin->id, 'from_status' => 'pending', 'to_status' => 'paid', 'source' => 'admin']);
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame($before, $order->only(['total_amount', 'order_status', 'payment_method']));
        $this->assertSame('pending', $group->refresh()->status);
        $this->assertSame(2, $item->refresh()->quantity);
        $this->assertSame(8, $product->refresh()->stock);
        $this->actingAs($admin)->patch(route('admin.orders.payment-status', $order), ['status' => 'failed'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('payment_status_histories', 1);
        $this->actingAs($buyer)->patch(route('admin.orders.payment-status', $order), ['status' => 'paid'])->assertForbidden();
        $this->actingAs($vendor)->patch(route('admin.orders.payment-status', $order), ['status' => 'paid'])->assertForbidden();
    }

    public function test_pending_can_fail_but_terminal_payment_states_cannot_change(): void
    {
        [$admin, , , $failedOrder] = $this->fixture();
        $this->actingAs($admin)->patch(route('admin.orders.payment-status', $failedOrder), ['status' => 'failed'])->assertRedirect();
        $this->assertSame('failed', $failedOrder->refresh()->payment_status);
        $this->actingAs($admin)->patch(route('admin.orders.payment-status', $failedOrder), ['status' => 'paid'])->assertSessionHasErrors('status');

        [$admin, , , $paidOrder] = $this->fixture();
        $this->actingAs($admin)->patch(route('admin.orders.payment-status', $paidOrder), ['status' => 'paid'])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.orders.payment-status', $paidOrder), ['status' => 'pending'])->assertSessionHasErrors('status');
        $this->assertSame('paid', $paidOrder->refresh()->payment_status);
        $this->assertDatabaseCount('payment_status_histories', 2);
    }

    private function fixture(array $orderData = [], string $storeName = 'Toko Test'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $vendorUser->id, 'status' => 'active']);
        $vendor->store()->create(['name' => $storeName, 'slug' => 'toko-'.uniqid()]);
        $category = Category::create(['name' => 'Kategori '.uniqid(), 'slug' => 'kategori-'.uniqid(), 'status' => 'active']);
        $product = $vendor->products()->create(['category_id' => $category->id, 'name' => 'Produk', 'slug' => 'produk-'.uniqid(), 'price' => 50, 'stock' => 8, 'status' => 'active']);
        $order = Order::create(['order_number' => 'ORD-'.uniqid(), 'user_id' => $buyer->id, 'total_amount' => 100, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'pending', 'shipping_address' => 'Alamat', ...$orderData]);
        $group = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => 'pending', 'subtotal' => 100]);
        $item = OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'vendor_id' => $vendor->id, 'order_vendor_group_id' => $group->id, 'product_name' => 'Produk snapshot', 'price' => 50, 'quantity' => 2, 'subtotal' => 100]);
        return [$admin, $buyer, $vendorUser, $order, $group, $item, $product];
    }
}
