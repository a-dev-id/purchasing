<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label('PR No.')
                    ->searchable()
                    ->placeholder('Draft'),

                TextColumn::make('requester_name')
                    ->label('Requester Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Request Name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('department_name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->color(fn(string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'normal' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Waiting Purchasing',
                        'submitted_to_purchasing' => 'Waiting Purchasing',
                        'revision_from_purchasing' => 'Revision from Purchasing',
                        'submitted_to_accounting' => 'Waiting Accounting',
                        'revision_from_accounting' => 'Revision from Accounting',
                        'on_hold_by_accounting' => 'On Hold by Accounting',
                        'submitted_to_gm' => 'Waiting GM',
                        'revision_from_gm' => 'Revision from GM',
                        'on_hold_by_gm' => 'On Hold by GM',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        null, '' => '-',
                        default => str($state)->replace('_', ' ')->title(),
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted',
                        'submitted_to_purchasing',
                        'submitted_to_accounting',
                        'submitted_to_gm' => 'warning',
                        'revision_from_purchasing',
                        'revision_from_accounting',
                        'revision_from_gm',
                        'rejected' => 'danger',
                        'on_hold_by_accounting',
                        'on_hold_by_gm' => 'gray',
                        'approved' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('current_desk')
                    ->label('Current Desk')
                    ->badge()
                    ->state(fn($record) => match ($record->status) {
                        'draft' => 'Requester',
                        'submitted' => 'Purchasing',
                        'submitted_to_purchasing' => 'Purchasing',
                        'revision_from_purchasing' => 'Requester',
                        'submitted_to_accounting' => 'Accounting',
                        'revision_from_accounting' => 'Requester',
                        'on_hold_by_accounting' => 'Accounting',
                        'submitted_to_gm' => 'GM',
                        'revision_from_gm' => 'Requester',
                        'on_hold_by_gm' => 'GM',
                        'approved' => 'Completed',
                        'rejected' => 'Stopped',
                        default => '-',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Requester' => 'gray',
                        'Purchasing' => 'warning',
                        'Accounting' => 'info',
                        'GM' => 'danger',
                        'Completed' => 'success',
                        'Stopped' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('submitRequest')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(PurchaseRequest $record): bool => PurchaseRequestResource::canEdit($record))
                    ->action(fn(PurchaseRequest $record) => static::submitRequest($record)),

                EditAction::make()
                    ->visible(fn(PurchaseRequest $record): bool => PurchaseRequestResource::canEdit($record)),
            ]);
    }

    protected static function getCurrentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function submitRequest(PurchaseRequest $record): void
    {
        if ($record->items()->count() === 0) {
            Notification::make()
                ->title('Add at least one item before submitting.')
                ->danger()
                ->send();

            return;
        }

        $fromStatus = $record->status;

        if (blank($record->request_number)) {
            $record->request_number = static::generateRequestNumber($record);
        }

        $record->status = 'submitted_to_purchasing';
        $record->current_status_at = now();

        if (blank($record->submitted_at)) {
            $record->submitted_at = now();
        }

        $record->save();

        $user = static::getCurrentUser();

        PurchaseRequestLog::create([
            'purchase_request_id' => $record->id,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'role_name' => $user?->role,
            'action' => 'submitted',
            'from_status' => $fromStatus,
            'to_status' => 'submitted_to_purchasing',
            'message' => 'Submitted by requester to Purchasing: ' . $record->requester_name,
            'meta' => [
                'request_number' => $record->request_number,
                'requester_name' => $record->requester_name,
                'department_name' => $record->department_name,
            ],
            'acted_at' => now(),
        ]);

        Notification::make()
            ->title('Purchase request submitted to Purchasing.')
            ->success()
            ->send();
    }

    protected static function generateRequestNumber(PurchaseRequest $record): string
    {
        $date = $record->created_at?->format('Ymd') ?? now()->format('Ymd');

        return 'PR-' . $date . '-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
    }
}
