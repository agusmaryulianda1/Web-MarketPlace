<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_cart_merges_quantity_and_rejects_stock_overflow(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $vendor->vendor()->create(['status' => 'active']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech', 'status' => 'active']);
        $product = $vendor->vendor->products()->create(['category_id' => $category->id, 'name' => 'P', 'slug' => 'p', 'price' => 10, 'stock' => 3, 'status' => 'active']);
        $this->actingAs($buyer)->post(route('buyer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();
        $this->actingAs($buyer)->post(route('buyer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2])->assertSessionHasErrors('quantity');
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_buyer_can_view_serialized_cart_with_server_values_and_storage_image_url(): void
    {
        Storage::fake('product_images');
        $buyer = User::factory()->create(['role' => 'buyer']);
        [$product, $category] = $this->product(['price' => '10.25', 'stock' => 3]);
        $product->vendor->store()->create(['name' => 'Tech Store', 'slug' => 'tech-store', 'is_open' => true]);
        $product->images()->create(['path' => 'products/photo.jpg', 'is_primary' => true]);
        $cart = $buyer->cart()->create([]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($buyer)->get(route('buyer.cart.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Buyer/Cart/Index')
                ->where('cart.total', '20.50')
                ->where('cart.items.0.price', '10.25')
                ->where('cart.items.0.subtotal', '20.50')
                ->where('cart.items.0.stock', 3)
                ->where('cart.items.0.status', 'active')
                ->where('cart.items.0.vendor.id', $product->vendor_id)
                ->where('cart.items.0.store.name', 'Tech Store')
                ->where('cart.items.0.image_url', Storage::disk('product_images')->url('products/photo.jpg'))
            );
    }

    public function test_buyer_can_update_and_delete_own_cart_item(): void
    {
        [$product] = $this->product(['stock' => 5]);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $item = $buyer->cart()->create([])->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($buyer)->patch(route('buyer.cart.items.update', $item), ['quantity' => 3])->assertRedirect();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);
        $this->actingAs($buyer)->delete(route('buyer.cart.items.destroy', $item))->assertRedirect();
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_buyer_cannot_update_or_delete_another_buyers_cart_item(): void
    {
        [$product] = $this->product(['stock' => 5]);
        $owner = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $item = $owner->cart()->create([])->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($otherBuyer)->patch(route('buyer.cart.items.update', $item), ['quantity' => 2])->assertNotFound();
        $this->actingAs($otherBuyer)->delete(route('buyer.cart.items.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_cart_rejects_invalid_quantity_and_guest_or_non_buyer_access(): void
    {
        [$product] = $this->product(['stock' => 2]);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $item = $buyer->cart()->create([])->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($buyer)->patch(route('buyer.cart.items.update', $item), ['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->actingAs($buyer)->patch(route('buyer.cart.items.update', $item), ['quantity' => 3])->assertSessionHasErrors('quantity');
        $this->app['auth']->logout();
        $this->get(route('buyer.cart.index'))->assertRedirect(route('login'));
        $this->post(route('buyer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1])->assertRedirect(route('login'));
        $this->assertDatabaseCount('cart_items', 1);

        foreach (['vendor', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('buyer.cart.index'))
                ->assertForbidden();
            $this->post(route('buyer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1])->assertForbidden();
        }

        $this->assertDatabaseCount('cart_items', 1);
    }

    private function product(array $attributes = []): array
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $vendorModel = $vendor->vendor()->create(['status' => 'active']);
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech-'.uniqid(), 'status' => 'active']);
        $product = $vendorModel->products()->create(array_merge([
            'category_id' => $category->id, 'name' => 'P', 'slug' => 'p-'.uniqid(), 'price' => 10, 'stock' => 3, 'status' => 'active',
        ], $attributes));
        return [$product, $category];
    }
}