<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'brand',
        'default_unit',
        'default_specification',
        'last_price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'last_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class);
    }

    public function updateLastPriceFromPurchasing(float|int|null $price, ?string $currency = 'IDR'): void
    {
        if ($price === null || $price <= 0) {
            return;
        }

        $this->update([
            'last_price' => $price,
            'currency' => $currency ?: ($this->currency ?: 'IDR'),
        ]);
    }
}
