<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('app-select'))->name('app.select');


use App\Http\Controllers\PurchaseRequestReminderController;

Route::get(
    '/cron/purchase-requests/reminders/{token}',
    [PurchaseRequestReminderController::class, 'run']
)->name('cron.purchase-requests.reminders');

use App\Http\Controllers\PurchaseRequestViewController;

Route::middleware(['auth'])->group(function () {
    Route::get('/purchase-requests/{purchaseRequest}/view-form', [PurchaseRequestViewController::class, 'show'])
        ->name('purchase-requests.view-form');
});


use App\Http\Controllers\PurchaseRequestSummaryPrintController;

Route::middleware(['auth'])->group(function () {
    Route::get('/purchase-requests/summary-print', [PurchaseRequestSummaryPrintController::class, 'index'])
        ->name('purchase-requests.summary-print');
});
