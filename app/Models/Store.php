<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $fillable = ['vendor_id', 'name', 'slug', 'description', 'logo', 'banner', 'phone', 'is_open'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean'];
    }

    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
}