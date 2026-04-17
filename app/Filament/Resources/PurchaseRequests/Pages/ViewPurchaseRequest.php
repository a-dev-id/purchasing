<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview Form')
                ->icon('heroicon-m-eye')
                ->color('gray')
                ->url(fn(): string => route('purchase-requests.view-form', [
                    'purchaseRequest' => $this->record,
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
