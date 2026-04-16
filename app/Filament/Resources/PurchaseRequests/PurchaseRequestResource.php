<?php

namespace App\Filament\Resources\PurchaseRequests;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\PurchaseRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;
    protected static ?string $navigationLabel = 'Purchase Requests';
    protected static ?string $modelLabel = 'Purchase Request';
    protected static ?string $pluralModelLabel = 'Purchase Requests';
    protected static string | UnitEnum | null $navigationGroup = 'Purchasing';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseRequests::route('/'),
            'create' => CreatePurchaseRequest::route('/create'),
            'view' => ViewPurchaseRequest::route('/{record}'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
        ];
    }

    protected static function getCurrentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function getUserRole(?User $user): string
    {
        return strtolower(trim((string) ($user?->role ?? '')));
    }

    protected static function isAdminUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, ['admin', 'administrator', 'super_admin', 'super-admin'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isAdmin')
            && $user->isAdmin();
    }

    protected static function isOwnerUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, ['owner'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isOwner')
            && $user->isOwner();
    }

    protected static function isRequesterUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, ['requester'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isRequester')
            && $user->isRequester();
    }

    protected static function isPurchasingUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, ['purchasing'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isPurchasing')
            && $user->isPurchasing();
    }

    protected static function isAccountingUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, ['accounting'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isAccounting')
            && $user->isAccounting();
    }

    protected static function isGmUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, ['gm', 'general_manager', 'general-manager'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isGm')
            && $user->isGm();
    }

    protected static function isFinancialControllerUser(?User $user): bool
    {
        $role = static::getUserRole($user);

        if (in_array($role, [
            'financial_controller',
            'financial-controller',
            'financial controller',
            'fc',
            'cost_controller',
            'cost-controller',
            'cost controller',
        ], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isFinancialController')
            && $user->isFinancialController();
    }

    protected static function canSeeAllUserPurchaseRequests(?User $user): bool
    {
        return static::isAdminUser($user) || static::isOwnerUser($user);
    }

    protected static function purchasingVisibleStatuses(): array
    {
        return [
            'submitted',
            'revision_from_purchasing',
            'submitted_to_accounting',
            'revision_from_accounting',
            'revision_to_purchasing_from_accounting',
            'revision_to_requester_from_accounting',
            'on_hold_by_accounting',
            'submitted_to_gm',
            'revision_from_gm',
            'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm',
            'revision_to_requester_from_gm',
            'on_hold_by_gm',
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
            'approved',
            'rejected',
            'cancelled',
        ];
    }

    protected static function accountingVisibleStatuses(): array
    {
        return [
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_from_accounting',
            'revision_to_purchasing_from_accounting',
            'revision_to_requester_from_accounting',
            'submitted_to_gm',
            'revision_from_gm',
            'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm',
            'revision_to_requester_from_gm',
            'on_hold_by_gm',
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
            'approved',
            'rejected',
            'cancelled',
        ];
    }

    protected static function gmVisibleStatuses(): array
    {
        return [
            'submitted_to_gm',
            'on_hold_by_gm',
            'revision_from_gm',
            'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm',
            'revision_to_requester_from_gm',
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
            'approved',
            'rejected',
            'cancelled',
        ];
    }

    protected static function financialControllerVisibleStatuses(): array
    {
        return [
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
            'approved',
            'rejected',
            'cancelled',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }

        if (static::canSeeAllUserPurchaseRequests($user)) {
            return $query;
        }

        if (static::isRequesterUser($user)) {
            return $query->where('department_name', $user->department_name);
        }

        if (static::isPurchasingUser($user)) {
            return $query->whereIn('status', static::purchasingVisibleStatuses());
        }

        if (static::isAccountingUser($user)) {
            return $query->whereIn('status', static::accountingVisibleStatuses());
        }

        if (static::isGmUser($user)) {
            return $query->whereIn('status', static::gmVisibleStatuses());
        }

        if (static::isFinancialControllerUser($user)) {
            return $query->whereIn('status', static::financialControllerVisibleStatuses());
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = static::getCurrentUser();

        return $user?->is_active ?? false;
    }

    public static function canCreate(): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if (static::isAdminUser($user)) {
            return true;
        }

        return method_exists($user, 'canCreatePurchaseRequests')
            ? $user->canCreatePurchaseRequests()
            : false;
    }

    public static function canView(Model $record): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if (static::canSeeAllUserPurchaseRequests($user)) {
            return true;
        }

        if (static::isRequesterUser($user)) {
            return $record->department_name === $user->department_name;
        }

        if (static::isPurchasingUser($user)) {
            return in_array($record->status, static::purchasingVisibleStatuses(), true);
        }

        if (static::isAccountingUser($user)) {
            return in_array($record->status, static::accountingVisibleStatuses(), true);
        }

        if (static::isGmUser($user)) {
            return in_array($record->status, static::gmVisibleStatuses(), true);
        }

        if (static::isFinancialControllerUser($user)) {
            return in_array($record->status, static::financialControllerVisibleStatuses(), true);
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if (static::isAdminUser($user)) {
            return true;
        }

        if (static::isPurchasingUser($user)) {
            return in_array($record->status, [
                'submitted',
                'revision_to_purchasing_from_accounting',
                'revision_to_purchasing_from_gm',
            ], true);
        }

        if (static::isAccountingUser($user)) {
            return in_array($record->status, [
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'revision_to_accounting_from_gm',
                'gm_approved',
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
                'on_hold_by_fc',
            ], true);
        }

        if (static::isGmUser($user)) {
            return in_array($record->status, [
                'submitted_to_gm',
                'on_hold_by_gm',
            ], true);
        }

        if (static::isFinancialControllerUser($user)) {
            return in_array($record->status, [
                'gm_approved',
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
                'on_hold_by_fc',
            ], true);
        }

        if (static::isRequesterUser($user)) {
            if ($record->department_name !== $user->department_name) {
                return false;
            }

            return in_array($record->status, [
                'draft',
                'revision_from_purchasing',
                'revision_from_accounting',
                'revision_from_gm',
                'revision_to_requester_from_accounting',
                'revision_to_requester_from_gm',
            ], true);
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
