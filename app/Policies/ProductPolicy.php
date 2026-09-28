<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool { return $user->role === 'admin' || ($user->role === 'vendor' && $user->vendor !== null); }
    public function view(User $user, Product $product): bool { return $user->role === 'admin' || $this->owns($user, $product); }
    public function create(User $user): bool { return $user->role === 'vendor' && $user->vendor !== null; }
    public function update(User $user, Product $product): bool { return $this->owns($user, $product); }
    public function changeStatus(User $user, Product $product): bool { return $user->role === 'admin' || $this->owns($user, $product); }
    public function updateStock(User $user, Product $product): bool { return $this->owns($user, $product); }
    public function delete(User $user, Product $product): bool { return $this->owns($user, $product); }

    private function owns(User $user, Product $product): bool
    {
        return $user->role === 'vendor' && $user->vendor?->id === $product->vendor_id;
    }
}