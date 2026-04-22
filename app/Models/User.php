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

    public function getNormalizedRoleAttribute(): string
    {
        return strtolower(trim((string) $this->role));
    }

    public function normalizedRole(): string
    {
        return strtolower(trim((string) $this->role));
    }

    public function isRequester(): bool
    {
        return $this->normalizedRole() === 'requester';
    }

    public function isPurchasing(): bool
    {
        return $this->normalizedRole() === 'purchasing';
    }

    public function isAccounting(): bool
    {
        return $this->normalizedRole() === 'accounting';
    }

    public function isGm(): bool
    {
        return in_array($this->normalizedRole(), [
            'gm',
            'general_manager',
            'general-manager',
            'general manager',
        ], true);
    }

    public function isOwner(): bool
    {
        return $this->normalizedRole() === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->normalizedRole(), [
            'admin',
            'administrator',
            'super_admin',
            'super-admin',
            'super admin',
        ], true);
    }

    public function isFinancialController(): bool
    {
        return in_array($this->normalizedRole(), [
            'financial_controller',
            'financial-controller',
            'financial controller',
            'fc',
            'cost_controller',
            'cost-controller',
            'cost controller',
        ], true);
    }

    public function isCostController(): bool
    {
        return $this->isFinancialController();
    }

    public function isApprover(): bool
    {
        return in_array($this->normalizedRole(), [
            'purchasing',
            'accounting',
            'gm',
            'general_manager',
            'general-manager',
            'general manager',
            'owner',
            'admin',
            'administrator',
            'super_admin',
            'super-admin',
            'super admin',
            'financial_controller',
            'financial-controller',
            'financial controller',
            'fc',
            'cost_controller',
            'cost-controller',
            'cost controller',
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
