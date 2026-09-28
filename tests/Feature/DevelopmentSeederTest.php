<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_buyer_is_idempotent_and_orders_use_explicit_account(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DevelopmentOrderSeeder::class);
        $this->seed(DevelopmentOrderSeeder::class);
        $buyer = User::where('email', 'buyer@example.test')->firstOrFail();
        $this->assertSame('buyer', $buyer->role);
        $this->assertTrue(Hash::check('password', $buyer->password));
        $this->assertSame(1, $buyer->cart()->count());
        $this->assertDatabaseMissing('orders', ['user_id' => User::where('email', 'vendor-orders-buyer@development.test')->value('id')]);
        $this->assertGreaterThan(0, $buyer->orders()->count());
    }
}