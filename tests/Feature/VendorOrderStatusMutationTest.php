<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\OrderVendorGroup;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOrderStatusMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_progress_own_group_and_writes_history(): void
    {
        [$buyer, $vendor, $order, $group] = $this->fixture();

        $this->actingAs($vendor)->patch(route('vendor.orders.groups.status', [$order, $group]), ['status' => 'processing'])
            ->assertRedirect(route('vendor.orders.show', $order));

        $this->assertDatabaseHas('order_vendor_groups', ['id' => $group->id, 'status' => 'processing']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'processing']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'order_vendor_group_id' => $group->id,
            'actor_user_id' => $vendor->id, 'from_status' => 'pending',
            'to_status' => 'processing', 'reason' => null, 'source' => 'vendor',
        ]);
        $this->assertSame(1, OrderStatusHistory::count());
    }

    public function test_vendor_can_cancel_pending_group_with_reason(): void
    {
        [$buyer, $vendor, $order, $group] = $this->fixture();

        $this->actingAs($vendor)->patch(route('vendor.orders.groups.status', [$order, $group]), [
            'status' => 'cancelled', 'cancellation_reason' => 'Buyer requested cancellation',
        ])->assertRedirect();

        $group->refresh();
        $this->assertSame('cancelled', $group->status);
        $this->assertSame($vendor->id, $group->cancelled_by);
        $this->assertSame('cancelled', $order->refresh()->order_status);
        $this->assertDatabaseHas('order_status_histories', ['reason' => 'Buyer requested cancellation']);
    }

    public function test_forbidden_users_and_other_vendor_cannot_mutate_group(): void
    {
        [$buyer, $vendor, $order, $group] = $this->fixture();
        $other = $this->vendorUser('other@example.test');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get(route('vendor.orders.groups.status', [$order, $group]))->assertMethodNotAllowed();
        $this->actingAs($buyer)->patch(route('vendor.orders.groups.status', [$order, $group]), ['status' => 'processing'])->assertForbidden();
        $this->actingAs($admin)->patch(route('vendor.orders.groups.status', [$order, $group]), ['status' => 'processing'])->assertForbidden();
        $this->actingAs($other)->patch(route('vendor.orders.groups.status', [$order, $group]), ['status' => 'processing'])->assertForbidden();
        $this->assertSame('pending', $group->refresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_invalid_transition_does_not_change_group_parent_or_history(): void
    {
        [$buyer, $vendor, $order, $group] = $this->fixture(['status' => 'shipped'], ['order_status' => 'shipped']);

        $this->actingAs($vendor)->patch(route('vendor.orders.groups.status', [$order, $group]), ['status' => 'cancelled', 'cancellation_reason' => 'Invalid transition test'])
            ->assertSessionHasErrors('status');

        $this->assertSame('shipped', $group->refresh()->status);
        $this->assertSame('shipped', $order->refresh()->order_status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_cancelled_and_processing_aggregates_to_partially_cancelled(): void
    {
        if ($this->app->make('db')->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite test schema lacks production partially_cancelled constraint support.');
        }

        [$buyer, $vendor, $order, $group] = $this->fixture();
        $other = $this->vendorUser('second@example.test');
        $second = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $other->vendor->id, 'status' => 'processing', 'subtotal' => '0.00']);

        $this->actingAs($vendor)->patch(route('vendor.orders.groups.status', [$order, $group]), [
            'status' => 'cancelled', 'cancellation_reason' => 'No longer available',
        ])->assertRedirect();

        $this->assertSame('partially_cancelled', $order->refresh()->order_status);
        $this->assertSame('processing', $second->refresh()->status);
    }

    public function test_group_from_another_order_is_rejected(): void
    {
        [$buyer, $vendor, $order, $group] = $this->fixture();
        [, , $otherOrder, $otherGroup] = $this->fixture([], [], 'second-order@example.test');

        $this->actingAs($vendor)->patch(route('vendor.orders.groups.status', [$order, $otherGroup]), ['status' => 'processing'])->assertForbidden();
        $this->assertSame('pending', $otherGroup->refresh()->status);
        $this->assertSame('pending', $otherOrder->refresh()->order_status);
    }

    public function test_protected_payload_fields_are_ignored(): void
    {
        [$buyer, $vendor, $order, $group] = $this->fixture();
        $payment = $order->payment_status;
        $total = $order->total_amount;

        $this->actingAs($vendor)->patch(route('vendor.orders.groups.status', [$order, $group]), [
            'status' => 'processing', 'vendor_id' => 999, 'order_id' => 999,
            'order_vendor_group_id' => 999, 'payment_status' => 'paid', 'order_status' => 'completed',
            'total_amount' => '1.00', 'user_id' => 999, 'subtotal' => '1.00',
        ])->assertRedirect();

        $this->assertSame($payment, $order->refresh()->payment_status);
        $this->assertSame($total, $order->total_amount);
        $this->assertSame($vendor->vendor->id, $group->refresh()->vendor_id);
        $this->assertSame('processing', $group->status);
    }

    public function test_show_payload_exposes_available_statuses_for_every_group_status(): void
    {
        $matrix = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        foreach ($matrix as $status => $expected) {
            [, $vendor, $order] = $this->fixture(['status' => $status], [], "matrix-{$status}@example.test");

            $this->actingAs($vendor)
                ->get(route('vendor.orders.show', $order))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('order.vendor_group.status', $status)
                    ->where('order.vendor_group.available_statuses', fn ($statuses) => $statuses->values()->all() === $expected));
        }
    }

    public function test_transitions_outside_the_state_machine_are_rejected(): void
    {
        $forbidden = [
            'pending' => ['shipped', 'completed'],
            'processing' => ['processing', 'completed'],
            'shipped' => ['pending', 'processing', 'shipped', 'cancelled'],
            'completed' => ['pending', 'processing', 'shipped', 'completed', 'cancelled'],
            'cancelled' => ['pending', 'processing', 'shipped', 'completed', 'cancelled'],
        ];

        $index = 0;
        foreach ($forbidden as $status => $targets) {
            foreach ($targets as $target) {
                $index++;
                [, $vendor, $order, $group] = $this->fixture(['status' => $status], [], "rejected-{$index}@example.test");

                $this->actingAs($vendor)
                    ->patch(route('vendor.orders.groups.status', [$order, $group]), [
                        'status' => $target,
                        'cancellation_reason' => 'Rejected transition coverage',
                    ])
                    ->assertSessionHasErrors('status');

                $this->assertSame($status, $group->refresh()->status);
                $this->assertSame('pending', $order->refresh()->order_status);
            }
        }

        $this->assertDatabaseCount('order_status_histories', 0);
    }

    private function fixture(array $group = [], array $order = [], ?string $email = null): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser($email ?? 'vendor@example.test');
        $orderModel = Order::create([
            'order_number' => 'ORD-'.fake()->unique()->numerify('#####'), 'user_id' => $buyer->id,
            'total_amount' => '100.00', 'payment_method' => 'cod', 'payment_status' => 'pending',
            'order_status' => 'pending', 'shipping_address' => 'Test', ...$order,
        ]);
        $groupModel = OrderVendorGroup::create([
            'order_id' => $orderModel->id, 'vendor_id' => $vendor->vendor->id,
            'status' => 'pending', 'subtotal' => '0.00', ...$group,
        ]);
        return [$buyer, $vendor, $orderModel, $groupModel];
    }

    private function vendorUser(string $email): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);
        return $user->refresh();
    }
}