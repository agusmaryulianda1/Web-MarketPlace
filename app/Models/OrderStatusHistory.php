<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'order_vendor_group_id',
        'from_status',
        'to_status',
        'actor_user_id',
        'reason',
        'source',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderVendorGroup(): BelongsTo
    {
        return $this->belongsTo(OrderVendorGroup::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}