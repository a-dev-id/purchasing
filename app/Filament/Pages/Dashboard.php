<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\NeedsAttentionPurchaseRequests;
use App\Filament\Widgets\PurchasingOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            PurchasingOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            NeedsAttentionPurchaseRequests::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 5,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }
}
