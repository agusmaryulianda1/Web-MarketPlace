<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_only_shows_products_matching_every_visibility_rule(): void
    {
        $visible = $this->publicProduct('Visible Product', [], ['name' => 'Electronic', 'slug' => 'electronic']);
        $this->publicProduct('Inactive Product', ['status' => 'inactive']);
        $this->publicProduct('Out Of Stock Product', ['stock' => 0]);
        $this->publicProduct('Free Product', ['price' => 0]);
        $this->publicProduct('Inactive Category Product', [], ['status' => 'inactive']);
        $this->publicProduct('Inactive Vendor Product', [], [], ['status' => 'inactive']);
        $this->publicProduct('Closed Store Product', [], [], [], ['is_open' => false]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Index', false)
                ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$visible->id])
                ->where('categories', fn ($categories) => collect($categories)->contains('slug', $visible->category->slug)));

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->where('products.0.id', $visible->id));
    }

    public function test_homepage_and_catalog_show_only_allowed_active_categories(): void
    {
        $this->publicProduct('Closed Store Product', [], ['name' => 'Gadget & Aksesoris', 'slug' => 'gadget-aksesoris'], [], ['is_open' => false]);
        $this->publicProduct('Unavailable Product', ['stock' => 0], ['name' => 'Kecantikan', 'slug' => 'kecantikan']);
        $visible = $this->publicProduct('Visible Product', [], ['name' => 'Electronic', 'slug' => 'electronic']);
        Category::create(['name' => 'Fashion & Busana', 'slug' => 'fashion-busana', 'status' => 'active']);
        Category::create(['name' => 'Rumah Tangga', 'slug' => 'rumah-tangga', 'status' => 'active']);
        Category::create(['name' => 'Vendor Orders Development', 'slug' => 'vendor-orders-development', 'status' => 'active']);
        Category::create(['name' => 'Hidden Allowed', 'slug' => 'rumah-tangga-hidden', 'status' => 'inactive']);
        Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active']);
        Category::create(['name' => 'Vendor', 'slug' => 'vendor', 'status' => 'active']);
        $expectedCategorySlugs = [
            'electronic',
            'fashion-busana',
            'gadget-aksesoris',
            'kecantikan',
            'rumah-tangga',
            'vendor-orders-development',
        ];

        foreach ([route('home'), route('products.index')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('categories', fn ($categories) => collect($categories)->pluck('slug')->all() === $expectedCategorySlugs)
                    ->where('products', function ($products) use ($url, $visible): bool {
                        $items = $url === route('home') ? $products : $products['data'];

                        return collect($items)->pluck('id')->all() === [$visible->id];
                    }));
        }

        foreach (['electronics', 'vendor', 'unknown-category', 'rumah-tangga-hidden'] as $category) {
            $this->get(route('products.index', ['category' => $category]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('filters.category', $category)
                    ->where('products.data', []));
        }
    }

    public function test_public_catalog_filters_on_server_and_never_exposes_private_fields(): void
    {
        Storage::fake('product_images');
        Storage::disk('product_images')->put('products/private-image.png', 'image');
        $matching = $this->publicProduct('Orange Headphones', [], ['name' => 'Electronic', 'slug' => 'electronic']);
        $other = $this->publicProduct('Orange Lamp', [], ['name' => 'Home', 'slug' => 'home']);
        ProductImage::create(['product_id' => $matching->id, 'path' => 'products/private-image.png', 'is_primary' => true]);

        $this->get(route('products.index', ['search' => 'headphones', 'category' => 'electronic']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.search', 'headphones')
                ->where('filters.category', 'electronic')
                ->where('products.data.0.id', $matching->id)
                ->missing('products.data.1')
                ->where('products.data.0.store.name', $matching->vendor->store->name)
                ->where('products.data.0.images.0.url', Storage::disk('product_images')->url('products/private-image.png'))
                ->missing('products.data.0.stock')
                ->missing('products.data.0.status')
                ->missing('products.data.0.vendor')
                ->missing('products.data.0.store.phone')
                ->missing('products.data.0.images.0.path'));

        $this->get(route('products.show', $matching))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Show', false)
                ->where('product.id', $matching->id)
                ->missing('product.stock')
                ->missing('product.vendor')
                ->missing('product.images.0.path'));

        $this->assertNotEquals($matching->id, $other->id);
    }

    public function test_public_product_detail_returns_not_found_when_product_is_not_visible(): void
    {
        $hidden = $this->publicProduct('Out Of Stock Product', ['stock' => 0]);

        $this->get(route('products.show', $hidden))->assertNotFound();
    }

    private function publicProduct(
        string $name,
        array $productAttributes = [],
        array $categoryAttributes = [],
        array $vendorAttributes = [],
        array $storeAttributes = [],
    ): Product {
        $suffix = str($name)->slug()->append('-'.str()->uuid());
        $category = Category::create([
            'name' => $categoryAttributes['name'] ?? "Category {$suffix}",
            'slug' => $categoryAttributes['slug'] ?? "category-{$suffix}",
            'status' => $categoryAttributes['status'] ?? 'active',
        ]);
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $user->id, 'status' => $vendorAttributes['status'] ?? 'active']);
        $vendor->store()->create([
            'name' => $storeAttributes['name'] ?? "Store {$suffix}",
            'slug' => $storeAttributes['slug'] ?? "store-{$suffix}",
            'phone' => '08123456789',
            'is_open' => $storeAttributes['is_open'] ?? true,
        ]);

        return $vendor->products()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => "product-{$suffix}",
            'description' => 'Public description.',
            'price' => $productAttributes['price'] ?? 10000,
            'stock' => $productAttributes['stock'] ?? 1,
            'status' => $productAttributes['status'] ?? 'active',
        ])->load(['category', 'vendor.store']);
    }
}
