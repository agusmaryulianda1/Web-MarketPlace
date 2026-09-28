<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorOrderNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        [, , $order] = $this->fixture();
        $this->get(route('vendor.orders.note', $order))->assertRedirect(route('login'));
    }

    public function test_owner_vendor_can_view_whitelisted_note_payload(): void
    {
        [$vendor, , $order] = $this->fixture();

        $this->actingAs($vendor)->get(route('vendor.orders.note', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Vendor/Orders/Note')
                ->where('note.store_name', 'Owner Store')
                ->where('note.order_number', $order->order_number)
                ->where('note.ordered_at', $order->created_at->toISOString())
                ->where('note.fulfillment_status', 'processing')
                ->where('note.shipping_address', 'Budi, 08123456789, Jalan Snapshot No. 1')
                ->where('note.vendor_subtotal', fn ($amount) => (float) $amount === 125.50)
                ->has('note.items', 1)
                ->where('note.items.0.product_name', 'Snapshot Owner Product')
                ->where('note.items.0.price', fn ($amount) => (float) $amount === 62.75)
                ->where('note.items.0.quantity', 2)
                ->where('note.items.0.subtotal', fn ($amount) => (float) $amount === 125.50)
                ->missing('note.total_amount')
                ->missing('note.payment_method')
                ->missing('note.payment_status')
                ->missing('note.vendor_groups')
                ->missing('note.id')
                ->missing('note.items.0.id')
                ->missing('note.items.0.product_id')
                ->missing('note.items.0.vendor_id')
                ->missing('note.items.0.stock'));
    }

    public function test_unrelated_vendor_is_forbidden(): void
    {
        [, , $order] = $this->fixture();
        $other = $this->vendorUser('unrelated@example.test');
        $this->actingAs($other)->get(route('vendor.orders.note', $order))->assertForbidden();
    }

    public function test_buyer_and_admin_are_forbidden_by_vendor_route(): void
    {
        [, , $order] = $this->fixture();
        $this->actingAs(User::factory()->create(['role' => 'buyer']))->get(route('vendor.orders.note', $order))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('vendor.orders.note', $order))->assertForbidden();
    }

    public function test_vendor_without_profile_is_forbidden(): void
    {
        [, , $order] = $this->fixture();
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->actingAs($vendor)->get(route('vendor.orders.note', $order))->assertForbidden();
    }

    public function test_vendor_without_matching_group_is_forbidden(): void
    {
        [$vendor, , $order] = $this->fixture();
        OrderItem::where('order_id', $order->id)->where('vendor_id', $vendor->vendor->id)->delete();
        OrderVendorGroup::where('order_id', $order->id)->where('vendor_id', $vendor->vendor->id)->delete();
        $this->actingAs($vendor)->get(route('vendor.orders.note', $order))->assertForbidden();
    }

    public function test_note_endpoint_allows_only_get(): void
    {
        [$vendor, , $order] = $this->fixture();
        $url = route('vendor.orders.note', $order);
        $this->actingAs($vendor)->post($url)->assertMethodNotAllowed();
        $this->actingAs($vendor)->put($url)->assertMethodNotAllowed();
        $this->actingAs($vendor)->patch($url)->assertMethodNotAllowed();
        $this->actingAs($vendor)->delete($url)->assertMethodNotAllowed();
    }

    public function test_multi_vendor_note_contains_only_authenticated_vendor_group(): void
    {
        [$owner, $other, $order] = $this->fixture();
        $this->actingAs($owner)->get(route('vendor.orders.note', $order))->assertInertia(fn ($page) => $page
            ->has('note.items', 1)->where('note.store_name', 'Owner Store')
            ->where('note.vendor_subtotal', fn ($amount) => (float) $amount === 125.50)
            ->where('note.items.0.product_name', 'Snapshot Owner Product')->missing('note.vendor_groups'));
        $this->actingAs($other)->get(route('vendor.orders.note', $order))->assertInertia(fn ($page) => $page
            ->has('note.items', 1)->where('note.store_name', 'Other Store')
            ->where('note.vendor_subtotal', fn ($amount) => (float) $amount === 200.0)
            ->where('note.items.0.product_name', 'Snapshot Other Product')->missing('note.vendor_groups'));
    }


    public function test_note_uses_order_item_snapshots_not_current_product_data(): void
    {
        [$vendor, , $order, , $products] = $this->fixture();
        $products[0]->update(['name' => 'Changed Current Name', 'price' => '999.00']);
        $this->actingAs($vendor)->get(route('vendor.orders.note', $order))->assertInertia(fn ($page) => $page
            ->where('note.items.0.product_name', 'Snapshot Owner Product')
            ->where('note.items.0.price', fn ($amount) => (float) $amount === 62.75));
    }

    public function test_getting_note_does_not_mutate_transaction_data(): void
    {
        [$vendor, , $order, $buyer, $products] = $this->fixture();
        DB::table('order_status_histories')->insert([
            'order_id' => $order->id, 'order_vendor_group_id' => null, 'from_status' => 'pending',
            'to_status' => 'processing', 'actor_user_id' => $vendor->id, 'reason' => null,
            'source' => 'vendor', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('payment_status_histories')->insert([
            'order_id' => $order->id, 'from_status' => 'pending', 'to_status' => 'paid',
            'actor_user_id' => $vendor->id, 'reason' => null, 'source' => 'admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cartId = DB::table('carts')->insertGetId(['user_id' => $buyer->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cart_items')->insert(['cart_id' => $cartId, 'product_id' => $products[0]->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $tables = ['orders', 'order_vendor_groups', 'order_items', 'products', 'carts', 'cart_items', 'order_status_histories', 'payment_status_histories'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [
            $table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ])->all();

        $this->actingAs($vendor)->get(route('vendor.orders.note', $order))->assertOk();

        foreach ($tables as $table) {
            $after = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            $this->assertSame($before[$table], $after, "Vendor note GET mutated {$table}.");
        }
    }

    private function fixture(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $owner = $this->vendorUser('owner@example.test', 'Owner Store');
        $other = $this->vendorUser('other@example.test', 'Other Store');
        $category = Category::create(['name' => 'Note Category', 'slug' => 'note-category-'.uniqid(), 'status' => 'active']);
        $products = [
            $owner->vendor->products()->create($this->productData($category->id, 'Current Owner Product')),
            $other->vendor->products()->create($this->productData($category->id, 'Current Other Product')),
        ];
        $order = Order::create([
            'order_number' => 'NOTE-'.uniqid(), 'user_id' => $buyer->id, 'total_amount' => '325.50',
            'payment_method' => 'bank_transfer', 'payment_status' => 'paid', 'order_status' => 'processing',
            'shipping_address' => 'Budi, 08123456789, Jalan Snapshot No. 1',
        ]);
        $groups = [
            OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $owner->vendor->id, 'status' => 'processing', 'subtotal' => '125.50']),
            OrderVendorGroup::create(['order_id' => $order->id, 'vendor_id' => $other->vendor->id, 'status' => 'pending', 'subtotal' => '200.00']),
        ];
        OrderItem::create($this->itemData($order, $groups[0], $products[0], 'Snapshot Owner Product', '62.75', 2, '125.50'));
        OrderItem::create($this->itemData($order, $groups[1], $products[1], 'Snapshot Other Product', '200.00', 1, '200.00'));

        return [$owner, $other, $order, $buyer, $products];
    }

    private function vendorUser(string $email, ?string $storeName = null): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $user->id, 'status' => 'active']);
        if ($storeName) {
            $vendor->store()->create([
                'name' => $storeName,
                'slug' => strtolower(str_replace(' ', '-', $storeName)).'-'.uniqid(),
            ]);
        }

        return $user->refresh();
    }

    private function productData(int $categoryId, string $name): array
    {
        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'price' => '500.00',
            'stock' => 17,
            'status' => 'active',
        ];
    }

    private function itemData(Order $order, OrderVendorGroup $group, $product, string $name, string $price, int $quantity, string $subtotal): array
    {
        return [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_id' => $group->vendor_id,
            'order_vendor_group_id' => $group->id,
            'product_name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ];
    }
}

