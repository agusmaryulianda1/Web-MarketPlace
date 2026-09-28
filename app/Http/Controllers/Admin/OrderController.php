<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrderIndexRequest;
use App\Http\Requests\Admin\UpdateOrderPaymentStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(AdminOrderIndexRequest $request): Response
    {
        Gate::authorize('viewAny', Order::class);
        $filters = $request->validated();
        $search = $filters['search'] ?? null;

        $orders = Order::query()
            ->with(['user:id,name,email', 'vendorGroups.vendor.store'])
            ->when($search, function ($query, $value) {
                $like = '%'.mb_strtolower($value).'%';
                $query->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(order_number) LIKE ?', [$like])
                        ->orWhereHas('user', fn ($buyer) => $buyer->whereRaw('LOWER(name) LIKE ?', [$like]))
                        ->orWhereHas('vendorGroups.vendor.store', fn ($store) => $store->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->when($filters['payment_status'] ?? null, fn ($query, $value) => $query->where('payment_status', $value))
            ->when($filters['order_status'] ?? null, fn ($query, $value) => $query->where('order_status', $value))
            ->when($filters['payment_method'] ?? null, fn ($query, $value) => $query->where('payment_method', $value))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Order $order) => $this->serializeIndexOrder($order));

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'payment_status' => $filters['payment_status'] ?? null,
                'order_status' => $filters['order_status'] ?? null,
                'payment_method' => $filters['payment_method'] ?? null,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);
        $order->load([
            'user:id,name,email',
            'vendorGroups.vendor.store',
            'vendorGroups.items',
            'paymentStatusHistories.actor:id,name,email',
        ]);

        return Inertia::render('Admin/Orders/Show', ['order' => $this->serializeDetailOrder($order)]);
    }

    public function updatePaymentStatus(UpdateOrderPaymentStatusRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('updatePaymentStatus', $order);
        $this->orderService->updatePaymentStatus(
            $order,
            $request->user(),
            $request->validated('status'),
            $request->validated('reason'),
        );

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    private function serializeIndexOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toISOString(),
            'buyer' => ['id' => $order->user?->id, 'name' => $order->user?->name, 'email' => $order->user?->email],
            'total_amount' => $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'vendor_groups' => $this->serializeGroups($order),
        ];
    }

    private function serializeDetailOrder(Order $order): array
    {
        return [
            ...$this->serializeIndexOrder($order),
            'shipping_address' => $order->shipping_address,
            'payment_history' => $order->paymentStatusHistories->map(fn ($history) => [
                'id' => $history->id,
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'reason' => $history->reason,
                'source' => $history->source,
                'created_at' => $history->created_at?->toISOString(),
                'actor' => ['id' => $history->actor?->id, 'name' => $history->actor?->name, 'email' => $history->actor?->email],
            ])->values()->all(),
        ];
    }

    private function serializeGroups(Order $order): array
    {
        return $order->vendorGroups->map(fn ($group) => [
            'id' => $group->id,
            'status' => $group->status,
            'subtotal' => $group->subtotal,
            'cancellation_reason' => $group->cancellation_reason,
            'vendor' => ['id' => $group->vendor?->id, 'name' => $group->vendor?->store?->name],
            'items' => $group->relationLoaded('items') ? $group->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ])->values()->all() : [],
        ])->values()->all();
    }
}
