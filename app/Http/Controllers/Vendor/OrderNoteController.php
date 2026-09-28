<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderVendorGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderNoteController extends Controller
{
    public function __invoke(Request $request, Order $order): Response
    {
        Gate::authorize('viewVendorNote', $order);

        $group = OrderVendorGroup::query()
            ->select(['id', 'order_id', 'vendor_id', 'status', 'subtotal'])
            ->where('order_id', $order->id)
            ->where('vendor_id', $request->user()->vendor->id)
            ->with([
                'vendor:id',
                'vendor.store:id,vendor_id,name',
                'items:id,order_vendor_group_id,product_name,price,quantity,subtotal',
            ])
            ->firstOrFail();

        return Inertia::render('Vendor/Orders/Note', [
            'note' => [
                'store_name' => $group->vendor?->store?->name ?? 'Toko tidak tersedia',
                'order_number' => $order->order_number,
                'ordered_at' => $order->created_at?->toISOString(),
                'fulfillment_status' => $group->status,
                'shipping_address' => $order->shipping_address,
                'vendor_subtotal' => $group->subtotal,
                'items' => $group->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ])->values()->all(),
            ],
        ]);
    }
}
