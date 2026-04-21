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
        'date_needed',
        'status',
        'request_notes',
        'current_status_at',
        'last_activity_at',
        'last_reminder_sent_at',
        'submitted_at',
        'approved_at',
        'cancelled_at',
        'vendor_comparison_mode',
        'received_at',
    ];

    protected $casts = [
        'date_needed' => 'date',
        'current_status_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'received_at' => 'datetime',
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

    public function reminderOwnerRole(): ?string
    {
        return match ($this->status) {
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm'
            => 'requester',

            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm'
            => 'purchasing',

            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm'
            => 'accounting',

            'submitted_to_gm',
            'on_hold_by_gm'
            => 'gm',

            default => null,
        };
    }

    public function reminderOwnerLabel(): ?string
    {
        return match ($this->reminderOwnerRole()) {
            'requester' => 'Requester',
            'purchasing' => 'Purchasing',
            'accounting' => 'Accounting',
            'gm' => 'GM',
            default => null,
        };
    }

    public function isReminderEligible(): bool
    {
        return $this->reminderOwnerRole() !== null;
    }

    public function markActivity(?string $status = null): void
    {
        if ($status !== null) {
            $this->status = $status;
        }

        $this->current_status_at = now();
        $this->last_activity_at = now();
        $this->last_reminder_sent_at = null;
        $this->save();
    }

    public function touchActivityOnly(): void
    {
        $this->last_activity_at = now();
        $this->last_reminder_sent_at = null;
        $this->save();
    }
}
