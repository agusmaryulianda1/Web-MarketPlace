<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_other_roles_cannot_access_buyer_dashboard(): void
    {
        $this->get(route('buyer.dashboard'))->assertRedirect(route('login'));
        foreach (['vendor', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('buyer.dashboard'))->assertForbidden();
        }
    }

    public function test_buyer_dashboard_returns_visible_catalog_data_only(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $vendorModel = $vendor->vendor()->create(['status' => 'active']);
        $vendorModel->store()->create(['name' => 'Toko Aktif', 'slug' => 'toko-aktif', 'is_open' => true]);
        $activeCategory = Category::create(['name' => 'Electronic', 'slug' => 'electronic', 'status' => 'active']);
        $inactiveCategory = Category::create(['name' => 'Arsip', 'slug' => 'arsip', 'status' => 'inactive']);
        Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active']);
        Category::create(['name' => 'Vendor Orders Development', 'slug' => 'vendor-orders-development', 'status' => 'active']);
        $visible = $vendorModel->products()->create(['category_id' => $activeCategory->id, 'name' => 'Produk Tampil', 'slug' => 'produk-tampil', 'price' => 125000, 'stock' => 3, 'status' => 'active']);
        $vendorModel->products()->create(['category_id' => $activeCategory->id, 'name' => 'Produk Nonaktif', 'slug' => 'produk-nonaktif', 'price' => 1, 'stock' => 1, 'status' => 'inactive']);
        $vendorModel->products()->create(['category_id' => $inactiveCategory->id, 'name' => 'Kategori Nonaktif', 'slug' => 'kategori-nonaktif', 'price' => 1, 'stock' => 1, 'status' => 'active']);
        $inactiveVendor = User::factory()->create(['role' => 'vendor'])->vendor()->create(['status' => 'inactive']);
        $inactiveVendor->products()->create(['category_id' => $activeCategory->id, 'name' => 'Vendor Nonaktif', 'slug' => 'vendor-nonaktif', 'price' => 1, 'stock' => 1, 'status' => 'active']);

        $this->actingAs($buyer)->get(route('buyer.dashboard'))->assertInertia(fn ($page) => $page
            ->component('Buyer/Dashboard')
            ->where('categories', fn ($categories) => collect($categories)->pluck('slug')->all() === ['electronic'])
            ->where('products.0.id', $visible->id)
            ->where('products.0.name', 'Produk Tampil')
            ->where('products.0.vendor.store_name', 'Toko Aktif')
            ->missing('products.1')
        );
    }

    public function test_buyer_dashboard_returns_only_official_active_categories(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        foreach ([
            ['name' => 'Electronic', 'slug' => 'electronic', 'status' => 'active'],
            ['name' => 'Vendor', 'slug' => 'vendor', 'status' => 'active'],
            ['name' => 'Rumah Tangga', 'slug' => 'rumah-tangga', 'status' => 'active'],
            ['name' => 'Fashion & Busana', 'slug' => 'fashion-busana', 'status' => 'active'],
            ['name' => 'Gadget & Aksesoris', 'slug' => 'gadget-aksesoris', 'status' => 'active'],
            ['name' => 'Kecantikan', 'slug' => 'kecantikan', 'status' => 'active'],
            ['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active'],
            ['name' => 'Vendor Orders Development', 'slug' => 'vendor-orders-development', 'status' => 'active'],
            ['name' => 'Arsip', 'slug' => 'arsip', 'status' => 'inactive'],
        ] as $category) {
            Category::create($category);
        }

        $this->actingAs($buyer)->get(route('buyer.dashboard'))->assertInertia(fn ($page) => $page
            ->where('categories', fn ($categories) => collect($categories)->pluck('slug')->sort()->values()->all() === [
                'electronic', 'fashion-busana', 'gadget-aksesoris', 'kecantikan', 'rumah-tangga', 'vendor',
            ])
        );
    }

    public function test_buyer_dashboard_filters_visible_products_by_category_and_keeps_filter(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendorModel = User::factory()->create(['role' => 'vendor'])->vendor()->create(['status' => 'active']);
        $selected = Category::create(['name' => 'Electronic', 'slug' => 'electronic', 'status' => 'active']);
        $other = Category::create(['name' => 'Fashion & Busana', 'slug' => 'fashion-busana', 'status' => 'active']);
        $selectedProduct = $vendorModel->products()->create(['category_id' => $selected->id, 'name' => 'Electronic Product', 'slug' => 'electronic-product', 'price' => 1, 'stock' => 1, 'status' => 'active']);
        $vendorModel->products()->create(['category_id' => $other->id, 'name' => 'Fashion Product', 'slug' => 'fashion-product', 'price' => 1, 'stock' => 1, 'status' => 'active']);

        $this->actingAs($buyer)->get(route('buyer.dashboard', ['category' => 'electronic']))->assertInertia(fn ($page) => $page
            ->component('Buyer/Dashboard')
            ->where('filters.category', 'electronic')
            ->where('products.0.id', $selectedProduct->id)
            ->missing('products.1')
        );
    }

    public function test_buyer_dashboard_unknown_category_returns_empty_home_and_reset_restores_products(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendorModel = User::factory()->create(['role' => 'vendor'])->vendor()->create(['status' => 'active']);
        $category = Category::create(['name' => 'Electronic', 'slug' => 'electronic', 'status' => 'active']);
        $product = $vendorModel->products()->create(['category_id' => $category->id, 'name' => 'Visible Product', 'slug' => 'visible-product', 'price' => 1, 'stock' => 1, 'status' => 'active']);

        $this->actingAs($buyer)->get(route('buyer.dashboard', ['category' => 'unknown-slug']))->assertInertia(fn ($page) => $page
            ->component('Buyer/Dashboard')
            ->where('filters.category', 'unknown-slug')
            ->missing('products.0')
        );

        $this->actingAs($buyer)->get(route('buyer.dashboard'))->assertInertia(fn ($page) => $page
            ->component('Buyer/Dashboard')
            ->where('filters.category', null)
            ->where('products.0.id', $product->id)
        );
    }

    public function test_category_seeder_is_official_and_idempotent(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $categories = [
            'electronic' => 'Electronic',
            'vendor' => 'Vendor',
            'rumah-tangga' => 'Rumah Tangga',
            'fashion-busana' => 'Fashion & Busana',
            'gadget-aksesoris' => 'Gadget & Aksesoris',
            'kecantikan' => 'Kecantikan',
        ];

        foreach ($categories as $slug => $name) {
            $this->assertDatabaseHas('categories', ['slug' => $slug, 'name' => $name]);
            $this->assertSame(1, \App\Models\Category::where('slug', $slug)->count());
        }
    }
}