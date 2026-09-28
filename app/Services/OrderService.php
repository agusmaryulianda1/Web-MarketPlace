<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentStatusHistory;
use App\Models\OrderVendorGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function updateVendorGroupStatus(Order $routeOrder, OrderVendorGroup $routeGroup, User $actor, string $status, ?string $reason = null): OrderVendorGroup
    {
        return DB::transaction(function () use ($routeOrder, $routeGroup, $actor, $status, $reason): OrderVendorGroup {
            $order = Order::query()->whereKey($routeOrder->id)->lockForUpdate()->firstOrFail();
            $groups = OrderVendorGroup::query()
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $group = $groups->firstWhere('id', $routeGroup->id);

            if ($group === null || $group->order_id !== $order->id) {
                abort(403);
            }

            $vendor = $actor->vendor;
            if ($actor->role !== 'vendor' || $vendor === null || $group->vendor_id !== $vendor->id) {
                abort(403);
            }

            $fromStatus = $group->status;
            if (! in_array($status, self::TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Status cannot change from {$fromStatus} to {$status}.",
                ]);
            }

            $group->forceFill([
                'status' => $status,
                'cancelled_at' => $status === 'cancelled' ? now() : $group->cancelled_at,
                'cancelled_by' => $status === 'cancelled' ? $actor->id : $group->cancelled_by,
                'cancellation_reason' => $status === 'cancelled' ? $reason : $group->cancellation_reason,
            ])->save();

            $groups = $groups->map(fn (OrderVendorGroup $item) => $item->id === $group->id ? $group : $item);
            $parentStatus = $this->aggregateStatus($groups);
            if ($order->order_status !== $parentStatus) {
                $order->update(['order_status' => $parentStatus]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'order_vendor_group_id' => $group->id,
                'from_status' => $fromStatus,
                'to_status' => $status,
                'actor_user_id' => $actor->id,
                'reason' => $status === 'cancelled' ? $reason : null,
                'source' => 'vendor',
            ]);

            return $group;
        });
    }

    public function updatePaymentStatus(Order $routeOrder, User $actor, string $status, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($routeOrder, $actor, $status, $reason): Order {
            if ($actor->role !== 'admin') {
                abort(403);
            }

            if (! in_array($status, ['paid', 'failed'], true)) {
                throw ValidationException::withMessages(['status' => 'Payment status is invalid.']);
            }

            $order = Order::query()->whereKey($routeOrder->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $order->payment_status;
            if ($fromStatus !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => "Payment status cannot change from {$fromStatus} to {$status}.",
                ]);
            }

            $order->update(['payment_status' => $status]);
            PaymentStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $status,
                'actor_user_id' => $actor->id,
                'reason' => $reason,
                'source' => 'admin',
            ]);

            return $order;
        });
    }

    private function aggregateStatus($groups): string
    {
        $statuses = $groups->pluck('status');
        if ($statuses->every(fn (string $status): bool => $status === 'cancelled')) {
            return 'cancelled';
        }
        if ($statuses->contains('cancelled')) {
            return 'partially_cancelled';
        }
        foreach (['processing', 'pending', 'shipped'] as $status) {
            if ($statuses->contains($status)) {
                return $status;
            }
        }
        return 'completed';
    }
}