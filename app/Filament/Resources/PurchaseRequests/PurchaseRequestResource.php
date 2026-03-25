<?php

namespace App\Filament\Resources\PurchaseRequests;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
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

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;
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

        $user = Auth::user();

        if ($user instanceof User && $user->isPurchasing()) {
            return $query->whereIn('status', [
                'submitted_to_purchasing',
                'revision_from_purchasing',
                'submitted_to_accounting',
                'revision_from_accounting',
                'on_hold_by_accounting',
                'submitted_to_gm',
                'revision_from_gm',
                'on_hold_by_gm',
                'approved',
                'rejected',
            ]);
        }

        return $query;
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

        if ($user->canSeeAllPurchaseRequests()) {
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
            return $record->status === 'submitted';
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
            ], true);
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
