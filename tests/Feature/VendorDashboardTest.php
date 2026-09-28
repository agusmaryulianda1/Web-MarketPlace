<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_authorization_and_missing_vendor_profile(): void
    {
        $this->get(route('vendor.dashboard'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'buyer']))->get(route('vendor.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('vendor.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'vendor']))->get(route('vendor.dashboard'))->assertForbidden();
        $this->actingAs($this->vendorUser())->get(route('vendor.dashboard'))->assertOk();
    }

    public function test_dashboard_is_vendor_scoped_and_uses_item_subtotals(): void
    {
        $vendor = $this->vendorUser('a@example.test');
        $other = $this->vendorUser('b@example.test');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $own = $vendor->vendor->products()->create($this->productData($category, 'Own Product', 2));
        $otherProduct = $other->vendor->products()->create($this->productData($category, 'Other Product', 10));
        $order = Order::create(['order_number' => 'ORD-1', 'user_id' => $buyer->id, 'total_amount' => 9999, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'processing', 'shipping_address' => 'Address']);
        $ownGroup = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $vendor->vendor->id, 'status' => 'processing', 'subtotal' => 200]);
        $otherGroup = OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $other->vendor->id, 'status' => 'processing', 'subtotal' => 9000]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $own->id, 'vendor_id' => $vendor->vendor->id, 'order_vendor_group_id' => $ownGroup->id, 'product_name' => 'Own Product', 'price' => 100, 'quantity' => 2, 'subtotal' => 200]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $otherProduct->id, 'vendor_id' => $other->vendor->id, 'order_vendor_group_id' => $otherGroup->id, 'product_name' => 'Other Product', 'price' => 9000, 'quantity' => 1, 'subtotal' => 9000]);

        $this->actingAs($vendor)->get(route('vendor.dashboard'))->assertInertia(fn ($page) => $page
            ->where('stats.products', 1)->where('stats.orders', 1)->where('stats.sales', 200)->where('stats.processingOrders', 1)
            ->has('recentProducts', 1)->where('recentOrders.0.subtotal', 200)->where('bestSellingProducts.0.sold_quantity', 2)->where('lowStockProducts.0.id', $own->id));
    }

    public function test_dashboard_empty_state_has_no_fake_data(): void
    {
        $this->actingAs($this->vendorUser())->get(route('vendor.dashboard'))->assertInertia(fn ($page) => $page
            ->where('stats.products', 0)->where('stats.orders', 0)->where('stats.sales', 0)->where('stats.processingOrders', 0)
            ->has('recentOrders', 0)->has('recentProducts', 0)->has('bestSellingProducts', 0)->has('lowStockProducts', 0));
    }

    public function test_recent_products_use_primary_image_disk_and_remain_scoped_and_limited(): void
    {
        Storage::fake('product_images', ['url' => 'https://product-images.test']);
        $vendor = $this->vendorUser('images@example.test');
        $other = $this->vendorUser('other-images@example.test');
        $category = Category::create(['name' => 'Images', 'slug' => 'images', 'status' => 'active']);
        $products = collect();

        foreach (range(1, 6) as $number) {
            $product = $vendor->vendor->products()->create($this->productData($category, 'Product '.$number, 10));
            $product->forceFill(['created_at' => now()->subMinutes(6 - $number)])->saveQuietly();
            $products->push($product);
        }

        $primaryPath = 'products/'.$vendor->vendor->id.'/primary.jpg';
        $products[5]->images()->create(['path' => 'products/'.$vendor->vendor->id.'/secondary.jpg', 'is_primary' => false]);
        $products[5]->images()->create(['path' => $primaryPath, 'is_primary' => true]);
        $otherProduct = $other->vendor->products()->create($this->productData($category, 'Other Product', 10));
        $otherProduct->images()->create(['path' => 'products/'.$other->vendor->id.'/primary.jpg', 'is_primary' => true]);

        $expectedUrl = Storage::disk('product_images')->url($primaryPath);

        $this->actingAs($vendor)->get(route('vendor.dashboard'))->assertInertia(fn ($page) => $page
            ->has('recentProducts', 5)
            ->where('recentProducts.0.id', $products[5]->id)
            ->where('recentProducts.0.image_url', $expectedUrl)
            ->where('recentProducts.1.id', $products[4]->id)
            ->where('recentProducts.1.image_url', null)
            ->where('recentProducts', fn ($recentProducts) => collect($recentProducts)->pluck('id')->sort()->values()->all() === $products->slice(1)->pluck('id')->sort()->values()->all()));
    }

    private function vendorUser(string $email = 'vendor@example.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);
        return $user->refresh();
    }

    private function productData(Category $category, string $name, int $stock): array
    {
        return ['category_id' => $category->id, 'name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name)), 'price' => 100, 'stock' => $stock, 'status' => 'active'];
    }
}