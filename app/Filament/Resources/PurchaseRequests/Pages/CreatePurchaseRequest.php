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
        $this->record->loadMissing([
            'items.photos',
        ]);

        foreach ($this->record->items as $purchaseRequestItem) {
            $itemName = trim((string) ($purchaseRequestItem->item_name ?? ''));

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

            if ((int) ($purchaseRequestItem->item_id ?? 0) !== (int) $item->id) {
                $purchaseRequestItem->update([
                    'item_id' => $item->id,
                ]);
            }

            if (! method_exists($item, 'photos')) {
                continue;
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
        $this->record->loadMissing([
            'vendorOffers',
            'items.vendorOffers',
        ]);

        foreach ($this->record->vendorOffers as $vendorOffer) {
            $this->syncSingleVendorOffer($vendorOffer);
        }

        foreach ($this->record->items as $purchaseRequestItem) {
            foreach ($purchaseRequestItem->vendorOffers as $vendorOffer) {
                $this->syncSingleVendorOffer($vendorOffer);
            }
        }
    }

    protected function syncSingleVendorOffer($vendorOffer): void
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

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Purchase request created';
    }
}
