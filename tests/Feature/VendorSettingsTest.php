<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Services\VendorSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_buyer_and_admin_cannot_view_settings(): void
    {
        $this->get(route('vendor.settings.show'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'buyer']))->get(route('vendor.settings.show'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('vendor.settings.show'))->assertForbidden();
    }

    public function test_vendor_can_view_whitelisted_settings(): void
    {
        $vendor = $this->vendorUser(['phone' => '081234567890']);

        $this->actingAs($vendor)->get(route('vendor.settings.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Vendor/Settings')
                ->where('settings', [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'email' => $vendor->email,
                    'phone' => '081234567890',
                    'avatar_url' => null,
                ])
                ->where('auth.user.avatar_url', null)
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
                ->missing('auth.user.avatar')
                ->missing('settings.password')
                ->missing('settings.remember_token')
                ->missing('settings.role'));
    }

    public function test_vendor_can_update_account_fields(): void
    {
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->put(route('vendor.settings.update'), [
            'name' => 'Updated Vendor',
            'email' => 'updated@example.test',
            'phone' => null,
        ])->assertRedirect(route('vendor.settings.show'));

        $this->assertDatabaseHas('users', [
            'id' => $vendor->id,
            'name' => 'Updated Vendor',
            'email' => 'updated@example.test',
            'phone' => null,
        ]);
    }

    public function test_duplicate_and_invalid_email_are_rejected(): void
    {
        $vendor = $this->vendorUser();
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($vendor)->put(route('vendor.settings.update'), [
            'name' => $vendor->name, 'email' => 'taken@example.test',
        ])->assertSessionHasErrors('email');
        $this->actingAs($vendor)->put(route('vendor.settings.update'), [
            'name' => $vendor->name, 'email' => 'invalid-email',
        ])->assertSessionHasErrors('email');
    }

    public function test_avatar_upload_replacement_and_url_serialization(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->put(route('vendor.settings.update'), [
            'name' => $vendor->name, 'email' => $vendor->email,
            'avatar' => UploadedFile::fake()->image('client-name.png'),
        ])->assertRedirect();

        $oldAvatar = $vendor->refresh()->avatar;
        $this->assertStringStartsWith('users/'.$vendor->id.'/avatar/', $oldAvatar);
        $this->assertStringNotContainsString('client-name', $oldAvatar);
        Storage::disk('product_images')->assertExists($oldAvatar);
        $this->actingAs($vendor)->get(route('vendor.settings.show'))
            ->assertInertia(fn ($page) => $page
                ->where('settings.avatar_url', Storage::disk('product_images')->url($oldAvatar))
                ->where('auth.user.avatar_url', Storage::disk('product_images')->url($oldAvatar))
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
                ->missing('auth.user.avatar'));

        $this->actingAs($vendor)->put(route('vendor.settings.update'), [
            'name' => $vendor->name, 'email' => $vendor->email,
            'avatar' => UploadedFile::fake()->image('replacement.png'),
        ])->assertRedirect();

        $newAvatar = $vendor->refresh()->avatar;
        Storage::disk('product_images')->assertMissing($oldAvatar);
        Storage::disk('product_images')->assertExists($newAvatar);
        $this->actingAs($vendor)->get(route('vendor.settings.show'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.avatar_url', Storage::disk('product_images')->url($newAvatar))
                ->missing('auth.user.avatar'));
    }

    public function test_new_avatar_is_deleted_when_update_fails(): void
    {
        Storage::fake('product_images');
        $vendor = $this->vendorUser();
        $oldAvatar = 'users/'.$vendor->id.'/avatar/old.png';
        Storage::disk('product_images')->put($oldAvatar, 'old');
        $vendor->update(['avatar' => $oldAvatar]);
        User::factory()->create(['email' => 'taken@example.test']);
        $this->assertContains($oldAvatar, Storage::disk('product_images')->allFiles('users/'.$vendor->id.'/avatar'));

        $this->expectException(\Throwable::class);
        try {
            app(VendorSettingsService::class)->update($vendor->fresh(), [
                'name' => $vendor->name, 'email' => 'taken@example.test',
                'avatar' => UploadedFile::fake()->image('new.png'),
            ]);
        } finally {
            $this->assertSame($oldAvatar, $vendor->fresh()->avatar);
            $files = Storage::disk('product_images')->allFiles('users/'.$vendor->id.'/avatar');
            $this->assertSame([$oldAvatar], $files);
        }
    }

    public function test_untrusted_fields_cannot_change_security_or_store_data(): void
    {
        $vendor = $this->vendorUser();
        $originalPassword = $vendor->password;
        $store = Store::create([
            'vendor_id' => $vendor->vendor->id, 'name' => 'Original Store', 'slug' => 'original-store',
            'logo' => 'stores/'.$vendor->vendor->id.'/logo/original.png',
            'banner' => 'stores/'.$vendor->vendor->id.'/banner/original.png',
        ]);

        $this->actingAs($vendor)->put(route('vendor.settings.update'), [
            'name' => 'Safe Update', 'email' => $vendor->email,
            'role' => 'admin', 'vendor_id' => 999999, 'store_id' => 999999,
            'password' => 'changed-password',
        ])->assertRedirect();

        $vendor = $vendor->fresh();
        $this->assertSame('vendor', $vendor->role);
        $this->assertSame($originalPassword, $vendor->password);
        $this->assertSame($store->id, $vendor->vendor->store->id);
        $this->assertSame('Original Store', $store->refresh()->name);
        $this->assertSame('stores/'.$vendor->vendor->id.'/logo/original.png', $store->logo);
        $this->assertSame('stores/'.$vendor->vendor->id.'/banner/original.png', $store->banner);
        $this->assertTrue(Hash::check('password', $vendor->password));
    }

    public function test_guest_buyer_and_admin_cannot_update_vendor_password(): void
    {
        $payload = ['current_password' => 'password', 'new_password' => 'NewPassword123!', 'new_password_confirmation' => 'NewPassword123!'];

        $this->put(route('vendor.settings.password.update'), $payload)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'buyer']))->put(route('vendor.settings.password.update'), $payload)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->put(route('vendor.settings.password.update'), $payload)->assertForbidden();
    }

    public function test_vendor_can_update_password_without_changing_account_identity(): void
    {
        $vendor = $this->vendorUser(['name' => 'Vendor Name', 'email' => 'vendor-password@example.test', 'phone' => '081234567890']);
        $original = $vendor->only(['id', 'role', 'name', 'email', 'phone']);
        $oldHash = $vendor->password;

        $this->actingAs($vendor)->put(route('vendor.settings.password.update'), [
            'current_password' => 'password',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
            'user_id' => User::factory()->create(['role' => 'buyer'])->id,
        ])->assertRedirect(route('vendor.settings.show'))->assertSessionHas('success', 'Password berhasil diperbarui.');

        $updated = $vendor->fresh();
        $this->assertNotSame($oldHash, $updated->password);
        $this->assertFalse(Hash::check('password', $updated->password));
        $this->assertTrue(Hash::check('NewPassword123!', $updated->password));
        $this->assertSame($original, $updated->only(['id', 'role', 'name', 'email', 'phone']));
    }

    public function test_password_update_rejects_wrong_current_password_short_password_and_mismatch(): void
    {
        $vendor = $this->vendorUser();

        $this->actingAs($vendor)->put(route('vendor.settings.password.update'), [
            'current_password' => 'wrong-password',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($vendor)->put(route('vendor.settings.password.update'), [
            'current_password' => 'password',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ])->assertSessionHasErrors('new_password');

        $this->actingAs($vendor)->put(route('vendor.settings.password.update'), [
            'current_password' => 'password',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'DifferentPassword123!',
        ])->assertSessionHasErrors('new_password');
    }

    private function vendorUser(array $attributes = []): User
    {
        $user = User::factory()->create([...$attributes, 'role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'status' => 'active']);

        return $user->refresh();
    }
}