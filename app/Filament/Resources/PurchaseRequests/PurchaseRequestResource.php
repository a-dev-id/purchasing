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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isRequester()) {
            return $query->where('department_name', $user->department_name);
        }

        if ($user->isPurchasing()) {
            return $query->whereIn('status', [
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
                'approved',
                'rejected',
            ]);
        }

        if ($user->isAccounting()) {
            return $query->whereIn('status', [
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
                'approved',
                'rejected',
            ]);
        }

        if ($user->isGm()) {
            return $query->whereIn('status', [
                'submitted_to_gm',
                'on_hold_by_gm',
                'revision_from_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'revision_to_requester_from_gm',
                'approved',
                'rejected',
            ]);
        }

        if ($user->canSeeAllPurchaseRequests()) {
            return $query;
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

        return ($user?->is_active ?? false)
            && $user->canCreatePurchaseRequests();
    }

    public static function canView(Model $record): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if (
            $user->isAdmin() ||
            $user->isPurchasing() ||
            $user->isAccounting() ||
            $user->isGm() ||
            $user->isOwner() ||
            $user->canSeeAllPurchaseRequests()
        ) {
            return true;
        }

        return $record->department_name === $user->department_name;
    }

    public static function canEdit(Model $record): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPurchasing()) {
            return in_array($record->status, [
                'submitted',
                'revision_to_purchasing_from_accounting',
                'revision_to_purchasing_from_gm',
            ], true);
        }

        if ($user->isAccounting()) {
            return in_array($record->status, [
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'revision_to_accounting_from_gm',
            ], true);
        }

        if ($user->isGm()) {
            return in_array($record->status, [
                'submitted_to_gm',
                'on_hold_by_gm',
            ], true);
        }

        if ($user->isRequester()) {
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
