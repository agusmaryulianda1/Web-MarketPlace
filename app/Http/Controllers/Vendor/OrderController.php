<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorOrderIndexRequest;
use App\Http\Requests\Vendor\UpdateVendorOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderVendorGroup;
use App\Services\OrderService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(VendorOrderIndexRequest $request): Response
    {
        Gate::authorize('viewAny', Order::class);
        $vendorId = $request->user()->vendor->id;
        $paymentStatus = $request->validated('payment_status');
        $orderStatus = $request->validated('order_status');

        $orders = Order::query()
            ->whereHas('vendorGroups', fn ($query) => $query->where('vendor_id', $vendorId))
            ->with([
                'user:id,name',
                'vendorGroups' => fn ($query) => $query->where('vendor_id', $vendorId)->with(['vendor.store', 'items.product.images']),
            ])
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($orderStatus, fn ($query) => $query->whereHas('vendorGroups', fn ($group) => $group->where('vendor_id', $vendorId)->where('status', $orderStatus)))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Order $order) => $this->serializeOrder($order));

        return Inertia::render('Vendor/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);
        $vendorId = request()->user()->vendor->id;
        $order->load([
            'user:id,name',
            'vendorGroups' => fn ($query) => $query->where('vendor_id', $vendorId)->with(['vendor.store', 'items.product.images']),
        ]);

        return Inertia::render('Vendor/Orders/Show', [
            'order' => $this->serializeOrder($order),
        ]);
    }

    public function updateGroupStatus(UpdateVendorOrderStatusRequest $request, Order $order, OrderVendorGroup $group)
    {
        Gate::authorize('updateGroupStatus', $group);

        $this->orderService->updateVendorGroupStatus(
            $order,
            $group,
            $request->user(),
            $request->validated('status'),
            $request->validated('cancellation_reason'),
        );

        return redirect()->route('vendor.orders.show', $order)->with('success', 'Status pesanan berhasil diperbarui.');
    }

    private function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'buyer_name' => $order->user?->name,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'created_at' => $order->created_at?->toISOString(),
            'vendor_group' => $this->serializeGroup($order->vendorGroups->first()),
        ];
    }

    private function serializeGroup($group): array
    {
        return [
            'id' => $group->id,
            'status' => $group->status,
            'available_statuses' => match ($group->status) {
                'pending' => ['processing', 'cancelled'],
                'processing' => ['shipped', 'cancelled'],
                'shipped' => ['completed'],
                default => [],
            },
            'cancellation_reason' => $group->cancellation_reason,
            'subtotal' => $group->subtotal,
            'vendor' => ['id' => $group->vendor?->id, 'name' => $group->vendor?->store?->name],
            'items' => $group->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'image_url' => ($image = ($item->product?->images->firstWhere('is_primary', true) ?? $item->product?->images->first())) ? 
                    \Illuminate\Support\Facades\Storage::disk('product_images')->url($image->path) : null,
            ])->values()->all(),
        ];
    }
}