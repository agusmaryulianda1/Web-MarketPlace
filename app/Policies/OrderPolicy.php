<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderVendorGroup;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin'
            || $user->role === 'buyer'
            || ($user->role === 'vendor' && $user->vendor !== null);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'buyer' && $order->user_id === $user->id)
            || ($user->role === 'vendor'
                && $user->vendor !== null
                && $order->vendorGroups()->where('vendor_id', $user->vendor->id)->exists());
    }

    public function viewVendorNote(User $user, Order $order): bool
    {
        return $user->role === 'vendor'
            && $user->vendor !== null
            && $order->vendorGroups()->where('vendor_id', $user->vendor->id)->exists();
    }

    public function viewPaymentReceipt(User $user, Order $order): bool
    {
        if ($order->payment_status !== 'paid') {
            return false;
        }

        return $user->role === 'admin'
            || ($user->role === 'buyer' && $order->user_id === $user->id);
    }

    public function updatePaymentStatus(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }

    public function updateGroupStatus(User $user, OrderVendorGroup $group): bool
    {
        return $user->role === 'vendor'
            && $user->vendor !== null
            && $group->vendor_id === $user->vendor->id;
    }
}
