<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('viewAny', Order::class);
        $vendorId = request()->user()->vendor->id;
        $vendorGroups = fn ($query) => $query->where('vendor_id', $vendorId);
        $recentOrders = Order::query()->whereHas('vendorGroups', $vendorGroups)->with(['user:id,name', 'vendorGroups' => fn ($query) => $query->where('vendor_id', $vendorId)->with('items')])->latest()->limit(5)->get()->map(fn (Order $order): array => [
            'id' => $order->id, 'order_number' => $order->order_number, 'buyer_name' => $order->user?->name ?? 'Pembeli',
            'product_name' => $order->vendorGroups->first()?->items->first()?->product_name ?? 'Produk', 'item_count' => $order->vendorGroups->sum(fn ($group) => $group->items->count()),
            'subtotal' => $order->vendorGroups->sum(fn ($group) => (float) $group->subtotal), 'order_status' => $order->vendorGroups->first()?->status,
        ])->values();
        $recentProducts = Product::query()->where('vendor_id', $vendorId)->with(['images' => fn ($query) => $query->where('is_primary', true)->limit(1)])->latest()->limit(5)->get()->map(fn (Product $product): array => $this->productData($product))->values();
        $bestSellingProducts = Product::query()->where('products.vendor_id', $vendorId)->join('order_items', 'products.id', '=', 'order_items.product_id')->join('order_vendor_groups', function ($join) use ($vendorId): void {
            $join->on('order_vendor_groups.id', '=', 'order_items.order_vendor_group_id')->where('order_vendor_groups.vendor_id', $vendorId);
        })->select('products.id', 'products.name', 'products.price')->selectRaw('SUM(order_items.quantity) as sold_quantity')->groupBy('products.id', 'products.name', 'products.price')->orderByDesc('sold_quantity')->limit(5)->get()->map(fn (Product $product): array => [
            'id' => $product->id, 'name' => $product->name, 'price' => $product->price, 'sold_quantity' => (int) $product->sold_quantity,
        ])->values();
        $lowStockProducts = Product::query()->where('vendor_id', $vendorId)->where('stock', '<', 5)->latest()->limit(5)->get(['id', 'name', 'stock'])->values();

        return Inertia::render('Vendor/Dashboard', [
            'stats' => [
                'products' => Product::query()->where('vendor_id', $vendorId)->count(),
                'orders' => Order::query()->whereHas('vendorGroups', $vendorGroups)->distinct('orders.id')->count('orders.id'),
                'sales' => Order::query()->whereHas('vendorGroups', $vendorGroups)->with('vendorGroups')->get()->sum(fn ($order) => $order->vendorGroups->where('vendor_id', $vendorId)->sum('subtotal')),
                'processingOrders' => Order::query()->whereHas('vendorGroups', fn ($query) => $query->where('vendor_id', $vendorId)->where('status', 'processing'))->distinct('orders.id')->count('orders.id'),
            ], 'recentOrders' => $recentOrders, 'recentProducts' => $recentProducts,
            'bestSellingProducts' => $bestSellingProducts, 'lowStockProducts' => $lowStockProducts,
        ]);
    }

    private function productData(Product $product): array
    {
        $path = $product->images->first()?->path;
        return ['id' => $product->id, 'name' => $product->name, 'price' => $product->price, 'stock' => $product->stock, 'status' => $product->status, 'image_url' => $path ? Storage::disk('product_images')->url($path) : null];
    }
}