<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $orders = Order::query()
            ->with(['user:id,name', 'items.vendor.user:id,name'])
            ->latest()->limit(3)->get()
            ->map(function (Order $order): array {
                $vendorNames = $order->items->pluck('vendor.user.name')->filter()->unique()->values();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'buyer_name' => $order->user?->name ?? 'Unknown buyer',
                    'vendor_name' => $vendorNames->count() > 1 ? 'Multiple vendors' : ($vendorNames->first() ?? 'Unknown vendor'),
                    'total_amount' => $order->total_amount,
                    'order_status' => $order->order_status,
                    'created_at' => $order->created_at?->toISOString(),
                ];
            })->values();

        $products = Product::query()
            ->with(['vendor.user:id,name', 'category:id,name', 'images' => fn ($query) => $query->where('is_primary', true)->limit(1)])
            ->latest()->limit(3)->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'vendor_name' => $product->vendor?->user?->name ?? 'Unknown vendor',
                'category_name' => $product->category?->name ?? 'Uncategorized',
                'price' => $product->price,
                'status' => $product->status,
                'image_url' => ($path = $product->images->first()?->path) ? Storage::url($path) : null,
                'created_at' => $product->created_at?->toISOString(),
            ])->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'vendors' => Vendor::query()->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
            ],
            'recentOrders' => $orders,
            'recentProducts' => $products,
        ]);
    }
}