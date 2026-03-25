<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLog;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitRequest')
                ->label('Submit Request')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => $this->canRequesterSubmit())
                ->action(function (): void {
                    /** @var PurchaseRequest $record */
                    $record = $this->record;

                    if ($record->items()->count() === 0) {
                        Notification::make()
                            ->title('Add at least one item before submitting.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $fromStatus = $record->status;

                    if (blank($record->request_number)) {
                        $record->request_number = $this->generateRequestNumber($record);
                    }

                    $record->status = 'submitted_to_purchasing';
                    $record->current_status_at = now();

                    if (blank($record->submitted_at)) {
                        $record->submitted_at = now();
                    }

                    $record->save();

                    $user = $this->getCurrentUser();

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

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Action::make('rejectForRevision')
                ->label('Reject for Revision')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => $this->canPurchasingReject())
                ->form([
                    Textarea::make('message')
                        ->label('Revision Message')
                        ->placeholder('Explain what needs to be corrected by the requester')
                        ->required()
                        ->rows(5),
                ])
                ->action(function (array $data): void {
                    /** @var PurchaseRequest $record */
                    $record = $this->record;

                    $fromStatus = $record->status;

                    $record->status = 'revision_from_purchasing';
                    $record->current_status_at = now();
                    $record->save();

                    $user = $this->getCurrentUser();

                    PurchaseRequestLog::create([
                        'purchase_request_id' => $record->id,
                        'user_id' => $user?->id,
                        'user_name' => $user?->name,
                        'role_name' => $user?->role,
                        'action' => 'rejected_for_revision',
                        'from_status' => $fromStatus,
                        'to_status' => 'revision_from_purchasing',
                        'message' => $data['message'],
                        'meta' => [
                            'rejected_by' => 'purchasing',
                            'request_number' => $record->request_number,
                        ],
                        'acted_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Purchase request sent back for revision.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->syncItemsToMasterCatalog();
        $this->syncVendorsToMasterCatalog();
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
        ], true);
    }

    protected function canPurchasingReject(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! ($user->isPurchasing() || $user->isAdmin())) {
            return false;
        }

        return $this->record->status === 'submitted_to_purchasing';
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
