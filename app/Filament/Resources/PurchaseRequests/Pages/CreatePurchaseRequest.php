<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Item;
use App\Models\User;
use App\Models\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isRequester()) {
            $data['department_name'] = $user->department_name;
        }

        $data['status'] = 'draft';
        $data['current_status_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncItemsToMasterCatalog();
        $this->syncVendorsToMasterCatalog();
    }

    protected function syncItemsToMasterCatalog(): void
    {
        $this->record->load('items');

        foreach ($this->record->items as $purchaseRequestItem) {
            if ($purchaseRequestItem->item_id) {
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

            $purchaseRequestItem->update([
                'item_id' => $item->id,
            ]);
        }
    }

    protected function syncVendorsToMasterCatalog(): void
    {
        $this->record->load([
            'vendorOffers',
            'items.vendorOffers',
        ]);

        $syncVendorOffer = function ($vendorOffer): void {
            if (! $vendorOffer) {
                return;
            }

            $vendorName = trim((string) $vendorOffer->vendor_name);

            if ($vendorOffer->vendor_id) {
                $existingVendor = Vendor::find($vendorOffer->vendor_id);

                if ($existingVendor) {
                    $existingVendor->update([
                        'contact_person' => $vendorOffer->contact_person ?: $existingVendor->contact_person,
                        'phone' => $vendorOffer->phone ?: $existingVendor->phone,
                        'email' => $vendorOffer->email ?: $existingVendor->email,
                        'is_active' => true,
                    ]);

                    $vendorOffer->update([
                        'vendor_id' => $existingVendor->id,
                        'vendor_name' => $existingVendor->name,
                    ]);

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

            if (! $vendor->is_active) {
                $vendor->is_active = true;
            }

            $vendor->save();

            $vendorOffer->update([
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
            ]);
        };

        foreach ($this->record->vendorOffers as $vendorOffer) {
            $syncVendorOffer($vendorOffer);
        }

        foreach ($this->record->items as $purchaseRequestItem) {
            foreach ($purchaseRequestItem->vendorOffers as $vendorOffer) {
                $syncVendorOffer($vendorOffer);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Purchase request created';
    }
}
