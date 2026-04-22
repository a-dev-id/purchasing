<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestVendorOffer extends Model
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
        'is_selected_by_accounting' => 'boolean',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
