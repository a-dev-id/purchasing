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
                Action::make('markWaitingPaymentByFc')
                    ->label('Mark Waiting Payment')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn() => $this->canFinancialControllerUpdate())
                    ->action(function () {
                        $this->record->status = 'waiting_payment_by_fc';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'waiting_payment_by_fc',
                            'message' => 'Purchase request marked as waiting for payment by Financial Controller.',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request marked as waiting for payment.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('markPaidToVendorByFc')
                    ->label('Mark Paid to Vendor')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn() => $this->canFinancialControllerUpdate())
                    ->action(function () {
                        $this->record->status = 'paid_to_vendor_by_fc';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'paid_to_vendor_by_fc',
                            'message' => 'Purchase request marked as paid to vendor by Financial Controller.',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request marked as paid to vendor.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('markItemArrivedByFc')
                    ->label('Mark Item Arrived')
                    ->icon('heroicon-m-truck')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn() => $this->canFinancialControllerUpdate())
                    ->action(function () {
                        $this->record->status = 'item_arrived_by_fc';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'item_arrived_by_fc',
                            'message' => 'Purchase request marked as item arrived by Financial Controller.',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request marked as item arrived.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('markReceivedByRequesterByFc')
                    ->label('Mark Received by Requester')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn() => $this->canFinancialControllerUpdate())
                    ->action(function () {
                        $this->record->status = 'received_by_requester_by_fc';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'received_by_requester_by_fc',
                            'message' => 'Purchase request marked as received by requester by Financial Controller.',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request marked as received by requester.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),

                Action::make('holdByFinancialController')
                    ->label('Hold')
                    ->icon('heroicon-m-pause-circle')
                    ->color('gray')
                    ->visible(fn() => $this->canFinancialControllerUpdate())
                    ->form([
                        Textarea::make('message')
                            ->label('Hold Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data) {
                        $this->record->status = 'on_hold_by_fc';
                        $this->record->save();

                        $this->record->markActivity();

                        $this->record->logs()->create([
                            'user_id' => Auth::id(),
                            'action' => 'on_hold_by_fc',
                            'message' => $data['message'],
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Purchase request placed on hold by Financial Controller.')
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'current_status_at',
                            'last_activity_at',
                            'last_reminder_sent_at',
                        ]);
                    }),
            ])
                ->label('Financial Controller Actions')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->visible(fn() => $this->canFinancialControllerUpdate()),
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
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
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

        if ($user->isAdmin()) {
            return in_array($this->record->status, [
                'gm_approved',
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
                'on_hold_by_fc',
            ], true);
        }

        if (! ($this->isFinancialControllerUser($user) || $user->isAccounting())) {
            return false;
        }

        return in_array($this->record->status, [
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'on_hold_by_fc',
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
