<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id', 'name', 'slug', 'description', 'price', 'stock', 'status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->where('price', '>', 0)
            ->whereHas('category', fn (Builder $query) => $query->where('status', 'active'))
            ->whereHas('vendor', fn (Builder $query) => $query
                ->where('status', 'active')
                ->whereHas('store', fn (Builder $query) => $query->where('is_open', true)));
    }

    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class); }
    public function cartItems(): HasMany { return $this->hasMany(CartItem::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}