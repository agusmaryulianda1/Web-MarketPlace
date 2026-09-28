<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_change_category_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Laptop Gaming', 'description' => 'Computers', 'status' => 'active',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::firstOrFail();
        $this->assertSame('laptop-gaming', $category->slug);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Laptop Gaming Pro', 'description' => null, 'status' => 'active',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertSame('laptop-gaming-pro', $category->refresh()->slug);
        $this->actingAs($admin)->patch(route('admin.categories.status', $category), ['status' => 'inactive'])
            ->assertRedirect();
        $this->assertSame('inactive', $category->refresh()->status);
    }

    public function test_only_admin_can_mutate_categories_and_slug_collisions_are_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = $this->vendorUser();
        $buyer = User::factory()->create(['role' => 'buyer']);

        foreach ([$vendor, $buyer] as $user) {
            $this->actingAs($user)->post(route('admin.categories.store'), [
                'name' => 'Books', 'status' => 'active',
            ])->assertForbidden();
        }

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Books', 'status' => 'active']);
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Books', 'status' => 'active']);
        $this->assertSame(['books', 'books-2'], Category::query()->orderBy('id')->pluck('slug')->all());
    }

    public function test_vendor_can_create_update_status_and_stock_only_for_own_product(): void
    {
        $user = $this->vendorUser();
        $otherUser = $this->vendorUser('other@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $otherProduct = $otherUser->vendor->products()->create($this->productData($category, 'Other Product'));

        $this->actingAs($user)->post(route('vendor.products.store'), $this->productData($category, 'Gaming Laptop'))
            ->assertRedirect(route('vendor.products.index'));

        $product = $user->vendor->products()->firstOrFail();
        $this->assertSame($user->vendor->id, $product->vendor_id);
        $this->assertSame('gaming-laptop', $product->slug);
        $this->assertSame($category->id, $product->category_id);

        $this->actingAs($user)->get(route('vendor.products.edit', $otherProduct))->assertForbidden();
        $this->actingAs($user)->put(route('vendor.products.update', $otherProduct), $this->productData($category, 'Changed'))
            ->assertForbidden();

        $this->actingAs($user)->patch(route('vendor.products.status', $product), ['status' => 'inactive'])->assertRedirect();
        $this->actingAs($user)->patch(route('vendor.products.stock', $product), ['stock' => 4])->assertRedirect();
        $this->assertSame(4, $product->refresh()->stock);
    }

    public function test_vendor_index_isolated_and_foreign_vendor_id_cannot_change_ownership(): void
    {
        $user = $this->vendorUser();
        $other = $this->vendorUser('second@example.test');
        $category = Category::create(['name' => 'Books', 'slug' => 'books', 'status' => 'active']);
        $product = $user->vendor->products()->create($this->productData($category, 'Book'));
        $other->vendor->products()->create($this->productData($category, 'Other Book'));

        $this->actingAs($user)->get(route('vendor.products.index'))
            ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.id', $product->id));

        $this->actingAs($user)->put(route('vendor.products.update', $product), [
            ...$this->productData($category, 'Updated Book'), 'vendor_id' => $other->vendor->id,
        ])->assertRedirect(route('vendor.products.index'));

        $this->assertSame($user->vendor->id, $product->refresh()->vendor_id);
    }

    public function test_inactive_category_and_invalid_product_values_are_rejected(): void
    {
        $user = $this->vendorUser();
        $inactive = Category::create(['name' => 'Old', 'slug' => 'old', 'status' => 'inactive']);

        $this->actingAs($user)->post(route('vendor.products.store'), [
            ...$this->productData($inactive), 'price' => -1, 'stock' => -1, 'status' => 'bad',
        ])->assertSessionHasErrors(['category_id', 'price', 'stock', 'status']);
    }

    public function test_admin_can_filter_products_and_change_status_without_editing_protected_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = $this->vendorUser();
        $otherVendor = $this->vendorUser('second@example.test');
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $otherCategory = Category::create(['name' => 'Books', 'slug' => 'books', 'status' => 'active']);
        $product = $vendor->vendor->products()->create($this->productData($category, 'Gaming Laptop'));
        $otherVendor->vendor->products()->create($this->productData($otherCategory, 'Other Book'));

        $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'Gaming', 'vendor_id' => $vendor->vendor->id, 'category_id' => $category->id, 'status' => 'active']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.id', $product->id));

        $before = $product->only(['vendor_id', 'price', 'stock', 'category_id', 'name', 'slug']);
        $this->actingAs($admin)->patch(route('admin.products.status', $product), ['status' => 'inactive'])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('inactive', $product->status);
        $this->assertSame($before, $product->only(['vendor_id', 'price', 'stock', 'category_id', 'name', 'slug']));
    }

    public function test_buyer_and_vendor_cannot_use_admin_product_routes_and_invalid_status_is_rejected(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = $this->vendorUser();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $vendor->vendor->products()->create($this->productData($category));

        $this->actingAs($buyer)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($buyer)->patch(route('admin.products.status', $product), ['status' => 'inactive'])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.products.status', $product), ['status' => 'pending'])->assertSessionHasErrors('status');
    }

    private function vendorUser(string $email = 'vendor@example.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);

        return $user->refresh();
    }

    private function productData(Category $category, string $name = 'Product'): array
    {
        return [
            'category_id' => $category->id,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'description' => 'Description',
            'price' => '100.00',
            'stock' => 10,
            'status' => 'active',
        ];
    }
}