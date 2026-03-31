<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_name',
        'is_active',
        'username',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseRequestVendorOffers()
    {
        return $this->hasMany(\App\Models\PurchaseRequestVendorOffer::class, 'created_by');
    }

    public function purchaseRequestLogs()
    {
        return $this->hasMany(\App\Models\PurchaseRequestLog::class);
    }

    public function isRequester(): bool
    {
        return $this->role === 'requester';
    }

    public function isPurchasing(): bool
    {
        return $this->role === 'purchasing';
    }

    public function isAccounting(): bool
    {
        return $this->role === 'accounting';
    }

    public function isGm(): bool
    {
        return $this->role === 'gm';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApprover(): bool
    {
        return in_array($this->role, [
            'purchasing',
            'accounting',
            'gm',
            'owner',
            'admin',
        ], true);
    }

    public function canSeeAllPurchaseRequests(): bool
    {
        return $this->isApprover();
    }

    public function canCreatePurchaseRequests(): bool
    {
        return $this->isRequester() || $this->isAdmin();
    }
}
