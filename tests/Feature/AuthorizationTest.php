<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_all_dashboards(): void
    {
        foreach (['admin', 'vendor', 'buyer'] as $role) {
            $this->get("/$role/dashboard")->assertRedirect('/login');
        }
    }

    public function test_admin_can_access_only_admin_dashboard(): void
    {
        $this->assertRoleAccess('admin', 'admin');
    }

    public function test_vendor_can_access_only_vendor_dashboard(): void
    {
        $this->assertRoleAccess('vendor', 'vendor');
    }

    public function test_buyer_can_access_only_buyer_dashboard(): void
    {
        $this->assertRoleAccess('buyer', 'buyer');
    }

    public function test_each_role_login_redirects_to_its_dashboard(): void
    {
        foreach (['admin', 'vendor', 'buyer'] as $role) {
            $user = User::factory()->create([
                'email' => "$role-login@example.test",
                'password' => 'Password123!',
                'role' => $role,
            ]);

            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'Password123!',
            ])->assertRedirect("/$role/dashboard");

            $this->post(route('logout'));
        }
    }

    public function test_registration_redirects_by_role_and_creates_active_vendor(): void
    {
        $this->post(route('register'), [
            'name' => 'Registered Buyer',
            'email' => 'registered-buyer@example.test',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'buyer',
        ])->assertRedirect('/buyer/dashboard');

        $this->post(route('logout'));

        $this->post(route('register'), [
            'name' => 'Registered Vendor',
            'email' => 'registered-vendor@example.test',
            'phone' => '081234567891',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'vendor',
        ])->assertRedirect('/vendor/dashboard');

        $user = User::where('email', 'registered-vendor@example.test')->firstOrFail();

        $this->assertInstanceOf(Vendor::class, $user->vendor);
        $this->assertSame('active', $user->vendor->status);
        $this->assertSame($user->id, $user->vendor->user_id);
    }

    private function assertRoleAccess(string $role, string $allowedRole): void
    {
        $user = User::factory()->create(['role' => $role]);
        if ($role === 'vendor') {
            Vendor::create(['user_id' => $user->id, 'status' => 'active']);
        }

        $this->actingAs($user)
            ->get("/$allowedRole/dashboard")
            ->assertOk();

        foreach (['admin', 'vendor', 'buyer'] as $otherRole) {
            if ($otherRole !== $allowedRole) {
                $this->get("/$otherRole/dashboard")->assertForbidden();
            }
        }
    }
}