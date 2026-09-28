<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_route(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login', false));
    }

    public function test_guest_can_open_register_route(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Register', false));
    }

    public function test_buyer_can_register_with_phone_and_hashed_password(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Buyer',
            'email' => 'new-buyer@example.test',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'buyer',
        ]);

        $response->assertRedirect('/buyer/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-buyer@example.test',
            'role' => 'buyer',
            'phone' => '081234567890',
        ]);
        $this->assertTrue(Hash::check('Password123!', User::where('email', 'new-buyer@example.test')->value('password')));
    }

    public function test_vendor_can_register_with_vendor_profile(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Vendor',
            'email' => 'new-vendor@example.test',
            'phone' => '081234567891',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'vendor',
        ]);

        $response->assertRedirect('/vendor/dashboard');
        $user = User::where('email', 'new-vendor@example.test')->firstOrFail();
        $this->assertSame('vendor', $user->role);
        $this->assertDatabaseHas('vendors', ['user_id' => $user->id, 'status' => 'active']);
        $this->assertInstanceOf(Vendor::class, $user->vendor);
    }

    public function test_public_registration_rejects_admin_and_unknown_roles(): void
    {
        foreach (['admin', 'manager'] as $role) {
            $this->from(route('register'))->post(route('register'), [
                'name' => 'Invalid Role',
                'email' => $role.'@example.test',
                'phone' => '081234567892',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => $role,
            ])->assertSessionHasErrors('role');
        }
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Duplicate',
                'email' => 'existing@example.test',
                'phone' => '081234567893',
                'role' => 'buyer',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_log_in_and_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'login@example.test',
            'role' => 'admin',
            'password' => 'Password123!',
        ]);
        $this->withSession(['login_marker' => true]);
        $oldSessionId = $this->app['session']->getId();

        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
    }

    public function test_buyer_login_returns_to_local_intended_buyer_product_page(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer', 'password' => 'Password123!']);
        $product = $this->visibleProduct();

        $this->get(route('buyer.products.show', $product))->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $buyer->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('buyer.products.show', $product));
    }

    public function test_buyer_registration_returns_to_local_intended_buyer_destination(): void
    {
        $this->withSession(['url.intended' => '/buyer/cart'])
            ->post(route('register'), [
                'name' => 'Intended Buyer',
                'email' => 'intended-buyer@example.test',
                'phone' => '081234567894',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'buyer',
            ])->assertRedirect('/buyer/cart');
    }

    public function test_buyer_login_rejects_external_or_non_buyer_intended_destinations(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer', 'password' => 'Password123!']);

        foreach (['https://example.test/buyer/cart', '/vendor/dashboard'] as $intended) {
            $this->withSession(['url.intended' => $intended])
                ->post(route('login'), [
                    'email' => $buyer->email,
                    'password' => 'Password123!',
                ])->assertRedirect('/buyer/dashboard');

            $this->post(route('logout'));
        }
    }

    public function test_vendor_and_admin_ignore_buyer_intended_destinations(): void
    {
        foreach (['vendor' => '/vendor/dashboard', 'admin' => '/admin/dashboard'] as $role => $dashboard) {
            $user = User::factory()->create(['role' => $role, 'password' => 'Password123!']);

            $this->withSession(['url.intended' => '/buyer/cart'])
                ->post(route('login'), [
                    'email' => $user->email,
                    'password' => 'Password123!',
                ])->assertRedirect($dashboard);

            $this->post(route('logout'));
        }
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->from(route('login'))
            ->post(route('login'), [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_invalid_credentials_are_rejected_without_disclosure(): void
    {
        User::factory()->create(['email' => 'login@example.test']);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'login@example.test',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotNull($this->app['session']->token());
    }

    public function test_guest_cannot_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    private function visibleProduct(): \App\Models\Product
    {
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = $vendorUser->vendor()->create(['status' => 'active']);
        $vendor->store()->create(['name' => 'Visible Store', 'slug' => 'visible-store', 'is_open' => true]);
        $category = \App\Models\Category::create(['name' => 'Technology', 'slug' => 'technology', 'status' => 'active']);

        return $vendor->products()->create([
            'category_id' => $category->id,
            'name' => 'Visible Product',
            'slug' => 'visible-product',
            'price' => 10000,
            'stock' => 1,
            'status' => 'active',
        ]);
    }

    public function test_authenticated_user_props_exclude_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'phone' => '0900000000',
            'avatar' => 'avatars/private.jpg',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->where('auth.user.role', 'buyer')
                ->missing('auth.user.password')
                ->missing('auth.user.phone')
                ->missing('auth.user.avatar'));
    }
}