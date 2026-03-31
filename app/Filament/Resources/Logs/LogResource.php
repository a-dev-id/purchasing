<?php

namespace App\Filament\Resources\Logs;

use App\Filament\Resources\Logs\Pages\ListLogs;
use App\Filament\Resources\Logs\Schemas\LogForm;
use App\Filament\Resources\Logs\Tables\LogsTable;
use App\Models\PurchaseRequestLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LogResource extends Resource
{
    protected static ?string $model = PurchaseRequestLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Logs';

    protected static string | UnitEnum | null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'logs';

    protected static function currentUserIsAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->is_active
            && $user->role === 'admin';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return LogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLogs::route('/'),
        ];
    }
}
