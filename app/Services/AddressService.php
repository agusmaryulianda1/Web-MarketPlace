<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function create(User $user, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $attributes): Address {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $default = (bool) ($attributes['is_default'] ?? false);
            $hasAddresses = $user->addresses()->exists();
            unset($attributes['is_default']);
            if ($default || ! $hasAddresses) $user->addresses()->update(['is_default' => false]);
            $attributes['is_default'] = $default || ! $hasAddresses;
            return $user->addresses()->create($attributes);
        });
    }

    public function update(User $user, Address $address, array $attributes): Address
    {
        abort_unless($address->user_id === $user->id, 404);
        return DB::transaction(function () use ($user, $address, $attributes): Address {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $address = $user->addresses()->findOrFail($address->id);
            $default = array_key_exists('is_default', $attributes) ? (bool) $attributes['is_default'] : $address->is_default;
            unset($attributes['is_default']);
            if ($default) $user->addresses()->whereKey('!=', $address->id)->update(['is_default' => false]);
            $address->update([...$attributes, 'is_default' => $default]);
            return $address->refresh();
        });
    }

    public function setDefault(User $user, Address $address): Address
    {
        abort_unless($address->user_id === $user->id, 404);
        return DB::transaction(function () use ($user, $address): Address {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $address = $user->addresses()->findOrFail($address->id);
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
            return $address->refresh();
        });
    }
}