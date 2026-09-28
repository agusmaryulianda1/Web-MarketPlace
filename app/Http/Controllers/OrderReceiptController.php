<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderReceiptController extends Controller
{
    public function show(Order $order): Response
    {
        Gate::authorize('viewPaymentReceipt', $order);

        $order->load([
            'user:id,name,email',
            'vendorGroups:id,order_id,vendor_id,subtotal',
            'vendorGroups.vendor:id',
            'vendorGroups.vendor.store:id,vendor_id,name',
            'vendorGroups.items:id,order_vendor_group_id,product_name,price,quantity,subtotal',
        ]);

        $paidAt = $order->paymentStatusHistories()
            ->where('to_status', 'paid')
            ->latest('created_at')
            ->latest('id')
            ->first(['created_at']);

        return Inertia::render('Orders/Receipt', [
            'receipt' => [
                'marketplace_name' => config('app.name'),
                'order_number' => $order->order_number,
                'ordered_at' => $order->created_at?->toISOString(),
                'paid_at' => $paidAt?->created_at?->toISOString(),
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'buyer' => [
                    'name' => $order->user?->name,
                    'email' => $order->user?->email,
                ],
                'shipping_address' => $order->shipping_address,
                'vendor_groups' => $order->vendorGroups->map(fn ($group) => [
                    'vendor_name' => $group->vendor?->store?->name,
                    'subtotal' => $group->subtotal,
                    'items' => $group->items->map(fn ($item) => [
                        'product_name' => $item->product_name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
                    ])->values()->all(),
                ])->values()->all(),
            ],
        ]);
    }
}
