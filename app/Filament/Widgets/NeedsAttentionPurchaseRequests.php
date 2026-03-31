<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class NeedsAttentionPurchaseRequests extends TableWidget
{
    protected static ?string $heading = 'Needs Attention';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('request_number')
                    ->label('PR')
                    ->placeholder('Draft')
                    ->searchable(),

                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn(PurchaseRequest $record) => $record->requester_name),

                TextColumn::make('department_name')
                    ->label('Department')
                    ->badge()
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Request')
                    ->searchable()
                    ->wrap()
                    ->limit(40)
                    ->tooltip(fn(PurchaseRequest $record) => $record->title),

                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? ucfirst($state) : '-')
                    ->color(fn(?string $state) => match ($state) {
                        'urgent' => 'danger',
                        'normal' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'submitted' => 'To Purchasing',
                        'revision_from_purchasing' => 'Back to Requester',
                        'revision_from_accounting' => 'Back to Requester',
                        'revision_from_gm' => 'Back to Requester',
                        'revision_to_purchasing_from_accounting' => 'Back to Purchasing',
                        'revision_to_requester_from_accounting' => 'Back to Requester',
                        'submitted_to_accounting' => 'To Accounting',
                        'on_hold_by_accounting' => 'Hold Accounting',
                        'submitted_to_gm' => 'To GM',
                        'revision_to_purchasing_from_gm' => 'Back to Purchasing',
                        'revision_to_accounting_from_gm' => 'Back to Accounting',
                        'revision_to_requester_from_gm' => 'Back to Requester',
                        'on_hold_by_gm' => 'Hold GM',
                        default => $state ? str($state)->replace('_', ' ')->title() : '-',
                    })
                    ->color(fn(?string $state) => match ($state) {
                        'submitted',
                        'revision_to_purchasing_from_accounting',
                        'revision_to_purchasing_from_gm' => 'warning',

                        'submitted_to_accounting',
                        'on_hold_by_accounting',
                        'revision_to_accounting_from_gm' => 'info',

                        'submitted_to_gm',
                        'on_hold_by_gm' => 'danger',

                        'revision_from_purchasing',
                        'revision_from_accounting',
                        'revision_from_gm',
                        'revision_to_requester_from_accounting',
                        'revision_to_requester_from_gm' => 'gray',

                        default => 'gray',
                    }),

                TextColumn::make('current_desk')
                    ->label('Desk')
                    ->badge()
                    ->state(fn(PurchaseRequest $record) => match ($record->status) {
                        'submitted',
                        'revision_to_purchasing_from_accounting',
                        'revision_to_purchasing_from_gm' => 'Purchasing',

                        'submitted_to_accounting',
                        'on_hold_by_accounting',
                        'revision_to_accounting_from_gm' => 'Accounting',

                        'submitted_to_gm',
                        'on_hold_by_gm' => 'GM',

                        'revision_from_purchasing',
                        'revision_from_accounting',
                        'revision_from_gm',
                        'revision_to_requester_from_accounting',
                        'revision_to_requester_from_gm' => 'Requester',

                        default => '-',
                    })
                    ->color(fn(string $state) => match ($state) {
                        'Purchasing' => 'warning',
                        'Accounting' => 'info',
                        'GM' => 'danger',
                        'Requester' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('waiting_days')
                    ->label('Days Waiting')
                    ->state(function (PurchaseRequest $record): int {
                        $since = $record->last_activity_at
                            ?? $record->current_status_at
                            ?? $record->submitted_at
                            ?? $record->created_at;

                        return $since ? $since->diffInDays(now()) : 0;
                    })
                    ->badge()
                    ->color(function (int $state): string {
                        if ($state >= 6) {
                            return 'danger';
                        }

                        if ($state >= 3) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open PR')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn(PurchaseRequest $record) => PurchaseRequestResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No PR needs attention right now');
    }

    protected function getTableQuery(): Builder
    {
        return PurchaseRequest::query()
            ->whereIn('status', [
                'submitted',
                'revision_from_purchasing',
                'revision_from_accounting',
                'revision_from_gm',
                'revision_to_purchasing_from_accounting',
                'revision_to_requester_from_accounting',
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'submitted_to_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'revision_to_requester_from_gm',
                'on_hold_by_gm',
            ])
            ->orderByRaw("
                CASE
                    WHEN priority = 'urgent' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('last_activity_at')
            ->orderByDesc('id');
    }
}
