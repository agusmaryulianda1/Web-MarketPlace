<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vendor extends Model
{
    protected $fillable = ['user_id', 'status'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function store(): HasOne { return $this->hasOne(Store::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
    public function orderVendorGroups(): HasMany { return $this->hasMany(OrderVendorGroup::class); }
}