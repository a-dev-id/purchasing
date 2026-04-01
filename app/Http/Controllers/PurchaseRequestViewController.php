<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestViewController extends Controller
{
    public function show(Request $request, PurchaseRequest $purchaseRequest)
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $canView =
            $user->isAdmin()
            || $user->canSeeAllPurchaseRequests()
            || (
                $user->isRequester()
                && $purchaseRequest->department_name === $user->department_name
            );

        abort_unless($canView, 403);

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
