<?php

namespace App\Http\Controllers;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class PurchaseRequestViewController extends Controller
{
    public function show(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless(
            PurchaseRequestResource::canView($purchaseRequest)
                || PurchaseRequestResource::canEdit($purchaseRequest),
            403
        );

        $purchaseRequest->load([
            'items.item',
            'vendorOffers.vendor',
            'logs' => fn($query) => $query->latest('acted_at'),
        ]);

        return view('purchase-requests.view-form', [
            'purchaseRequest' => $purchaseRequest,
        ]);
    }
}
