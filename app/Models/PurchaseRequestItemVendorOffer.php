<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItemVendorOffer extends Model
{
    protected $fillable = [
        'purchase_request_item_id',
        'vendor_id',
        'vendor_name',
        'category',
        'contact_person',
        'phone',
        'email',
        'offer_total',
        'currency',
        'lead_time_days',
        'offer_rank',
        'is_selected_by_accounting',
        'offer_notes',
        'quotation_file',
    ];

    protected $casts = [
        'offer_total' => 'decimal:2',
        'lead_time_days' => 'integer',
        'offer_rank' => 'integer',
        'is_selected_by_accounting' => 'boolean',
    ];

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
