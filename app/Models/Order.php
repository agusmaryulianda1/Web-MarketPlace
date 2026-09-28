<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['order_number', 'user_id', 'address_id', 'total_amount', 'payment_method', 'payment_status', 'order_status', 'shipping_address'];

    protected function casts(): array { return ['total_amount' => 'decimal:2']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function address(): BelongsTo { return $this->belongsTo(Address::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function vendorGroups(): HasMany { return $this->hasMany(OrderVendorGroup::class); }
    public function statusHistories(): HasMany { return $this->hasMany(OrderStatusHistory::class); }
    public function paymentStatusHistories(): HasMany { return $this->hasMany(PaymentStatusHistory::class); }
}