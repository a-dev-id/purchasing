<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Mail\PurchaseRequestApprovedNotification;
use App\Mail\PurchaseRequestReturnedToRequesterNotification;
use App\Mail\PurchaseRequestSubmittedNotification;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitRequest')
                ->label('Submit')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->requiresConfirmation()
                ->visible(fn() => $this->canRequesterSubmit())
                ->action(function () {
                    $fromStatus = $this->record->status;

                    if (blank($this->record->request_number)) {
                        $this->record->request_number = $this->generateRequestNumber($this->record);
                    }

                    if (blank($this->record->submitted_at)) {
                        $this->record->submitted_at = now();
                    }

                    $this->record->status = 'submitted';
                    $this->record->submitted_at = $this->record->submitted_at ?: now();
                    $this->record->save();

                    $this->record->markActivity();

                    $this->record->logs()->create([
                        'user_id' => Auth::id(),
                        'action' => 'submitted',
                        'message' => 'Purchase request submitted to Purchasing.',
                    ]);

                    $this->sendSubmittedEmailToPurchasing($this->record, $fromStatus);

                    Notification::make()
                        ->success()
                        ->title('Purchase request submitted.')
                        ->send();

                    $this->refreshFormData([
                        'request_number',
                        'submitted_at',
                        'status',
                        'current_status_at',
                        'last_activity_at',
                        'last_reminder_sent_at',
                    ]);
                }),

            Action::make('cancelRequest')
                ->label('Cancel PR')
                ->color('danger')
                ->icon('heroicon-m-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Cancel Purchase Request')
                ->modalDescription('This purchase request will be marked as cancelled and kept for history.')
                ->visible(fn() => $this->canCancelRequest())
                ->action(function () {
                    $this->record->status = 'cancelled';
                    $this->record->cancelled_at = now();
                    $this->record->save();

                    $this->record->markActivity();

                    $this->record->logs()->create([
                        'user_id' => Auth::id(),
                        'action' => 'cancelled',
                        'message' => 'Purchase request cancelled.',
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Purchase request cancelled.')
                        ->send();

                    $this->redirect(PurchaseRequestResource::getUrl('index'));
                }),

            Action::make('submitToAccounting')
                ->label('Submit to Accounting')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->requiresConfirmation()
                ->visible(fn() => $this->canPurchasingSubmitToAccounting())
                ->action(function () {
                    $this->record->status = 'submitted_to_accounting';
                    $this->record->save();

                    $this->record->markActivity();

                    $this->record->logs()->create([
                        'user_id' => Auth::id(),
                        'action' => 'submitted_to_accounting',
                        'message' => 'Purchase request submitted to Accounting.',
                    ]);

                    $this->sendSubmittedEmailToAccounting(
                        $this->record,
                        'submitted_to_accounting'
                    );

                    Notification::make()
                        ->success()
                        ->title('Purchase request submitted to Accounting.')
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'current_status_at',
                        'last_activity_at',
                        'last_reminder_sent_at',
                    ]);
                }),

            ActionGroup::make([
                Action::make('returnToRequesterFromPurchasing')
                    ->label('Return to Requester')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Textarea::make('message')
                            ->label('Return Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'revision_from_purchasing';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'revision_from_purchasing',
                            'message' => $data['message'],
                        ]);

                        $this->sendReturnedToRequesterEmail(
                            $this->record,
                            $data['message'],
                            'Purchasing'
                        );

                        Notification::make()
                            ->success()
                            ->title('Purchase request returned to Requester.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),
            ])
                ->label('Purchasing Actions')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->visible(fn() => $this->canPurchasingReject()),

            Action::make('submitToGm')
                ->label('Submit to GM')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->requiresConfirmation()
                ->visible(fn() => $this->canAccountingSubmitToGm())
                ->action(function () {
                    $this->record->status = 'submitted_to_gm';
                    $this->record->save();

                    $this->record->markActivity();

                    $this->record->logs()->create([
                        'user_id' => Auth::id(),
                        'action' => 'submitted_to_gm',
                        'message' => 'Purchase request submitted to GM.',
                    ]);

                    $this->sendSubmittedEmailToGm(
                        $this->record,
                        'submitted_to_gm'
                    );

                    Notification::make()
                        ->success()
                        ->title('Purchase request submitted to GM.')
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'current_status_at',
                        'last_activity_at',
                        'last_reminder_sent_at',
                    ]);
                }),

            ActionGroup::make([
                Action::make('holdByAccounting')
                    ->label('Hold')
                    ->icon('heroicon-m-pause-circle')
                    ->color('gray')
                    ->form([
                        Textarea::make('message')
                            ->label('Hold Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'on_hold_by_accounting';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'on_hold_by_accounting',
                            'message' => $data['message'],
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request placed on hold.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('returnToPurchasingFromAccounting')
                    ->label('Return to Purchasing')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->form([
                        Textarea::make('message')
                            ->label('Return Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'revision_to_purchasing_from_accounting';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'revision_to_purchasing_from_accounting',
                            'message' => $data['message'],
                        ]);

                        $this->sendSubmittedEmailToPurchasing(
                            $this->record,
                            'revision_to_purchasing_from_accounting'
                        );

                        Notification::make()
                            ->success()
                            ->title('Purchase request returned to Purchasing.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('returnToRequesterFromAccounting')
                    ->label('Return to Requester')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Textarea::make('message')
                            ->label('Return Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'revision_to_requester_from_accounting';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'revision_to_requester_from_accounting',
                            'message' => $data['message'],
                        ]);

                        $this->sendReturnedToRequesterEmail(
                            $this->record,
                            $data['message'],
                            'Accounting'
                        );

                        Notification::make()
                            ->success()
                            ->title('Purchase request returned to Requester.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),
            ])
                ->label('Accounting Actions')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->visible(fn() => $this->canAccountingReturn()),

            Action::make('approveByGm')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->requiresConfirmation()
                ->visible(fn() => $this->canGmApprove())
                ->action(function () {
                    $this->record->status = 'approved';
                    $this->record->approved_at = now();
                    $this->record->save();

                    $this->record->markActivity();

                    $this->record->logs()->create([
                        'user_id' => Auth::id(),
                        'action' => 'approved',
                        'message' => 'Purchase request approved by GM.',
                    ]);

                    $this->sendApprovedEmailToEveryone($this->record);

                    Notification::make()
                        ->success()
                        ->title('Purchase request approved.')
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'approved_at',
                        'current_status_at',
                        'last_activity_at',
                        'last_reminder_sent_at',
                    ]);
                }),

            ActionGroup::make([
                Action::make('holdByGm')
                    ->label('Hold')
                    ->icon('heroicon-m-pause-circle')
                    ->color('gray')
                    ->form([
                        Textarea::make('message')
                            ->label('Hold Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'on_hold_by_gm';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'on_hold_by_gm',
                            'message' => $data['message'],
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request placed on hold.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('returnToAccountingFromGm')
                    ->label('Return to Accounting')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->form([
                        Textarea::make('message')
                            ->label('Return Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'revision_to_accounting_from_gm';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'revision_to_accounting_from_gm',
                            'message' => $data['message'],
                        ]);

                        $this->sendSubmittedEmailToAccounting(
                            $this->record,
                            'revision_to_accounting_from_gm'
                        );

                        Notification::make()
                            ->success()
                            ->title('Purchase request returned to Accounting.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('returnToPurchasingFromGm')
                    ->label('Return to Purchasing')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->form([
                        Textarea::make('message')
                            ->label('Return Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'revision_to_purchasing_from_gm';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'revision_to_purchasing_from_gm',
                            'message' => $data['message'],
                        ]);

                        $this->sendSubmittedEmailToPurchasing(
                            $this->record,
                            'revision_to_purchasing_from_gm'
                        );

                        Notification::make()
                            ->success()
                            ->title('Purchase request returned to Purchasing.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('returnToRequesterFromGm')
                    ->label('Return to Requester')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Textarea::make('message')
                            ->label('Return Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'revision_to_requester_from_gm';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'revision_to_requester_from_gm',
                            'message' => $data['message'],
                        ]);

                        $this->sendReturnedToRequesterEmail(
                            $this->record,
                            $data['message'],
                            'GM'
                        );

                        Notification::make()
                            ->success()
                            ->title('Purchase request returned to Requester.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),
            ])
                ->label('GM Actions')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->visible(fn() => $this->canGmReturn()),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->touchActivityOnly();

        $this->syncItemsToMasterCatalog();
        $this->syncVendorsToMasterCatalog();

        $this->refreshFormData([
            'current_status_at',
            'last_activity_at',
            'last_reminder_sent_at',
        ]);
    }

    protected function syncItemsToMasterCatalog(): void
    {
        $this->record->load('items');

        foreach ($this->record->items as $purchaseRequestItem) {
            if ($purchaseRequestItem->item_id) {
                $existingItem = Item::find($purchaseRequestItem->item_id);

                if ($existingItem) {
                    $existingItem->update([
                        'default_unit' => $purchaseRequestItem->unit ?: $existingItem->default_unit,
                        'default_specification' => $purchaseRequestItem->specification ?: $existingItem->default_specification,
                    ]);
                }

                continue;
            }

            $itemName = trim((string) $purchaseRequestItem->item_name);

            if ($itemName === '') {
                continue;
            }

            $item = Item::firstOrCreate(
                [
                    'name' => $itemName,
                ],
                [
                    'default_unit' => $purchaseRequestItem->unit,
                    'default_specification' => $purchaseRequestItem->specification,
                    'currency' => 'IDR',
                    'is_active' => true,
                ]
            );

            if (! $item->default_unit && $purchaseRequestItem->unit) {
                $item->default_unit = $purchaseRequestItem->unit;
            }

            if (! $item->default_specification && $purchaseRequestItem->specification) {
                $item->default_specification = $purchaseRequestItem->specification;
            }

            $item->save();

            $purchaseRequestItem->update([
                'item_id' => $item->id,
            ]);
        }
    }

    protected function syncVendorsToMasterCatalog(): void
    {
        $this->record->load('vendorOffers');

        foreach ($this->record->vendorOffers as $vendorOffer) {
            if ($vendorOffer->vendor_id) {
                $existingVendor = Vendor::find($vendorOffer->vendor_id);

                if ($existingVendor) {
                    $existingVendor->update([
                        'contact_person' => $vendorOffer->contact_person ?: $existingVendor->contact_person,
                        'phone' => $vendorOffer->phone ?: $existingVendor->phone,
                        'email' => $vendorOffer->email ?: $existingVendor->email,
                    ]);
                }

                continue;
            }

            $vendorName = trim((string) $vendorOffer->vendor_name);

            if ($vendorName === '') {
                continue;
            }

            $vendor = Vendor::firstOrCreate(
                [
                    'name' => $vendorName,
                ],
                [
                    'contact_person' => $vendorOffer->contact_person,
                    'phone' => $vendorOffer->phone,
                    'email' => $vendorOffer->email,
                    'is_active' => true,
                ]
            );

            if (! $vendor->contact_person && $vendorOffer->contact_person) {
                $vendor->contact_person = $vendorOffer->contact_person;
            }

            if (! $vendor->phone && $vendorOffer->phone) {
                $vendor->phone = $vendorOffer->phone;
            }

            if (! $vendor->email && $vendorOffer->email) {
                $vendor->email = $vendorOffer->email;
            }

            $vendor->save();

            $vendorOffer->update([
                'vendor_id' => $vendor->id,
            ]);
        }
    }

    protected function sendSubmittedEmailToPurchasing(PurchaseRequest $purchaseRequest, string $fromStatus = 'draft'): void
    {
        $emails = config('mail.purchasing_notification_emails', []);

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(
            new PurchaseRequestSubmittedNotification($purchaseRequest, $fromStatus)
        );
    }

    protected function sendSubmittedEmailToAccounting(PurchaseRequest $purchaseRequest, string $fromStatus = 'submitted_to_accounting'): void
    {
        $emails = config('mail.accounting_notification_emails', []);

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(
            new PurchaseRequestSubmittedNotification($purchaseRequest, $fromStatus)
        );
    }

    protected function sendSubmittedEmailToGm(PurchaseRequest $purchaseRequest, string $fromStatus = 'submitted_to_gm'): void
    {
        $emails = config('mail.gm_notification_emails', []);

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(
            new PurchaseRequestSubmittedNotification($purchaseRequest, $fromStatus)
        );
    }

    protected function sendApprovedEmailToEveryone(PurchaseRequest $purchaseRequest): void
    {
        $requesterEmails = User::query()
            ->where('role', 'requester')
            ->where('is_active', true)
            ->where('department_name', $purchaseRequest->department_name)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->map(fn($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $emails = collect([
            ...$requesterEmails,
            ...config('mail.purchasing_notification_emails', []),
            ...config('mail.accounting_notification_emails', []),
            ...config('mail.gm_notification_emails', []),
            ...config('mail.owner_notification_emails', []),
        ])
            ->filter()
            ->map(fn($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(
            new PurchaseRequestApprovedNotification($purchaseRequest)
        );
    }

    protected function sendReturnedToRequesterEmail(
        PurchaseRequest $purchaseRequest,
        ?string $messageText = null,
        string $returnedByLabel = 'Purchasing'
    ): void {
        $emails = User::query()
            ->where('role', 'requester')
            ->where('is_active', true)
            ->where('department_name', $purchaseRequest->department_name)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->map(fn($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(
            new PurchaseRequestReturnedToRequesterNotification(
                $purchaseRequest,
                $messageText,
                $returnedByLabel
            )
        );
    }

    protected function getCurrentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected function canRequesterSubmit(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isRequester() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'draft',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ], true);
    }

    protected function canCancelRequest(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return ! in_array($this->record->status, [
                'approved',
                'rejected',
                'cancelled',
            ], true);
        }

        if ($user->isRequester()) {
            return in_array($this->record->status, [
                'draft',
                'revision_from_purchasing',
                'revision_from_accounting',
                'revision_from_gm',
                'revision_to_requester_from_accounting',
                'revision_to_requester_from_gm',
            ], true);
        }

        return false;
    }

    protected function canPurchasingReject(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isPurchasing() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ], true);
    }

    protected function canPurchasingSubmitToAccounting(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isPurchasing() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ], true);
    }

    protected function canAccountingSubmitToGm(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isAccounting() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm',
        ], true);
    }

    protected function canAccountingReturn(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isAccounting() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm',
        ], true);
    }

    protected function canGmApprove(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isGm() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted_to_gm',
            'on_hold_by_gm',
        ], true);
    }

    protected function canGmHold(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isGm() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted_to_gm',
            'on_hold_by_gm',
        ], true);
    }

    protected function canGmReturn(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isGm() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'submitted_to_gm',
            'on_hold_by_gm',
        ], true);
    }

    protected function generateRequestNumber(PurchaseRequest $record): string
    {
        $date = $record->created_at?->format('Ymd') ?? now()->format('Ymd');

        return 'PR-' . $date . '-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Purchase request updated';
    }
}
