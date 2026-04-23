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
            Action::make('viewPrForm')
                ->label('View PR Form')
                ->color('gray')
                ->icon('heroicon-m-eye')
                ->url(fn() => route('purchase-requests.view-form', $this->record))
                ->openUrlInNewTab(),

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
                    ->visible(fn() => $this->canPurchasingReject())
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

                Action::make('markOnShippingByPurchasing')
                    ->label('On Shipping')
                    ->icon('heroicon-m-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn() => $this->canPurchasingUpdateAfterPaidToVendor()
                        && in_array($this->record->status, ['paid_to_vendor', 'on_shipping'], true))
                    ->action(fn() => $this->updatePurchasingDeliveryStatus(
                        status: 'on_shipping',
                        action: 'on_shipping',
                        successTitle: 'Purchase request marked as on shipping.',
                        message: 'The PR is Paid to vendor by Financial Controller and On Shipping.',
                    )),

                Action::make('markReceivedByRequesterByPurchasing')
                    ->label('Received by Requester')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn() => $this->canPurchasingUpdateAfterPaidToVendor()
                        && in_array($this->record->status, ['paid_to_vendor', 'on_shipping'], true))
                    ->form([
                        \Filament\Forms\Components\DateTimePicker::make('received_at')
                            ->label('Received At')
                            ->required()
                            ->seconds(false)
                            ->default(now())
                            ->displayFormat('d M Y H:i')
                            ->native(false),

                        \Filament\Forms\Components\TextInput::make('receiver_name')
                            ->label('Receiver Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Input the person who takes the item from Purchasing'),

                        Textarea::make('message')
                            ->label('Received Note')
                            ->placeholder('Write the handover / receiving note for this PR.')
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $receiverName = trim((string) ($data['receiver_name'] ?? ''));
                        $receivedAt = trim((string) ($data['received_at'] ?? ''));
                        $extraNote = trim((string) ($data['message'] ?? ''));

                        $receivedDateLabel = $receivedAt !== ''
                            ? \Carbon\Carbon::parse($receivedAt)->format('Y-m-d')
                            : now()->format('Y-m-d');

                        $message = "The item was received by {$receiverName} on {$receivedDateLabel}.";

                        if ($extraNote !== '') {
                            $message .= " Note: {$extraNote}";
                        }

                        $this->updatePurchasingDeliveryStatus(
                            status: 'received_by_requester',
                            action: 'received_by_requester',
                            successTitle: 'Purchase request marked as received by requester.',
                            message: $message,
                            receivedAt: $receivedAt,
                            receiverName: $receiverName,
                        );
                    }),
            ])
                ->label('Purchasing Actions')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->visible(fn() => $this->canPurchasingReject() || $this->canPurchasingUpdateAfterPaidToVendor()),

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
                    $this->record->status = 'gm_approved';
                    $this->record->approved_at = now();
                    $this->record->save();

                    $this->record->markActivity();

                    $this->record->logs()->create([
                        'user_id' => Auth::id(),
                        'action' => 'gm_approved',
                        'message' => 'Purchase request approved by GM and handed over to Financial Controller.',
                    ]);

                    $this->sendSubmittedEmailToFinancialController($this->record, 'gm_approved');

                    Notification::make()
                        ->success()
                        ->title('Purchase request approved by GM and handed over to Financial Controller.')
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

            ActionGroup::make([
                $this->makeFinancialControllerAction(
                    name: 'markPendingByFc',
                    label: 'Pending',
                    icon: 'heroicon-m-clock',
                    color: 'gray',
                    status: 'pending',
                    visibleStatuses: ['gm_approved', 'pending', 'on_progress', 'waiting_payment', 'on_hold_by_fc'],
                    noteLabel: 'Pending Note',
                    notePlaceholder: 'Write why this PR is still pending.',
                    noteRequired: true,
                ),

                $this->makeFinancialControllerAction(
                    name: 'markOnProgressByFc',
                    label: 'On Progress',
                    icon: 'heroicon-m-arrow-path',
                    color: 'info',
                    status: 'on_progress',
                    visibleStatuses: ['gm_approved', 'pending', 'on_progress', 'waiting_payment', 'paid_to_vendor', 'on_hold_by_fc'],
                    defaultMessage: 'Purchase request marked as on progress by Financial Controller.',
                ),

                $this->makeFinancialControllerAction(
                    name: 'markWaitingPaymentByFc',
                    label: 'Waiting Payment',
                    icon: 'heroicon-m-credit-card',
                    color: 'warning',
                    status: 'waiting_payment',
                    visibleStatuses: ['gm_approved', 'pending', 'on_progress', 'waiting_payment', 'on_hold_by_fc'],
                    defaultMessage: 'Purchase request marked as waiting payment by Financial Controller.',
                ),

                $this->makeFinancialControllerAction(
                    name: 'markPaidToVendorByFc',
                    label: 'Paid to Vendor',
                    icon: 'heroicon-m-banknotes',
                    color: 'info',
                    status: 'paid_to_vendor',
                    visibleStatuses: ['waiting_payment', 'on_progress', 'pending', 'paid_to_vendor', 'on_hold_by_fc'],
                    defaultMessage: 'Purchase request marked as paid to vendor by Financial Controller.',
                ),

                $this->makeFinancialControllerAction(
                    name: 'holdByFc',
                    label: 'Hold',
                    icon: 'heroicon-m-pause-circle',
                    color: 'gray',
                    status: 'on_hold_by_fc',
                    visibleStatuses: ['gm_approved', 'pending', 'on_progress', 'waiting_payment', 'paid_to_vendor', 'on_shipping', 'on_hold_by_fc'],
                    noteLabel: 'Hold Note',
                    notePlaceholder: 'Write why this PR is on hold.',
                    noteRequired: true,
                ),

                $this->makeFinancialControllerAction(
                    name: 'cancelByFc',
                    label: 'Cancel',
                    icon: 'heroicon-m-x-circle',
                    color: 'danger',
                    status: 'cancelled',
                    visibleStatuses: ['gm_approved', 'pending', 'on_progress', 'waiting_payment', 'paid_to_vendor', 'on_shipping', 'on_hold_by_fc'],
                    noteLabel: 'Cancel Note',
                    notePlaceholder: 'Write why this PR is cancelled.',
                    noteRequired: true,
                    setCancelledAt: true,
                ),
            ])
                ->label('FC Actions')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->visible(fn() => $this->canShowFinancialControllerActions()),
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
        $this->record->load([
            'items.photos',
        ]);

        foreach ($this->record->items as $purchaseRequestItem) {
            $itemName = trim((string) $purchaseRequestItem->item_name);

            if ($itemName === '') {
                continue;
            }

            $item = null;

            if ($purchaseRequestItem->item_id) {
                $item = Item::find($purchaseRequestItem->item_id);
            }

            if (! $item) {
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
            }

            $itemUpdates = [];

            if (blank($item->default_unit) && filled($purchaseRequestItem->unit)) {
                $itemUpdates['default_unit'] = $purchaseRequestItem->unit;
            }

            if (blank($item->default_specification) && filled($purchaseRequestItem->specification)) {
                $itemUpdates['default_specification'] = $purchaseRequestItem->specification;
            }

            if (! $item->is_active) {
                $itemUpdates['is_active'] = true;
            }

            if (! empty($itemUpdates)) {
                $item->update($itemUpdates);
            }

            if ($purchaseRequestItem->item_id !== $item->id) {
                $purchaseRequestItem->update([
                    'item_id' => $item->id,
                ]);
            }

            foreach ($purchaseRequestItem->photos ?? [] as $photo) {
                $filePath = trim((string) ($photo->file_path ?? ''));

                if ($filePath === '') {
                    continue;
                }

                $fileName = trim((string) ($photo->file_name ?? ''));

                $item->photos()->updateOrCreate(
                    [
                        'file_path' => $filePath,
                    ],
                    [
                        'file_name' => $fileName !== '' ? $fileName : basename($filePath),
                    ]
                );
            }
        }
    }

    protected function syncVendorsToMasterCatalog(): void
    {
        $this->record->load([
            'vendorOffers',
            'items.vendorOffers',
        ]);

        foreach ($this->record->vendorOffers as $vendorOffer) {
            $this->syncSingleVendorOfferToMasterCatalog($vendorOffer);
        }

        foreach ($this->record->items as $purchaseRequestItem) {
            foreach ($purchaseRequestItem->vendorOffers as $vendorOffer) {
                $this->syncSingleVendorOfferToMasterCatalog($vendorOffer);
            }
        }
    }

    protected function syncSingleVendorOfferToMasterCatalog($vendorOffer): void
    {
        if (! $vendorOffer) {
            return;
        }

        $vendorName = trim((string) ($vendorOffer->vendor_name ?? ''));
        $category = trim((string) ($vendorOffer->category ?? ''));

        if ($vendorOffer->vendor_id) {
            $existingVendor = Vendor::find($vendorOffer->vendor_id);

            if ($existingVendor) {
                $vendorUpdates = [];

                if (blank($existingVendor->category) && $category !== '') {
                    $vendorUpdates['category'] = $category;
                }

                if (blank($existingVendor->contact_person) && filled($vendorOffer->contact_person)) {
                    $vendorUpdates['contact_person'] = $vendorOffer->contact_person;
                }

                if (blank($existingVendor->phone) && filled($vendorOffer->phone)) {
                    $vendorUpdates['phone'] = $vendorOffer->phone;
                }

                if (blank($existingVendor->email) && filled($vendorOffer->email)) {
                    $vendorUpdates['email'] = $vendorOffer->email;
                }

                if (! $existingVendor->is_active) {
                    $vendorUpdates['is_active'] = true;
                }

                if (! empty($vendorUpdates)) {
                    $existingVendor->update($vendorUpdates);
                }

                $offerUpdates = [];

                if ((int) ($vendorOffer->vendor_id ?? 0) !== (int) $existingVendor->id) {
                    $offerUpdates['vendor_id'] = $existingVendor->id;
                }

                if (($vendorOffer->vendor_name ?? null) !== $existingVendor->name) {
                    $offerUpdates['vendor_name'] = $existingVendor->name;
                }

                if (! empty($offerUpdates)) {
                    $vendorOffer->update($offerUpdates);
                }

                return;
            }
        }

        if ($vendorName === '') {
            return;
        }

        $vendor = Vendor::firstOrCreate(
            [
                'name' => $vendorName,
            ],
            [
                'category' => $category !== '' ? $category : null,
                'contact_person' => $vendorOffer->contact_person,
                'phone' => $vendorOffer->phone,
                'email' => $vendorOffer->email,
                'is_active' => true,
            ]
        );

        $vendorUpdates = [];

        if (blank($vendor->category) && $category !== '') {
            $vendorUpdates['category'] = $category;
        }

        if (blank($vendor->contact_person) && filled($vendorOffer->contact_person)) {
            $vendorUpdates['contact_person'] = $vendorOffer->contact_person;
        }

        if (blank($vendor->phone) && filled($vendorOffer->phone)) {
            $vendorUpdates['phone'] = $vendorOffer->phone;
        }

        if (blank($vendor->email) && filled($vendorOffer->email)) {
            $vendorUpdates['email'] = $vendorOffer->email;
        }

        if (! $vendor->is_active) {
            $vendorUpdates['is_active'] = true;
        }

        if (! empty($vendorUpdates)) {
            $vendor->update($vendorUpdates);
        }

        $vendorOffer->update([
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
        ]);
    }

    protected function makeFinancialControllerAction(
        string $name,
        string $label,
        string $icon,
        string $color,
        string $status,
        array $visibleStatuses = [],
        ?string $defaultMessage = null,
        ?string $noteLabel = null,
        ?string $notePlaceholder = null,
        bool $noteRequired = false,
        bool $setCancelledAt = false,
        bool $setReceivedAt = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn() => $this->canFinancialControllerUpdate()
                && (empty($visibleStatuses) || in_array($this->record->status, $visibleStatuses, true)));

        if ($noteRequired) {
            $action = $action
                ->form([
                    Textarea::make('message')
                        ->label($noteLabel ?? ($label . ' Note'))
                        ->placeholder($notePlaceholder)
                        ->required()
                        ->rows(4),
                ])
                ->action(fn(array $data) => $this->updateFinancialControllerStatus(
                    status: $status,
                    action: $status,
                    successTitle: 'Purchase request marked as ' . strtolower($label) . '.',
                    message: trim((string) ($data['message'] ?? '')),
                    setCancelledAt: $setCancelledAt,
                    setReceivedAt: $setReceivedAt,
                ));
        } else {
            $action = $action
                ->requiresConfirmation()
                ->action(fn() => $this->updateFinancialControllerStatus(
                    status: $status,
                    action: $status,
                    successTitle: 'Purchase request marked as ' . strtolower($label) . '.',
                    message: $defaultMessage ?? ('Purchase request marked as ' . strtolower($label) . ' by Financial Controller.'),
                    setCancelledAt: $setCancelledAt,
                    setReceivedAt: $setReceivedAt,
                ));
        }

        return $action;
    }

    protected function updateFinancialControllerStatus(
        string $status,
        string $action,
        string $successTitle,
        string $message,
        bool $setCancelledAt = false,
        bool $setReceivedAt = false,
    ): void {
        $this->record->status = $status;

        if ($setCancelledAt) {
            $this->record->cancelled_at = now();
        }

        if ($setReceivedAt) {
            $this->record->received_at = now();
        }

        $this->record->save();

        $this->record->markActivity();

        $this->record->logs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'message' => $message,
        ]);

        if ($status === 'paid_to_vendor') {
            $this->sendSubmittedEmailToPurchasing(
                $this->record,
                'paid_to_vendor'
            );
        }

        Notification::make()
            ->success()
            ->title($successTitle)
            ->send();

        $this->refreshFormData([
            'status',
            'approved_at',
            'received_at',
            'cancelled_at',
            'current_status_at',
            'last_activity_at',
            'last_reminder_sent_at',
        ]);
    }

    protected function updatePurchasingDeliveryStatus(
        string $status,
        string $action,
        string $successTitle,
        string $message,
        ?string $receivedAt = null,
        ?string $receiverName = null,
    ): void {
        $this->record->status = $status;

        if ($receivedAt) {
            $this->record->received_at = $receivedAt;
        }

        if ($receiverName && \Schema::hasColumn($this->record->getTable(), 'receiver_name')) {
            $this->record->receiver_name = $receiverName;
        }

        $this->record->save();

        $this->record->markActivity();

        $this->record->logs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'message' => $message,
        ]);

        Notification::make()
            ->success()
            ->title($successTitle)
            ->send();

        $this->refreshFormData([
            'status',
            'received_at',
            'current_status_at',
            'last_activity_at',
            'last_reminder_sent_at',
        ]);
    }

    protected function sendSubmittedEmailToPurchasing(PurchaseRequest $purchaseRequest, string $fromStatus = 'draft'): void
    {
        $configuredEmails = collect(config('mail.purchasing_notification_emails', []));

        $userEmails = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get()
            ->filter(function (User $user) {
                $role = strtolower(trim((string) ($user->role ?? '')));

                if ($role === 'purchasing') {
                    return true;
                }

                return method_exists($user, 'isPurchasing') && $user->isPurchasing();
            })
            ->pluck('email');

        $emails = $configuredEmails
            ->merge($userEmails)
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
            new PurchaseRequestSubmittedNotification($purchaseRequest, $fromStatus)
        );
    }

    protected function sendSubmittedEmailToAccounting(PurchaseRequest $purchaseRequest, string $fromStatus = 'submitted_to_accounting'): void
    {
        $configuredEmails = collect(config('mail.accounting_notification_emails', []));

        $userEmails = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get()
            ->filter(function (User $user) {
                $role = strtolower(trim((string) ($user->role ?? '')));

                if ($role === 'accounting') {
                    return true;
                }

                return method_exists($user, 'isAccounting') && $user->isAccounting();
            })
            ->pluck('email');

        $emails = $configuredEmails
            ->merge($userEmails)
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

    protected function sendSubmittedEmailToFinancialController(PurchaseRequest $purchaseRequest, string $fromStatus = 'gm_approved'): void
    {
        $emails = config('mail.financial_controller_notification_emails', config('mail.accounting_notification_emails', []));

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
            ...config('mail.financial_controller_notification_emails', []),
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

    protected function getUserRole(?User $user): string
    {
        return strtolower(trim((string) ($user?->role ?? '')));
    }

    protected function isFinancialControllerUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, [
            'financial_controller',
            'financial-controller',
            'financial controller',
            'fc',
            'cost_controller',
            'cost-controller',
            'cost controller',
        ], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isFinancialController')
            && $user->isFinancialController();
    }

    protected function getFinancialControllerEditableStatuses(): array
    {
        return [
            'gm_approved',
            'pending',
            'on_progress',
            'waiting_payment',
            'paid_to_vendor',
            'on_shipping',
            'on_hold_by_fc',

            // legacy compatibility
            'pending_by_fc',
            'on_progress_by_fc',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'on_shipping_by_fc',
            'item_arrived_by_fc',
        ];
    }

    protected function getFinancialControllerClosedStatuses(): array
    {
        return [
            'received_by_requester',
            'received_by_requester_by_fc',
            'cancelled',
            'rejected',
            'approved',
        ];
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
                'gm_approved',
                'pending',
                'on_progress',
                'waiting_payment',
                'paid_to_vendor',
                'on_shipping',
                'received_by_requester',
                'pending_by_fc',
                'on_progress_by_fc',
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'on_shipping_by_fc',
                'received_by_requester_by_fc',
                'on_hold_by_fc',
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

    protected function canFinancialControllerUpdate(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            return false;
        }

        if (in_array($this->record->status, $this->getFinancialControllerClosedStatuses(), true)) {
            return false;
        }

        if (! in_array($this->record->status, $this->getFinancialControllerEditableStatuses(), true)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->isFinancialControllerUser($user);
    }

    protected function canShowFinancialControllerActions(): bool
    {
        if (! $this->canFinancialControllerUpdate()) {
            return false;
        }

        return in_array($this->record->status, [
            'gm_approved',
            'pending',
            'pending_by_fc',
            'on_progress',
            'on_progress_by_fc',
            'waiting_payment',
            'waiting_payment_by_fc',
            'on_hold_by_fc',
        ], true);
    }

    protected function canPurchasingUpdateAfterPaidToVendor(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            return false;
        }

        if (! ($user->isPurchasing() || $user->isAdmin())) {
            return false;
        }

        return in_array($this->record->status, [
            'paid_to_vendor',
            'on_shipping',
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
