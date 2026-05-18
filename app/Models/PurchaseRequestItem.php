<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'item_name',
        'specification',
        'qty',
        'unit',
        'needed_by',
        'purpose',
        'sort_order',
        'item_id',
        'gm_not_approved_reason',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'needed_by' => 'date',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PurchaseRequestItemPhoto::class)->orderBy('sort_order');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function vendorOffers(): HasMany
    {
        return $this->hasMany(PurchaseRequestItemVendorOffer::class)
            ->orderBy('offer_rank')
            ->orderBy('id');
    }
}
