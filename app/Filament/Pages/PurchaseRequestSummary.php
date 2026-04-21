<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PurchaseRequestSummary extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'PR Summary';

    protected static string | UnitEnum | null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Purchase Request Summary';

    protected ?string $heading = 'Purchase Request Summary';

    protected ?string $subheading = 'Printable summary for all purchase requests';

    protected string $view = 'filament.pages.purchase-request-summary';

    public static function canAccess(): bool
    {
        return Auth::user() instanceof User;
    }
}
