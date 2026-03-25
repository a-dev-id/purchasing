<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItemPhoto extends Model
{
    protected $fillable = [
        'purchase_request_item_id',
        'file_path',
        'file_name',
        'sort_order',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'purchase_request_item_id');
    }
}
