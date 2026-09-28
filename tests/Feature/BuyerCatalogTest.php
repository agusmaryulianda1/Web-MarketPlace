<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_catalog_hides_inactive_product_category_and_vendor(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $vendor->vendor->products()->create(['category_id' => $category->id, 'name' => 'Active', 'slug' => 'active', 'price' => 10, 'stock' => 1, 'status' => 'active']);
        $this->actingAs($buyer)->get(route('buyer.products.index'))->assertInertia(fn ($page) => $page->where('products.data.0.id', $product->id));
        $product->update(['status' => 'inactive']);
        $this->actingAs($buyer)->get(route('buyer.products.show', $product))->assertNotFound();
    }

    public function test_buyer_catalog_search_category_and_store_serialization_are_server_side(): void
    {
        Storage::fake('product_images');
        Storage::disk('product_images')->put('stores/1/logo.png', 'logo');
        Storage::disk('product_images')->put('products/1/image.png', 'image');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $vendor->vendor->store()->create(['name' => 'Safe Store', 'slug' => 'safe-store', 'logo' => 'stores/1/logo.png']);
        $tech = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $home = Category::create(['name' => 'Home', 'slug' => 'home', 'status' => 'active']);
        foreach (range(1, 12) as $number) {
            $vendor->vendor->products()->create(['category_id' => $tech->id, 'name' => "Headphones {$number}", 'slug' => "headphones-{$number}", 'price' => 10, 'stock' => 1, 'status' => 'active']);
        }
        $matching = $vendor->vendor->products()->create(['category_id' => $tech->id, 'name' => 'Orange Headphones', 'slug' => 'orange-headphones', 'price' => 10, 'stock' => 1, 'status' => 'active']);
        $vendor->vendor->products()->create(['category_id' => $home->id, 'name' => 'Orange Lamp', 'slug' => 'orange-lamp', 'price' => 10, 'stock' => 1, 'status' => 'active']);
        ProductImage::create(['product_id' => $matching->id, 'path' => 'products/1/image.png', 'is_primary' => true]);

        $this->actingAs($buyer)->get(route('buyer.products.index', ['search' => 'headphones', 'category' => 'tech']))
            ->assertInertia(fn ($page) => $page
                ->where('products.data', function ($data): bool {
                    $names = collect($data)->pluck('name');

                    return $names->count() === 12
                        && $names->every(
                            fn (string $name): bool =>
                                str_contains(strtolower($name), 'headphones')
                        );
                })
                ->where('filters.search', 'headphones')
                ->where('filters.category', 'tech')
                ->where('categories.0.slug', 'tech')
                ->missing('categories.2')
                ->where('products.data.0.vendor.store.name', 'Safe Store')
                ->where('products.data.0.vendor.store.logo', Storage::disk('product_images')->url('stores/1/logo.png'))
                ->where('products.links.2.url', fn ($url) => str_contains($url, 'search=headphones'))
                ->missing('products.data.0.vendor.user')
                ->missing('products.data.0.vendor.store.phone'));
    }

    public function test_inactive_category_and_vendor_are_not_publicly_visible(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Hidden', 'slug' => 'hidden', 'status' => 'inactive']);
        $product = $vendor->vendor->products()->create(['category_id' => $category->id, 'name' => 'Hidden Product', 'slug' => 'hidden-product', 'price' => 10, 'stock' => 1, 'status' => 'active']);
        $this->actingAs($buyer)->get(route('buyer.products.index', ['category' => 'hidden']))
            ->assertInertia(fn ($page) => $page->where('products.data', []));
        $product->vendor->update(['status' => 'inactive']);
        $this->actingAs($buyer)->get(route('buyer.products.show', $product))->assertNotFound();
    }

    private function vendorUser(): User
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $user->vendor()->create(['status' => 'active']);
        return $user->refresh();
    }
}