<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\PaymentStatusHistory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_routes_require_authentication(): void
    {
        [, , , $order] = $this->fixture();

        $this->get(route('admin.orders.receipt', $order))->assertRedirect(route('login'));
        $this->get(route('buyer.orders.receipt', $order))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_any_paid_order_receipt(): void
    {
        [$admin, , , $order] = $this->fixture();
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        [, , , $otherOrder] = $this->fixture($otherBuyer);

        $this->actingAs($admin)->get(route('admin.orders.receipt', $order))->assertOk();
        $this->actingAs($admin)->get(route('admin.orders.receipt', $otherOrder))->assertOk();
    }

    public function test_buyer_can_view_only_own_paid_order_receipt(): void
    {
        [, $buyer, , $order] = $this->fixture();
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        [, , , $otherOrder] = $this->fixture($otherBuyer);

        $this->actingAs($buyer)->get(route('buyer.orders.receipt', $order))->assertOk();
        $this->actingAs($buyer)->get(route('buyer.orders.receipt', $otherOrder))->assertForbidden();
    }

    public function test_vendor_cannot_view_receipt_routes(): void
    {
        [, , $vendor, $order] = $this->fixture();

        $this->actingAs($vendor)->get(route('admin.orders.receipt', $order))->assertForbidden();
        $this->actingAs($vendor)->get(route('buyer.orders.receipt', $order))->assertForbidden();
        $this->assertFalse(Route::has('vendor.orders.receipt'));
    }

    public function test_pending_and_failed_receipts_are_forbidden(): void
    {
        foreach (['pending', 'failed'] as $status) {
            [$admin, $buyer, , $order] = $this->fixture(null, $status);

            $this->actingAs($admin)->get(route('admin.orders.receipt', $order))->assertForbidden();
            $this->actingAs($buyer)->get(route('buyer.orders.receipt', $order))->assertForbidden();
        }
    }

    public function test_receipt_endpoints_allow_only_get(): void
    {
        [$admin, $buyer, , $order] = $this->fixture();

        $this->actingAs($admin)->post(route('admin.orders.receipt', $order))->assertMethodNotAllowed();
        $this->actingAs($admin)->patch(route('admin.orders.receipt', $order))->assertMethodNotAllowed();
        $this->actingAs($buyer)->post(route('buyer.orders.receipt', $order))->assertMethodNotAllowed();
        $this->actingAs($buyer)->patch(route('buyer.orders.receipt', $order))->assertMethodNotAllowed();
    }

    public function test_paid_receipt_uses_whitelisted_parent_snapshots_and_latest_paid_history(): void
    {
        [$admin, $buyer, , $order] = $this->fixture();
        $this->paymentHistory($order, $admin, now()->subHours(2)->startOfSecond());
        $latestPaidAt = now()->subHour()->startOfSecond();
        $this->paymentHistory($order, $admin, $latestPaidAt);

        $this->actingAs($buyer)->get(route('buyer.orders.receipt', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Receipt')
                ->where('receipt.marketplace_name', config('app.name'))
                ->where('receipt.order_number', $order->order_number)
                ->where('receipt.ordered_at', $order->created_at->toISOString())
                ->where('receipt.paid_at', $latestPaidAt->toISOString())
                ->where('receipt.payment_method', 'bank_transfer')
                ->where('receipt.payment_status', 'paid')
                ->where('receipt.total_amount', fn ($amount) => (float) $amount === 325.50)
                ->where('receipt.buyer.name', $buyer->name)
                ->where('receipt.buyer.email', $buyer->email)
                ->where('receipt.shipping_address', 'Receipt shipping snapshot')
                ->has('receipt.vendor_groups', 2)
                ->where('receipt.vendor_groups.0.vendor_name', 'Store One')
                ->where('receipt.vendor_groups.0.subtotal', fn ($amount) => (float) $amount === 125.50)
                ->where('receipt.vendor_groups.0.items.0.product_name', 'Snapshot Product One')
                ->where('receipt.vendor_groups.0.items.0.price', fn ($amount) => (float) $amount === 62.75)
                ->where('receipt.vendor_groups.0.items.0.quantity', 2)
                ->where('receipt.vendor_groups.0.items.0.subtotal', fn ($amount) => (float) $amount === 125.50)
                ->where('receipt.vendor_groups.1.vendor_name', 'Store Two')
                ->where('receipt.vendor_groups.1.subtotal', fn ($amount) => (float) $amount === 200.0)
                ->where('receipt.vendor_groups.1.items.0.product_name', 'Snapshot Product Two')
                ->missing('receipt.user_id')
                ->missing('receipt.buyer.id')
                ->missing('receipt.vendor_groups.0.id')
                ->missing('receipt.vendor_groups.0.items.0.product_id')
                ->missing('receipt.vendor_groups.0.items.0.stock')
                ->missing('receipt.payment_history')
                ->missing('receipt.payment_actor'));
    }

    public function test_paid_at_is_null_without_paid_history(): void
    {
        [$admin, , , $order] = $this->fixture();

        $this->actingAs($admin)->get(route('admin.orders.receipt', $order))
            ->assertInertia(fn ($page) => $page->where('receipt.paid_at', null));
    }

    public function test_getting_receipt_does_not_mutate_transaction_data(): void
    {
        [$admin, $buyer, , $order, , $products] = $this->fixture();
        $this->paymentHistory($order, $admin, now()->subMinute());
        DB::table('order_status_histories')->insert([
            'order_id' => $order->id,
            'order_vendor_group_id' => null,
            'from_status' => 'pending',
            'to_status' => 'processing',
            'actor_user_id' => $admin->id,
            'reason' => null,
            'source' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cartId = DB::table('carts')->insertGetId(['user_id' => $buyer->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cart_items')->insert(['cart_id' => $cartId, 'product_id' => $products[0]->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $tables = ['orders', 'order_items', 'products', 'order_vendor_groups', 'payment_status_histories', 'order_status_histories', 'cart_items'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [
            $table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ])->all();

        $this->actingAs($admin)->get(route('admin.orders.receipt', $order))->assertOk();

        foreach ($tables as $table) {
            $after = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            $this->assertSame($before[$table], $after, "Receipt GET mutated {$table}.");
        }
    }

    private function fixture(?User $buyer = null, string $paymentStatus = 'paid'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer ??= User::factory()->create(['role' => 'buyer']);
        $vendorUsers = [
            User::factory()->create(['role' => 'vendor']),
            User::factory()->create(['role' => 'vendor']),
        ];
        $category = Category::create([
            'name' => 'Receipt Category '.uniqid(),
            'slug' => 'receipt-category-'.uniqid(),
            'status' => 'active',
        ]);
        $vendors = [];
        $products = [];

        foreach ($vendorUsers as $index => $vendorUser) {
            $vendor = Vendor::create(['user_id' => $vendorUser->id, 'status' => 'active']);
            $vendor->store()->create([
                'name' => 'Store '.($index === 0 ? 'One' : 'Two'),
                'slug' => 'receipt-store-'.uniqid(),
            ]);
            $products[] = $vendor->products()->create([
                'category_id' => $category->id,
                'name' => 'Current Product '.($index + 1),
                'slug' => 'current-product-'.uniqid(),
                'price' => '999.00',
                'stock' => 17 + $index,
                'status' => 'active',
            ]);
            $vendors[] = $vendor;
        }

        $order = Order::create([
            'order_number' => 'RECEIPT-'.uniqid(),
            'user_id' => $buyer->id,
            'total_amount' => '325.50',
            'payment_method' => 'bank_transfer',
            'payment_status' => $paymentStatus,
            'order_status' => 'pending',
            'shipping_address' => 'Receipt shipping snapshot',
        ]);
        $groups = [
            OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendors[0]->id, 'status' => 'pending', 'subtotal' => '125.50']),
            OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendors[1]->id, 'status' => 'pending', 'subtotal' => '200.00']),
        ];
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $products[0]->id,
            'vendor_id' => $vendors[0]->id,
            'order_vendor_group_id' => $groups[0]->id,
            'product_name' => 'Snapshot Product One',
            'price' => '62.75',
            'quantity' => 2,
            'subtotal' => '125.50',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $products[1]->id,
            'vendor_id' => $vendors[1]->id,
            'order_vendor_group_id' => $groups[1]->id,
            'product_name' => 'Snapshot Product Two',
            'price' => '200.00',
            'quantity' => 1,
            'subtotal' => '200.00',
        ]);

        return [$admin, $buyer, $vendorUsers[0], $order, $groups, $products];
    }

    private function paymentHistory(Order $order, User $actor, $createdAt): PaymentStatusHistory
    {
        $history = PaymentStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
            'actor_user_id' => $actor->id,
            'reason' => 'Private payment note',
            'source' => 'admin',
        ]);
        $history->timestamps = false;
        $history->created_at = $createdAt;
        $history->updated_at = $createdAt;
        $history->save();

        return $history;
    }
}
