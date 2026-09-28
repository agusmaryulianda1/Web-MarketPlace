<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_dashboard_with_real_statistics_and_safe_props(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $vendorUser->id, 'status' => 'active']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $vendor->products()->create($this->productData($category, 'Keyboard'));
        Order::create($this->orderData($admin));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.users', 2)
                ->where('stats.vendors', 1)
                ->where('stats.products', 1)
                ->where('stats.orders', 1)
                ->missing('stats.password')
                ->missing('recentOrders.0.user.password')
                ->missing('recentProducts.0.vendor.password'));
    }

    public function test_buyer_and_vendor_receive_forbidden(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($buyer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_recent_orders_use_multiple_vendors_label(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendorA = Vendor::create(['user_id' => User::factory()->create(['role' => 'vendor'])->id, 'status' => 'active']);
        $vendorB = Vendor::create(['user_id' => User::factory()->create(['role' => 'vendor'])->id, 'status' => 'active']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $productA = $vendorA->products()->create($this->productData($category, 'Keyboard'));
        $productB = $vendorB->products()->create($this->productData($category, 'Mouse'));
        $order = Order::create($this->orderData($buyer));
        OrderItem::create($this->itemData($order, $productA, $vendorA));
        OrderItem::create($this->itemData($order, $productB, $vendorB));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page->where('recentOrders.0.vendor_name', 'Multiple vendors'));
    }

    public function test_recent_products_and_empty_state_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.users', 1)
                ->where('stats.vendors', 0)
                ->where('stats.products', 0)
                ->where('stats.orders', 0)
                ->has('recentOrders', 0)
                ->has('recentProducts', 0));
    }

    private function productData(Category $category, string $name): array
    {
        return ['category_id' => $category->id, 'name' => $name, 'slug' => strtolower($name), 'description' => 'Description', 'price' => 100000, 'stock' => 10, 'status' => 'active'];
    }

    private function orderData(User $user): array
    {
        return ['order_number' => 'ORD-'.uniqid(), 'user_id' => $user->id, 'total_amount' => 100000, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'pending', 'shipping_address' => 'Test address'];
    }

    private function itemData(Order $order, Product $product, Vendor $vendor): array
    {
        return ['order_id' => $order->id, 'product_id' => $product->id, 'vendor_id' => $vendor->id, 'product_name' => $product->name, 'price' => 100000, 'quantity' => 1, 'subtotal' => 100000];
    }
}