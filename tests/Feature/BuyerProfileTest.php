<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_buyer_cannot_access_profile(): void
    {
        $this->get(route('buyer.profile.show'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'vendor']))->get(route('buyer.profile.show'))->assertForbidden();
    }

    public function test_buyer_can_view_and_update_profile_without_security_fields(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->actingAs($buyer)->get(route('buyer.profile.show'))->assertOk()->assertInertia(fn ($page) => $page->missing('profile.password'));
        $password = $buyer->password;
        $this->actingAs($buyer)->patch(route('buyer.profile.update'), ['name' => 'New', 'phone' => '123', 'email' => 'changed@example.test', 'role' => 'admin', 'password' => 'changed'])->assertRedirect();
        $this->assertSame(['New', '123', 'buyer', $password], [$buyer->refresh()->name, $buyer->phone, $buyer->role, $buyer->password]);
    }

    public function test_buyer_avatar_is_validated_and_stored(): void
    {
        Storage::fake('product_images');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->actingAs($buyer)->patch(route('buyer.profile.update'), ['name' => $buyer->name, 'avatar' => UploadedFile::fake()->image('avatar.png')])->assertRedirect();
        $this->assertNotNull($buyer->refresh()->avatar);
        Storage::disk('product_images')->assertExists($buyer->avatar);
    }

    public function test_invalid_and_oversized_avatar_is_rejected(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->actingAs($buyer)->patch(route('buyer.profile.update'), ['name' => $buyer->name, 'avatar' => UploadedFile::fake()->create('avatar.gif', 10, 'image/gif')])->assertSessionHasErrors('avatar');
        $this->actingAs($buyer)->patch(route('buyer.profile.update'), ['name' => $buyer->name, 'avatar' => UploadedFile::fake()->create('avatar.png', 5121, 'image/png')])->assertSessionHasErrors('avatar');
    }

    public function test_sensitive_fields_cannot_be_mass_assigned(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $password = $buyer->password;
        $this->actingAs($buyer)->patch(route('buyer.profile.update'), ['name' => 'Safe', 'role' => 'admin', 'password' => 'new-password', 'user_id' => 999, 'vendor_id' => 10])->assertRedirect();
        $this->assertSame(['buyer', $password], [$buyer->refresh()->role, $buyer->password]);
    }
}