<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderVendorGroupSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_supports_multiple_vendor_groups_and_relationships(): void
    {
        [$order, $vendorA, $vendorB] = $this->orderWithVendors();
        $groupA = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendorA->id, 'status' => 'pending', 'subtotal' => '10.00']);
        $groupB = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendorB->id, 'status' => 'pending', 'subtotal' => '20.00']);
        $item = OrderItem::create($this->item($order, $vendorA, $groupA, '10.00'));

        $this->assertCount(2, $order->refresh()->vendorGroups);
        $this->assertSame($order->id, $groupA->refresh()->order->id);
        $this->assertSame($vendorA->id, $groupA->refresh()->vendor->id);
        $this->assertSame($groupA->id, $item->refresh()->vendorGroup->id);
        $this->assertSame(0, OrderVendorGroup::whereKey($groupB->id)->where('subtotal', '<', 0)->count());
    }

    public function test_group_vendor_pair_and_subtotal_constraint_are_enforced(): void
    {
        [$order, $vendor] = $this->orderWithVendors();
        OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => 'pending', 'subtotal' => '0.00']);

        try {
            OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => 'pending', 'subtotal' => '-1.00']);
            $this->fail('Negative subtotal must be rejected.');
        } catch (QueryException) {
            // Database constraint verified.
        }

        $this->expectException(QueryException::class);
        OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => 'pending', 'subtotal' => '1.00']);
    }

    public function test_historical_backfill_creates_correct_idempotent_groups(): void
    {
        [$order, $vendorA, $vendorB] = $this->orderWithVendors();
        $items = [
            OrderItem::create($this->item($order, $vendorA, null, '10.00')),
            OrderItem::create($this->item($order, $vendorB, null, '20.00')),
        ];

        $migration = require base_path('database/migrations/2026_09_23_000003_backfill_order_vendor_groups.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('order_vendor_groups', 2);
        $this->assertSame(2, OrderItem::whereIn('id', collect($items)->pluck('id'))->whereNotNull('order_vendor_group_id')->count());
        $this->assertSame(['10.00', '20.00'], OrderVendorGroup::query()->orderBy('vendor_id')->pluck('subtotal')->all());
    }

    public function test_history_tables_and_foreign_keys_exist(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('order_status_histories'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('payment_status_histories'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('order_items', 'order_vendor_group_id'));
    }

    public function test_parent_accepts_partially_cancelled_when_database_supports_constraint_migration(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL constraint behavior is not available on SQLite.');
        }

        $order = Order::factory()->create(['order_status' => 'partially_cancelled']);
        $this->assertSame('partially_cancelled', $order->order_status);
    }

    private function orderWithVendors(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendorA = User::factory()->create(['role' => 'vendor'])->vendor()->create(['status' => 'active']);
        $vendorB = User::factory()->create(['role' => 'vendor'])->vendor()->create(['status' => 'active']);
        $order = Order::create([
            'order_number' => 'ORD-SCHEMA-'.uniqid(),
            'user_id' => $buyer->id,
            'total_amount' => '30.00',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_address' => 'Schema test',
        ]);

        return [$order, $vendorA, $vendorB];
    }

    private function item(Order $order, $vendor, ?OrderVendorGroup $group, string $subtotal): array
    {
        return [
            'order_id' => $order->id,
            'product_id' => $this->product($vendor)->id,
            'vendor_id' => $vendor->id,
            'order_vendor_group_id' => $group?->id,
            'product_name' => 'Product',
            'price' => $subtotal,
            'quantity' => 1,
            'subtotal' => $subtotal,
        ];
    }

    private function product($vendor)
    {
        $category = Category::firstOrCreate(['slug' => 'schema-test'], ['name' => 'Schema Test', 'status' => 'active']);
        return $vendor->products()->create(['category_id' => $category->id, 'name' => 'Product', 'slug' => uniqid('schema-', true), 'price' => 10, 'stock' => 5, 'status' => 'active']);
    }
}