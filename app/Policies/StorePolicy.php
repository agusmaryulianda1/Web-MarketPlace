<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'vendor';
    }

    public function view(User $user, Store $store): bool
    {
        return $this->owns($user, $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->owns($user, $store);
    }

    private function owns(User $user, Store $store): bool
    {
        return $user->role === 'vendor' && $user->vendor?->id === $store->vendor_id;
    }
}