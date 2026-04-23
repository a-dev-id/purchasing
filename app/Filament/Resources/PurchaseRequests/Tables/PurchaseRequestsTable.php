<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                TextColumn::make('request_number')
                    ->label('PR')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Draft'),

                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(24),

                TextColumn::make('title')
                    ->label('Request')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(28),

                TextColumn::make('department_name')
                    ->label('Dept')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(20),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'urgent' => 'Urgent',
                        'normal' => 'Normal',
                        default => '-',
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'normal' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => static::statusLabel($state))
                    ->color(fn(?string $state): string => static::statusColor($state))
                    ->sortable(),

                TextColumn::make('desk')
                    ->label('Desk')
                    ->state(fn(PurchaseRequest $record): string => static::deskLabel($record->status))
                    ->badge()
                    ->color(fn(string $state): string => static::deskColor($state))
                    ->sortable(false),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('last_activity_at')
                    ->label('Last Update')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('open')
                    ->label(fn(PurchaseRequest $record): string => PurchaseRequestResource::canEdit($record) ? 'Edit' : 'View')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn(PurchaseRequest $record): string => PurchaseRequestResource::canEdit($record)
                        ? PurchaseRequestResource::getUrl('edit', ['record' => $record])
                        : PurchaseRequestResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No Purchase Requests')
            ->paginated([10, 25, 50]);
    }

    protected static function statusLabel(?string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'revision_from_purchasing' => 'Need Revision',
            'submitted_to_accounting' => 'To Accounting',
            'on_hold_by_accounting' => 'Hold Accounting',
            'revision_from_accounting' => 'Need Revision',
            'revision_to_purchasing_from_accounting' => 'Back to Purchasing',
            'revision_to_requester_from_accounting' => 'Back to Requester',
            'submitted_to_gm' => 'To GM',
            'on_hold_by_gm' => 'Hold GM',
            'revision_from_gm' => 'Need Revision',
            'revision_to_purchasing_from_gm' => 'Back to Purchasing',
            'revision_to_accounting_from_gm' => 'Back to Accounting',
            'revision_to_requester_from_gm' => 'Back to Requester',
            'gm_approved' => 'GM Approved',

            'pending',
            'pending_by_fc' => 'Pending',

            'on_progress',
            'on_progress_by_fc' => 'On Progress',

            'waiting_payment',
            'waiting_payment_by_fc' => 'Waiting Payment',

            'paid_to_vendor',
            'paid_to_vendor_by_fc' => 'Paid to Vendor',

            'on_shipping',
            'on_shipping_by_fc',
            'item_arrived_by_fc' => 'On Shipping',

            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'Done',

            'on_hold_by_fc' => 'Hold',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',

            default => str($state)->replace('_', ' ')->title()->toString(),
        };
    }

    protected static function statusColor(?string $state): string
    {
        return match ($state) {
            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'success',

            'paid_to_vendor',
            'paid_to_vendor_by_fc' => 'info',

            'on_shipping',
            'on_shipping_by_fc',
            'item_arrived_by_fc' => 'success',

            'submitted',
            'submitted_to_accounting',
            'submitted_to_gm',
            'gm_approved',
            'waiting_payment',
            'waiting_payment_by_fc' => 'warning',

            'cancelled',
            'rejected' => 'danger',

            default => 'gray',
        };
    }

    protected static function deskLabel(?string $state): string
    {
        return match ($state) {
            'draft',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm' => 'Requester',

            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'paid_to_vendor',
            'paid_to_vendor_by_fc',
            'on_shipping',
            'on_shipping_by_fc' => 'Purchasing',

            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm' => 'Accounting',

            'submitted_to_gm',
            'on_hold_by_gm' => 'GM',

            'gm_approved',
            'pending',
            'pending_by_fc',
            'on_progress',
            'on_progress_by_fc',
            'waiting_payment',
            'waiting_payment_by_fc',
            'item_arrived_by_fc',
            'on_hold_by_fc' => 'Financial Controller',

            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'Done',

            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',

            default => '-',
        };
    }

    protected static function deskColor(string $state): string
    {
        return match ($state) {
            'Requester' => 'gray',
            'Purchasing' => 'warning',
            'Accounting' => 'info',
            'GM' => 'warning',
            'Financial Controller' => 'success',
            'Done' => 'success',
            'Cancelled', 'Rejected' => 'danger',
            default => 'gray',
        };
    }
}
