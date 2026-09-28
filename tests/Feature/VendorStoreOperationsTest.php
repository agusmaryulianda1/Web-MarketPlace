<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorStoreOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_store_is_closed_by_default(): void
    {
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->post(route('vendor.store.store'), ['name' => 'Store', 'slug' => 'store'])
            ->assertRedirect(route('vendor.store.show'));

        $store = Store::firstOrFail();
        $this->assertFalse($store->is_open);
    }

    public function test_owner_can_open_and_close_manually(): void
    {
        $vendor = $this->vendorUser();
        $store = Store::create(['vendor_id' => $vendor->vendor->id, 'name' => 'Store', 'slug' => 'store']);
        $this->actingAs($vendor)->patch(route('vendor.store.status'), ['is_open' => true])->assertRedirect();
        $this->assertTrue($store->refresh()->is_open);

        $this->actingAs($vendor)->patch(route('vendor.store.status'), ['is_open' => false])->assertRedirect();
        $this->assertFalse($store->refresh()->is_open);
    }

    public function test_invalid_banner_is_rejected(): void
    {
        $vendor = $this->vendorUser();
        Store::create(['vendor_id' => $vendor->vendor->id, 'name' => 'Store', 'slug' => 'store']);

        $this->actingAs($vendor)->post(route('vendor.store.store'), ['name' => 'Store', 'slug' => 'store', 'banner' => UploadedFile::fake()->create('banner.pdf', 100, 'application/pdf')])->assertSessionHasErrors('banner');
    }

    public function test_other_vendor_cannot_update_status(): void
    {
        $owner = $this->vendorUser('owner@example.test');
        $other = $this->vendorUser('other@example.test');
        Store::create(['vendor_id' => $owner->vendor->id, 'name' => 'Store', 'slug' => 'store']);

        $this->actingAs($other)->patch(route('vendor.store.status'), ['is_open' => true])->assertForbidden();
    }

    public function test_vendor_can_upload_and_replace_banner(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->post(route('vendor.store.store'), ['name' => 'Store', 'slug' => 'store', 'banner' => UploadedFile::fake()->image('banner.png')])->assertRedirect();
        $store = Store::firstOrFail();
        $old = $store->banner;
        $this->assertStringStartsWith('stores/'.$vendor->vendor->id.'/banner/', $old);
        Storage::disk('product_images')->assertExists($old);

        $vendor = $vendor->refresh();
        $this->actingAs($vendor)->put(route('vendor.store.update'), ['name' => 'Store', 'slug' => 'store', 'banner' => UploadedFile::fake()->image('new-banner.png')])->assertRedirect();
        $new = $store->refresh()->banner;
        Storage::disk('product_images')->assertMissing($old);
        Storage::disk('product_images')->assertExists($new);
    }

    public function test_banner_persists_and_serializes_from_saved_path(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();
        $this->actingAs($vendor)->post(route('vendor.store.store'), [
            'name' => 'Store', 'slug' => 'store', 'banner' => UploadedFile::fake()->image('banner.png'),
        ])->assertRedirect();

        $store = Store::firstOrFail()->fresh();
        $this->assertNotNull($store->banner);
        Storage::disk('product_images')->assertExists($store->banner);
        $vendor = $vendor->refresh();
        $this->actingAs($vendor)->get(route('vendor.store.show'))
            ->assertInertia(fn ($page) => $page->where('store', [
                'id' => $store->id,
                'name' => 'Store',
                'slug' => 'store',
                'description' => null,
                'phone' => null,
                'is_open' => false,
                'logo_url' => null,
                'banner_url' => Storage::disk('product_images')->url($store->banner),
            ]));
    }

    public function test_profile_update_without_banner_preserves_existing_banner(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();
        $banner = 'stores/'.$vendor->vendor->id.'/banner/existing.png';
        Storage::disk('product_images')->put($banner, 'banner');
        Store::create(['vendor_id' => $vendor->vendor->id, 'name' => 'Store', 'slug' => 'store', 'banner' => $banner]);

        $this->actingAs($vendor)->put(route('vendor.store.update'), ['name' => 'Updated', 'slug' => 'updated'])
            ->assertRedirect();

        $this->assertSame($banner, Store::firstOrFail()->banner);
        Storage::disk('product_images')->assertExists($banner);
    }

    private function vendorUser(string $email = 'vendor@example.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);

        return $user->refresh();
    }
}