<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use App\Support\IndonesiaRegions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_buyer_cannot_access_addresses(): void
    {
        $this->get(route('buyer.addresses.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'vendor']))->get(route('buyer.addresses.index'))->assertForbidden();
    }

    public function test_buyer_can_create_complete_address_and_persistence_uses_authenticated_user(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $province = 'Aceh';
        $city = IndonesiaRegions::cities($province)[0];
        $data = [
            'label' => 'Rumah',
            'recipient_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'postal_code' => '23111',
            'province' => $province,
            'city' => $city,
            'is_default' => true,
        ];

        $this->actingAs($buyer)
            ->post(route('buyer.addresses.store'), $data)
            ->assertRedirect(route('buyer.addresses.index'));

        $this->assertDatabaseHas('addresses', [
            'user_id' => $buyer->id,
            ...$data,
        ]);

        $address = $buyer->addresses()->firstOrFail();
        $this->assertSame(
            ['user_id', 'label', 'recipient_name', 'phone', 'address', 'city', 'province', 'postal_code', 'is_default'],
            array_keys($address->only(['user_id', 'label', 'recipient_name', 'phone', 'address', 'city', 'province', 'postal_code', 'is_default']))
        );
        $this->assertTrue($address->is_default);
    }

    public function test_buyer_sees_only_own_addresses_and_cannot_update_another_address(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $other = User::factory()->create(['role' => 'buyer']);
        $address = Address::create([
            'user_id' => $buyer->id,
            'label' => 'Rumah',
            'recipient_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Kota Banda Aceh',
            'province' => 'Aceh',
            'postal_code' => '23111',
            'is_default' => true,
        ]);
        $foreign = Address::create([
            'user_id' => $other->id,
            'recipient_name' => 'Other',
            'phone' => '2',
            'address' => 'Other street',
        ]);

        $this->actingAs($buyer)
            ->get(route('buyer.addresses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Buyer/Addresses/Index')
                ->has('addresses', 1)
                ->where('addresses.0.id', $address->id)
                ->where('addresses.0.recipient_name', 'Budi Santoso')
                ->where('addresses.0.phone', '081234567890')
                ->where('addresses.0.address', 'Jl. Merdeka No. 10')
                ->where('addresses.0.province', 'Aceh')
                ->where('addresses.0.city', 'Kota Banda Aceh')
                ->where('addresses.0.postal_code', '23111')
                ->missing('addresses.1'));
        $this->actingAs($buyer)->patch(route('buyer.addresses.update', $foreign), ['recipient_name' => 'Changed', 'phone' => '2', 'address' => 'Changed'])->assertNotFound();
        $this->assertSame('Other', $foreign->refresh()->recipient_name);
    }

    public function test_buyer_address_default_is_owned_and_unique_per_user(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $data = ['recipient_name' => 'Buyer', 'phone' => '123', 'address' => 'Street', 'is_default' => true];
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), $data);
        $second = Address::create(['user_id' => $buyer->id, ...$data, 'is_default' => false]);
        $this->actingAs($buyer)->patch(route('buyer.addresses.default', $second))->assertRedirect();
        $this->assertSame(1, $buyer->addresses()->where('is_default', true)->count());
    }

    public function test_address_page_exposes_complete_region_data(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->get(route('buyer.addresses.index'))
            ->assertInertia(fn ($page) => $page
                ->has('regions', 38)
                ->where('regions.Aceh', IndonesiaRegions::cities('Aceh')));
    }

    public function test_address_region_pair_must_be_valid_but_both_empty_is_allowed(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $data = ['recipient_name' => 'Buyer', 'phone' => '123', 'address' => 'Street'];

        $this->actingAs($buyer)->post(route('buyer.addresses.store'), [...$data, 'province' => 'Aceh', 'city' => 'Kota Bogor'])->assertSessionHasErrors('city');
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), [...$data, 'province' => 'Aceh'])->assertSessionHasErrors('city');
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), [...$data, 'city' => 'Kota Bogor'])->assertSessionHasErrors('city');
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), [...$data, 'province' => 'Unknown'])->assertSessionHasErrors('province');
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), $data)->assertRedirect();
    }

    public function test_first_address_becomes_default_and_validation_works(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), ['recipient_name' => '', 'phone' => '', 'address' => ''])->assertSessionHasErrors(['recipient_name', 'phone', 'address']);
        $this->actingAs($buyer)->post(route('buyer.addresses.store'), ['recipient_name' => 'Buyer', 'phone' => '123', 'address' => 'Street'])->assertRedirect();
        $this->assertTrue($buyer->addresses()->first()->is_default);
    }

    public function test_buyer_can_update_own_address_and_user_id_stays_owned(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = Address::create(['user_id' => $buyer->id, 'recipient_name' => 'Old', 'phone' => '1', 'address' => 'Old street']);
        $this->actingAs($buyer)->put(route('buyer.addresses.update', $address), ['recipient_name' => 'New', 'phone' => '2', 'address' => 'New street', 'is_default' => true])->assertRedirect();
        $this->assertSame([$buyer->id, 'New', true], [$address->refresh()->user_id, $address->recipient_name, $address->is_default]);
    }
}