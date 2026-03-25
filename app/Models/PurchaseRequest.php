<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'request_number',
        'requested_by',
        'requester_name',
        'department_name',
        'title',
        'priority',
        'status',
        'request_notes',
        'current_status_at',
        'submitted_at',
        'approved_at',
        'cancelled_at',
    ];

    protected $casts = [
        'current_status_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class)->orderBy('sort_order');
    }

    public function vendorOffers(): HasMany
    {
        return $this->hasMany(PurchaseRequestVendorOffer::class)->orderBy('offer_rank');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PurchaseRequestLog::class)->orderByDesc('acted_at')->orderByDesc('id');
    }
}
