<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly CartService $cartService, private readonly CheckoutService $checkoutService) {}

    public function checkout(): Response
    {
        $user = request()->user();
        $cart = $this->cartService->cart($user)->load(['items.product.vendor.store', 'items.product.category', 'items.product.images']);
        return Inertia::render('Buyer/Checkout', [
            'cart' => $this->cartData($cart),
            'addresses' => $user->addresses()->latest()->get(),
            'payment_methods' => ['bank_transfer', 'cod'],
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        $order = $this->checkoutService->create($request->user(), $request->validated('address_id'), $request->validated('payment_method'));
        return redirect()->route('buyer.orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function index(): Response
    {
        Gate::authorize('viewAny', Order::class);
        $orders = Order::with(['vendorGroups.vendor.store', 'vendorGroups.items.product.images', 'vendorGroups.items.vendor.store'])
            ->where('user_id', request()->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $orders->getCollection()->transform(fn (Order $order) => $this->serialize($order));
        return Inertia::render('Buyer/Orders/Index', ['orders' => $orders]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);
        $order->load(['vendorGroups.vendor.store', 'vendorGroups.items.product.images', 'vendorGroups.items.vendor.store', 'address']);
        return Inertia::render('Buyer/Orders/Show', ['order' => $this->serialize($order, true)]);
    }

    private function serialize(Order $order, bool $detail = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toISOString(),
            'total_amount' => $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'vendor_groups' => $this->groups($order),
        ];
        if ($detail) {
            $address = $order->address;
            $data['shipping'] = [
                'recipient_name' => $address?->recipient_name,
                'phone' => $address?->phone,
                'address' => $address?->address,
                'city' => $address?->city,
                'province' => $address?->province,
                'postal_code' => $address?->postal_code,
            ];
            $data['shipping_address'] = $order->shipping_address;
        }
        return $data;
    }

    private function groups(Order $order): array
    {
        return $order->vendorGroups->map(function ($group): array {
            return [
                'id' => $group->id,
                'status' => $group->status,
                'subtotal' => $group->subtotal,
                'vendor' => [
                    'id' => $group->vendor?->id,
                    'name' => $group->vendor?->store?->name,
                ],
                'items' => $group->items->map(function ($item): array {
            $image = $item->product?->images->firstWhere('is_primary', true) ?? $item->product?->images->first();

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'image_url' => $image ? Storage::disk('product_images')->url($image->path) : null,
            ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private function cartData($cart): array
    {
        $items = $cart->items->map(function ($item): array {
            $product = $item->product;
            $image = $product?->images->firstWhere('is_primary', true) ?? $product?->images->first();
            $subtotal = $product?->price === null ? null : number_format(((float) $product->price * $item->quantity), 2, '.', '');
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $product?->name,
                'price' => $product?->price,
                'quantity' => $item->quantity,
                'subtotal' => $subtotal,
                'image_url' => $image ? Storage::disk('product_images')->url($image->path) : null,
                'store' => $product?->vendor?->store?->only(['name']),
            ];
        })->values()->all();
        return ['id' => $cart->id, 'items' => $items, 'total' => number_format($items ? array_sum(array_map(fn ($item) => (float) $item['subtotal'], $items)) : 0, 2, '.', '')];
    }
}