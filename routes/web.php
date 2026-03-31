<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


use App\Http\Controllers\PurchaseRequestReminderController;

Route::get(
    '/cron/purchase-requests/reminders/{token}',
    [PurchaseRequestReminderController::class, 'run']
)->name('cron.purchase-requests.reminders');
