<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class VendorProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_vendor_scoped_and_search_is_case_insensitive(): void
    {
        $vendor = $this->vendorUser();
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $match = $vendor->vendor->products()->create($this->productData($category, 'Gaming Laptop'));
        $vendor->vendor->products()->create($this->productData($category, 'Office Mouse'));
        $other->vendor->products()->create($this->productData($category, 'Gaming Laptop Other'));

        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'gAmInG']))
            ->assertInertia(fn ($page) => $page
                ->where('search', 'gAmInG')
                ->has('products.data', 1)
                ->where('products.data.0.id', $match->id));
    }

    public function test_empty_and_single_character_search_do_not_filter_products(): void
    {
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $vendor->vendor->products()->create($this->productData($category, 'Laptop'));
        $vendor->vendor->products()->create($this->productData($category, 'Mouse'));

        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => '']))
            ->assertInertia(fn ($page) => $page->where('search', null)->has('products.data', 2));
        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'l']))
            ->assertInertia(fn ($page) => $page->where('search', null)->has('products.data', 2));
    }

    public function test_two_character_search_filters_products_and_no_match_returns_empty_result(): void
    {
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $laptop = $vendor->vendor->products()->create($this->productData($category, 'Laptop'));
        $vendor->vendor->products()->create($this->productData($category, 'Mouse'));

        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'la']))
            ->assertInertia(fn ($page) => $page
                ->where('search', 'la')
                ->has('products.data', 1)
                ->where('products.data.0.id', $laptop->id));
        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'missing']))
            ->assertInertia(fn ($page) => $page
                ->where('search', 'missing')
                ->has('products.data', 0)
                ->where('products.total', 0)
                ->where('stats.total', 2));
    }

    public function test_status_filters_follow_stock_and_status_rules_and_can_combine_with_search(): void
    {
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $active = $vendor->vendor->products()->create($this->productData($category, 'Active Phone'));
        $inactiveEmpty = $vendor->vendor->products()->create($this->productData($category, 'Empty Phone', 0, 'inactive'));
        $vendor->vendor->products()->create($this->productData($category, 'Empty Tablet', 0));
        $vendor->vendor->products()->create($this->productData($category, 'Inactive Stocked', 4, 'inactive'));

        $this->actingAs($vendor)->get(route('vendor.products.index', ['status' => 'active']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 2)
                ->where('products.data', fn ($products) => collect($products)->pluck('id')->contains($active->id)
                    && collect($products)->every(fn ($product) => $product['status'] === 'active')));
        $this->actingAs($vendor)->get(route('vendor.products.index', ['status' => 'out_of_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 2)
                ->where('products.data', fn ($products) => collect($products)->pluck('id')->contains($inactiveEmpty->id)));
        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'phone', 'status' => 'active']))
            ->assertInertia(fn ($page) => $page
                ->where('search', 'phone')->where('status', 'active')
                ->has('products.data', 1)->where('products.data.0.id', $active->id));
        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'phone', 'status' => 'out_of_stock']))
            ->assertInertia(fn ($page) => $page
                ->where('search', 'phone')->where('status', 'out_of_stock')
                ->has('products.data', 1)->where('products.data.0.id', $inactiveEmpty->id));
    }

    public function test_index_paginates_five_products_and_preserves_filters_in_links(): void
    {
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        foreach (range(1, 6) as $number) {
            $vendor->vendor->products()->create($this->productData($category, 'Phone '.$number));
        }

        $this->actingAs($vendor)->get(route('vendor.products.index', ['search' => 'Phone', 'status' => 'active']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 5)
                ->where('products.per_page', 5)
                ->where('products.total', 6)
                ->where('products.last_page', 2)
                ->where('products.links', fn ($links) => collect($links)->whereNotNull('url')->every(fn ($link) => str_contains($link['url'], 'search=Phone') && str_contains($link['url'], 'status=active'))));
    }

    public function test_export_contains_all_filtered_vendor_products_with_expected_columns(): void
    {
        Excel::fake();
        $vendor = $this->vendorUser();
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        foreach (range(1, 6) as $number) {
            $vendor->vendor->products()->create($this->productData($category, 'Phone '.$number, 0, $number === 1 ? 'inactive' : 'active'));
        }
        $vendor->vendor->products()->create($this->productData($category, 'Laptop', 0));
        $other->vendor->products()->create($this->productData($category, 'Phone Other', 0));

        $this->actingAs($vendor)->get(route('vendor.products.export', ['search' => 'pHoNe', 'status' => 'out_of_stock', 'page' => 2]))->assertOk();

        Excel::assertDownloaded('produk-vendor.xlsx', function ($export) use ($vendor) {
            $products = $export->query()->get();
            $rows = $products->map(fn ($product) => $export->map($product));

            return $export->headings() === ['No', 'Nama Produk', 'Kategori', 'Harga', 'Stok', 'Status', 'Dibuat']
                && $products->count() === 6
                && $products->every(fn ($product) => $product->vendor_id === $vendor->vendor->id)
                && $rows->pluck(0)->all() === range(1, 6)
                && $rows->every(fn ($row) => count($row) === 7 && str_contains(strtolower($row[1]), 'phone') && $row[4] === 0 && is_string($row[6]));
        });
    }

    public function test_vendor_cannot_delete_another_vendors_product(): void
    {
        $vendor = $this->vendorUser();
        $other = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $other->vendor->products()->create($this->productData($category));

        $this->actingAs($vendor)->delete(route('vendor.products.destroy', $product))->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'vendor_id' => $other->vendor->id]);
    }

    public function test_vendor_can_upload_image_and_index_returns_image_url(): void
    {
        Storage::fake('product_images');
        $user = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);

        $this->actingAs($user)->post(route('vendor.products.store'), [
            ...$this->productData($category), 'image' => UploadedFile::fake()->image('product.jpg'),
        ])->assertRedirect(route('vendor.products.index'));

        $product = Product::firstOrFail();
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id, 'is_primary' => true]);
        $this->actingAs($user)->get(route('vendor.products.index'))
            ->assertInertia(fn ($page) => $page->where('products.data.0.image_url', fn ($url) => is_string($url)));
    }

    public function test_vendor_cannot_delete_product_used_by_order_or_cart(): void
    {
        $user = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $user->vendor->products()->create($this->productData($category));

        $cart = Cart::create(['user_id' => User::factory()->create(['role' => 'buyer'])->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)->delete(route('vendor.products.destroy', $product))
            ->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_vendor_cannot_upload_unsupported_product_image(): void
    {
        $user = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);

        $this->actingAs($user)->post(route('vendor.products.store'), [
            ...$this->productData($category), 'image' => UploadedFile::fake()->create('product.gif', 10, 'image/gif'),
        ])->assertSessionHasErrors('image');
    }

    private function vendorUser(string $email = 'vendor@example.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);

        return $user->refresh();
    }

    private function productData(Category $category, string $name = 'Product', int $stock = 10, string $status = 'active'): array
    {
        return ['category_id' => $category->id, 'name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name)), 'description' => 'Description', 'price' => '100.00', 'stock' => $stock, 'status' => $status];
    }
}