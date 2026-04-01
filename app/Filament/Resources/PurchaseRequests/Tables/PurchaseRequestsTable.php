<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Mail\PurchaseRequestSubmittedNotification;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label('PR')
                    ->searchable()
                    ->placeholder('Draft')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable()
                    ->limit(16)
                    ->tooltip(fn($record) => $record->requester_name)
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Request')
                    ->searchable()
                    ->limit(18)
                    ->tooltip(fn($record) => $record->title)
                    ->toggleable(),

                TextColumn::make('department_name')
                    ->label('Dept')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->color(fn(string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'normal' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'To Purchasing',
                        'revision_from_purchasing' => 'Rev. Purchasing',
                        'submitted_to_accounting' => 'To Accounting',
                        'revision_from_accounting' => 'Rev. Accounting',
                        'revision_to_purchasing_from_accounting' => 'Back to Purchasing',
                        'revision_to_requester_from_accounting' => 'Back to Requester',
                        'on_hold_by_accounting' => 'Hold Accounting',
                        'submitted_to_gm' => 'To GM',
                        'revision_from_gm' => 'Rev. GM',
                        'revision_to_purchasing_from_gm' => 'Back to Purchasing',
                        'revision_to_accounting_from_gm' => 'Back to Accounting',
                        'revision_to_requester_from_gm' => 'Back to Requester',
                        'on_hold_by_gm' => 'Hold GM',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                        null, '' => '-',
                        default => str($state)->replace('_', ' ')->title(),
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted',
                        'submitted_to_accounting',
                        'submitted_to_gm',
                        'revision_to_purchasing_from_accounting',
                        'revision_to_purchasing_from_gm',
                        'revision_to_accounting_from_gm' => 'warning',
                        'revision_from_purchasing',
                        'revision_from_accounting',
                        'revision_from_gm',
                        'revision_to_requester_from_accounting',
                        'revision_to_requester_from_gm',
                        'rejected',
                        'cancelled' => 'danger',
                        'on_hold_by_accounting',
                        'on_hold_by_gm' => 'gray',
                        'approved' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('current_desk')
                    ->label('Desk')
                    ->badge()
                    ->state(fn($record) => match ($record->status) {
                        'draft' => 'Requester',
                        'submitted' => 'Purchasing',
                        'revision_from_purchasing' => 'Requester',
                        'submitted_to_accounting' => 'Accounting',
                        'revision_from_accounting' => 'Requester',
                        'revision_to_purchasing_from_accounting' => 'Purchasing',
                        'revision_to_requester_from_accounting' => 'Requester',
                        'on_hold_by_accounting' => 'Accounting',
                        'submitted_to_gm' => 'GM',
                        'revision_from_gm' => 'Requester',
                        'revision_to_purchasing_from_gm' => 'Purchasing',
                        'revision_to_accounting_from_gm' => 'Accounting',
                        'revision_to_requester_from_gm' => 'Requester',
                        'on_hold_by_gm' => 'GM',
                        'approved' => 'Done',
                        'rejected' => 'Stopped',
                        'cancelled' => 'Cancelled',
                        default => '-',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Requester' => 'gray',
                        'Purchasing' => 'warning',
                        'Accounting' => 'info',
                        'GM' => 'danger',
                        'Done' => 'success',
                        'Stopped' => 'danger',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('viewForm')
                    ->label('View PR Form')
                    ->icon('heroicon-o-document-text')
                    ->url(fn(PurchaseRequest $record): string => route('purchase-requests.view-form', $record))
                    ->openUrlInNewTab(),

                Action::make('submitRequest')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(PurchaseRequest $record): bool => static::canShowSubmitAction($record))
                    ->action(fn(PurchaseRequest $record) => static::submitRequest($record)),

                ViewAction::make()
                    ->label('View')
                    ->visible(fn(PurchaseRequest $record): bool => $record->status === 'approved'),

                EditAction::make()
                    ->visible(fn(PurchaseRequest $record): bool => PurchaseRequestResource::canEdit($record)),
            ]);
    }

    protected static function getCurrentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function canShowSubmitAction(PurchaseRequest $record): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if (! ($user->isRequester() || $user->isAdmin())) {
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

        $record->status = 'submitted';
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
            'to_status' => 'submitted',
            'message' => 'Submitted by requester to Purchasing: ' . $record->requester_name,
            'meta' => [
                'request_number' => $record->request_number,
                'requester_name' => $record->requester_name,
                'department_name' => $record->department_name,
            ],
            'acted_at' => now(),
        ]);

        static::sendSubmittedEmailToPurchasing($record, $fromStatus);

        Notification::make()
            ->title('Purchase request submitted to Purchasing.')
            ->success()
            ->send();
    }

    protected static function sendSubmittedEmailToPurchasing(PurchaseRequest $record, string $fromStatus = 'draft'): void
    {
        $emails = config('mail.purchasing_notification_emails', []);

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(
            new PurchaseRequestSubmittedNotification($record, $fromStatus)
        );
    }

    protected static function generateRequestNumber(PurchaseRequest $record): string
    {
        $date = $record->created_at?->format('Ymd') ?? now()->format('Ymd');

        return 'PR-' . $date . '-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
    }
}
