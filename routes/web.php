<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseRequestReminderController;
use App\Http\Controllers\PurchaseRequestViewController;
use App\Http\Controllers\PurchaseRequestSummaryPrintController;
use App\Http\Controllers\Purchasing\V2\DashboardController;
use App\Http\Controllers\Purchasing\V2\ItemMasterController;
use App\Http\Controllers\Purchasing\V2\PurchaseRequestController;
use App\Http\Controllers\Purchasing\V2\PurchaseRequestPurchasingController;
use App\Http\Controllers\Purchasing\V2\PurchaseRequestCostControlController;
use App\Http\Controllers\Purchasing\V2\PurchaseRequestGmController;

Route::get('/', fn() => view('app-select'))->name('app.select');

Route::get(
    '/cron/purchase-requests/reminders/{token}',
    [PurchaseRequestReminderController::class, 'run']
)->name('cron.purchase-requests.reminders');

Route::middleware(['auth'])->group(function () {
    Route::get('/purchase-requests/{purchaseRequest}/view-form', [PurchaseRequestViewController::class, 'show'])
        ->name('purchase-requests.view-form');

    Route::get('/purchase-requests/summary-print', [PurchaseRequestSummaryPrintController::class, 'index'])
        ->name('purchase-requests.summary-print');
});

// --- Purchasing Lite UI ---
Route::middleware(['auth'])
    ->prefix('purchasing/v2')
    ->name('purchasing.v2.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard.index');

        Route::post('/requests/{purchaseRequest}/fc-status', [DashboardController::class, 'updateFcStatus'])
            ->name('requests.fc-status.update');

        Route::get('/need-my-action', [PurchaseRequestController::class, 'needMyAction'])
            ->name('need-my-action');

        Route::post('/logout', function (Request $request) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/purchasing/login');
        })->name('logout');

        // Purchase Requests
        Route::get('/requests', [PurchaseRequestController::class, 'index'])
            ->name('requests.index');

        Route::get('/requests/create', [PurchaseRequestController::class, 'create'])
            ->name('requests.create');

        Route::post('/requests', [PurchaseRequestController::class, 'store'])
            ->name('requests.store');

        Route::get('/requests/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])
            ->name('requests.edit');

        Route::put('/requests/{purchaseRequest}', [PurchaseRequestController::class, 'update'])
            ->name('requests.update');

        Route::get('/requests/{purchaseRequest}', [PurchaseRequestController::class, 'show'])
            ->name('requests.show');

        Route::post('/requests/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])
            ->name('requests.submit');

        // Purchasing Actions
        Route::post('/requests/{purchaseRequest}/vendor-offers', [PurchaseRequestPurchasingController::class, 'saveVendorOffers'])
            ->name('requests.vendor-offers.save');

        Route::post('/requests/{purchaseRequest}/submit-to-accounting', [PurchaseRequestPurchasingController::class, 'submitToAccounting'])
            ->name('requests.submit-to-accounting');

        Route::post('/requests/{purchaseRequest}/return-to-requester', [PurchaseRequestPurchasingController::class, 'returnToRequester'])
            ->name('requests.return-to-requester');

        Route::post('/requests/{purchaseRequest}/reject', [PurchaseRequestPurchasingController::class, 'reject'])
            ->name('requests.reject');

        // Cost Control Actions
        Route::post('/requests/{purchaseRequest}/selected-vendors', [PurchaseRequestCostControlController::class, 'saveSelectedVendors'])
            ->name('requests.save-selected-vendors');

        Route::post('/requests/{purchaseRequest}/submit-to-gm', [PurchaseRequestCostControlController::class, 'submitToGm'])
            ->name('requests.submit-to-gm');

        // GM Actions
        Route::post('/requests/{purchaseRequest}/gm-approve-items', [PurchaseRequestGmController::class, 'approveItems'])
            ->name('requests.gm-approve-items');

        Route::post('/requests/{purchaseRequest}/gm-send-back-to-purchasing', [PurchaseRequestGmController::class, 'sendBackToPurchasing'])
            ->name('requests.gm-send-back-to-purchasing');

        Route::post('/requests/{purchaseRequest}/gm-send-back-to-requester', [PurchaseRequestGmController::class, 'sendBackToRequester'])
            ->name('requests.gm-send-back-to-requester');

        Route::post('/requests/{purchaseRequest}/gm-reject', [PurchaseRequestGmController::class, 'reject'])
            ->name('requests.gm-reject');

        /*
        |--------------------------------------------------------------------------
        | Legacy Action Route Aliases
        |--------------------------------------------------------------------------
        | Keep this alias so older Blade files still work.
        */

        Route::post('/requests/{purchaseRequest}/send-back-to-purchasing', [PurchaseRequestGmController::class, 'sendBackToPurchasing'])
            ->name('requests.send-back-to-purchasing');

        // Item Master
        Route::get('/items', [ItemMasterController::class, 'index'])
            ->name('items.index');

        Route::get('/items/create', [ItemMasterController::class, 'create'])
            ->name('items.create');

        Route::post('/items', [ItemMasterController::class, 'store'])
            ->name('items.store');

        Route::post('/items/quick-store', [ItemMasterController::class, 'quickStore'])
            ->name('items.quick-store');

        Route::post('/items/{item}/quick-photo', [ItemMasterController::class, 'quickPhotoStore'])
            ->name('items.quick-photo-store');

        Route::get('/items/{item}/edit', [ItemMasterController::class, 'edit'])
            ->name('items.edit');

        Route::put('/items/{item}', [ItemMasterController::class, 'update'])
            ->name('items.update');

        Route::delete('/item-photos/{photo}', [ItemMasterController::class, 'destroyPhoto'])
            ->name('items.photos.destroy');
    });
