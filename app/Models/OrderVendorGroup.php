<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderVendorGroup extends Model
{
    protected $fillable = ['order_id', 'vendor_id', 'status', 'subtotal', 'cancelled_at', 'cancelled_by', 'cancellation_reason'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'cancelled_at' => 'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function statusHistories(): HasMany { return $this->hasMany(OrderStatusHistory::class); }
}