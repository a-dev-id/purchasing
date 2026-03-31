<?php

namespace App\Filament\Resources\Logs\Tables;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['purchaseRequest', 'user']))
            ->defaultSort('acted_at', 'desc')
            ->columns([
                TextColumn::make('acted_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('purchaseRequest.request_number')
                    ->label('PR No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchaseRequest.title')
                    ->label('Request Name')
                    ->searchable()
                    ->wrap()
                    ->limit(40),

                TextColumn::make('purchaseRequest.department_name')
                    ->label('Department')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role_name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? str($state)->replace('_', ' ')->title() : '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_name')
                    ->label('By')
                    ->formatStateUsing(fn(?string $state) => $state ?: 'System')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? str($state)->replace('_', ' ')->title() : '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('from_status')
                    ->label('From')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? str($state)->replace('_', ' ')->title() : '-')
                    ->toggleable(),

                TextColumn::make('to_status')
                    ->label('To')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? str($state)->replace('_', ' ')->title() : '-')
                    ->toggleable(),

                TextColumn::make('message')
                    ->searchable()
                    ->wrap()
                    ->limit(90),

                TextColumn::make('meta')
                    ->label('Meta')
                    ->formatStateUsing(function ($state): string {
                        if (blank($state)) {
                            return '-';
                        }

                        if (is_array($state)) {
                            return json_encode(
                                $state,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            );
                        }

                        return (string) $state;
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role_name')
                    ->label('Role')
                    ->options([
                        'requester' => 'Requester',
                        'purchasing' => 'Purchasing',
                        'accounting' => 'Accounting',
                        'gm' => 'GM',
                        'owner' => 'Owner',
                        'admin' => 'Admin',
                        'system' => 'System',
                    ]),

                SelectFilter::make('action')
                    ->options([
                        'submitted' => 'Submitted',
                        'submitted_to_accounting' => 'Submitted to Accounting',
                        'submitted_to_gm' => 'Submitted to GM',
                        'revision_from_purchasing' => 'Returned by Purchasing',
                        'revision_from_accounting' => 'Returned by Accounting',
                        'revision_from_gm' => 'Returned by GM',
                        'revision_to_purchasing_from_accounting' => 'Returned to Purchasing from Accounting',
                        'revision_to_requester_from_accounting' => 'Returned to Requester from Accounting',
                        'revision_to_accounting_from_gm' => 'Returned to Accounting from GM',
                        'revision_to_purchasing_from_gm' => 'Returned to Purchasing from GM',
                        'revision_to_requester_from_gm' => 'Returned to Requester from GM',
                        'on_hold_by_accounting' => 'On Hold by Accounting',
                        'on_hold_by_gm' => 'On Hold by GM',
                        'approved' => 'Approved',
                        'cancelled' => 'Cancelled',
                        'inactivity_reminder_sent' => 'Inactivity Reminder Sent',
                    ]),

                SelectFilter::make('department')
                    ->label('Department')
                    ->relationship('purchaseRequest', 'department_name'),
            ])
            ->recordActions([
                Action::make('open_pr')
                    ->label('Open PR')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn($record) => $record->purchaseRequest
                        ? PurchaseRequestResource::getUrl('edit', ['record' => $record->purchaseRequest])
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => filled($record->purchaseRequest)),
            ])
            ->toolbarActions([]);
    }
}
