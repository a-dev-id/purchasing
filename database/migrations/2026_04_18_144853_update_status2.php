<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('purchase_requests')->where('status', 'pending_by_fc')->update(['status' => 'pending']);
        DB::table('purchase_requests')->where('status', 'on_progress_by_fc')->update(['status' => 'on_progress']);
        DB::table('purchase_requests')->where('status', 'waiting_payment_by_fc')->update(['status' => 'waiting_payment']);
        DB::table('purchase_requests')->where('status', 'paid_to_vendor_by_fc')->update(['status' => 'paid_to_vendor']);
        DB::table('purchase_requests')->where('status', 'on_shipping_by_fc')->update(['status' => 'on_shipping']);
        DB::table('purchase_requests')->where('status', 'item_arrived_by_fc')->update(['status' => 'on_shipping']);
        DB::table('purchase_requests')->where('status', 'received_by_requester_by_fc')->update(['status' => 'received_by_requester']);
    }

    public function down(): void
    {
        DB::table('purchase_requests')->where('status', 'pending')->update(['status' => 'pending_by_fc']);
        DB::table('purchase_requests')->where('status', 'on_progress')->update(['status' => 'on_progress_by_fc']);
        DB::table('purchase_requests')->where('status', 'waiting_payment')->update(['status' => 'waiting_payment_by_fc']);
        DB::table('purchase_requests')->where('status', 'paid_to_vendor')->update(['status' => 'paid_to_vendor_by_fc']);
        DB::table('purchase_requests')->where('status', 'on_shipping')->update(['status' => 'on_shipping_by_fc']);
        DB::table('purchase_requests')->where('status', 'received_by_requester')->update(['status' => 'received_by_requester_by_fc']);
    }
};
