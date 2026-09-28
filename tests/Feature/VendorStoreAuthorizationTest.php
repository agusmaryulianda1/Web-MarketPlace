<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorStoreAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_store_owner_can_view_and_update_store(): void
    {
        $owner = $this->vendorUser('owner@example.test');
        $other = $this->vendorUser('other@example.test');
        $store = Store::create([
            'vendor_id' => $owner->vendor->id,
            'name' => 'Owner Store',
            'slug' => 'owner-store',
        ]);
        Store::create([
            'vendor_id' => $other->vendor->id,
            'name' => 'Other Store',
            'slug' => 'other-store',
        ]);

        $this->actingAs($owner)->get(route('vendor.store.show'))->assertOk();
        $this->actingAs($owner)->put(route('vendor.store.update'), [
            'name' => 'Updated Store',
            'description' => 'Description',
            'slug' => 'updated-store',
            'phone' => '081234567890',
            'vendor_id' => $other->vendor->id,
            'user_id' => $other->id,
        ])->assertRedirect(route('vendor.store.show'));

        $this->assertSame('Updated Store', $store->refresh()->name);
        $this->assertSame($owner->vendor->id, $store->vendor_id);
        $this->actingAs($other)->get(route('vendor.store.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('store.id', $other->vendor->store->id));
    }

    public function test_store_route_requires_vendor_role_and_validates_fields(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = $this->vendorUser();
        $this->get(route('vendor.store.show'))->assertRedirect('/login');
        $this->actingAs($buyer)->get(route('vendor.store.show'))->assertForbidden();
        $this->actingAs($admin)->get(route('vendor.store.show'))->assertForbidden();
        $this->actingAs($vendor)->get(route('vendor.store.show'))->assertOk();
    }

    public function test_missing_store_is_rendered_as_create_form(): void
    {
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->get(route('vendor.store.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Vendor/Store')->where('store', null));
        $this->assertDatabaseCount('stores', 0);
    }

    public function test_vendor_can_create_store_with_logo(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->post(route('vendor.store.store'), [
            'name' => 'Owner Store',
            'slug' => 'owner-store',
            'description' => 'Description',
            'phone' => '081234567890',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect(route('vendor.store.show'));

        $path = Store::firstOrFail()->logo;
        $this->assertStringStartsWith('stores/'.$vendor->vendor->id.'/logo/', $path);
        Storage::disk('product_images')->assertExists($path);
    }

    public function test_duplicate_store_creation_is_rejected(): void
    {
        $vendor = $this->vendorUser();
        Store::create(['vendor_id' => $vendor->vendor->id, 'name' => 'Store', 'slug' => 'store']);

        $this->actingAs($vendor)->post(route('vendor.store.store'), [
            'name' => 'Another Store', 'slug' => 'another-store',
        ])->assertSessionHasErrors('store');

        $this->assertDatabaseCount('stores', 1);
    }

    public function test_store_validation_rejects_invalid_values_and_logo_path(): void
    {
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->post(route('vendor.store.store'), [
            'name' => '', 'slug' => 'Invalid Slug', 'description' => ['invalid'],
            'phone' => str_repeat('1', 51), 'logo' => 'stores/logo.png',
        ])->assertSessionHasErrors(['name', 'slug', 'description', 'phone', 'logo']);
    }

    public function test_duplicate_slug_is_rejected_except_current_store(): void
    {
        $owner = $this->vendorUser('owner@example.test');
        $other = $this->vendorUser('other@example.test');
        Store::create(['vendor_id' => $other->vendor->id, 'name' => 'Other', 'slug' => 'taken-slug']);
        Store::create(['vendor_id' => $owner->vendor->id, 'name' => 'Owner', 'slug' => 'owner-slug']);

        $this->actingAs($owner)->put(route('vendor.store.update'), [
            'name' => 'Owner', 'slug' => 'taken-slug',
        ])->assertSessionHasErrors('slug');

        $this->actingAs($owner)->put(route('vendor.store.update'), [
            'name' => 'Owner Updated', 'slug' => 'owner-slug',
        ])->assertRedirect(route('vendor.store.show'));
    }

    public function test_logo_replacement_removes_old_logo_after_successful_update(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();
        $old = 'stores/'.$vendor->vendor->id.'/logo/old.png';
        Storage::disk('product_images')->put($old, 'old');
        Store::create(['vendor_id' => $vendor->vendor->id, 'name' => 'Store', 'slug' => 'store', 'logo' => $old]);

        $this->actingAs($vendor)->put(route('vendor.store.update'), [
            'name' => 'Store', 'slug' => 'store', 'logo' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect(route('vendor.store.show'));

        Storage::disk('product_images')->assertMissing($old);
        Storage::disk('product_images')->assertExists(Store::firstOrFail()->logo);
    }

    public function test_vendor_cannot_update_another_vendors_store(): void
    {
        $owner = $this->vendorUser('owner@example.test');
        $other = $this->vendorUser('other@example.test');
        $store = Store::create(['vendor_id' => $owner->vendor->id, 'name' => 'Owner', 'slug' => 'owner']);
        Store::create(['vendor_id' => $other->vendor->id, 'name' => 'Other', 'slug' => 'other']);

        $this->actingAs($other)->put(route('vendor.store.update'), ['name' => 'Changed', 'slug' => 'changed'])
            ->assertRedirect(route('vendor.store.show'));

        $this->assertSame('Owner', $store->refresh()->name);
    }

    public function test_store_hours_route_is_not_available(): void
    {
        $this->assertFalse(collect(app('router')->getRoutes()->getRoutes())->contains(
            fn ($route) => $route->uri() === 'vendor/store/hours'
        ));
    }

    private function vendorUser(string $email = 'vendor@example.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);

        return $user->refresh();
    }
}