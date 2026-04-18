<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE purchase_requests
            MODIFY COLUMN status ENUM(
                'draft',
                'submitted',
                'revision_from_purchasing',
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'revision_from_accounting',
                'revision_to_purchasing_from_accounting',
                'revision_to_requester_from_accounting',
                'submitted_to_gm',
                'on_hold_by_gm',
                'revision_from_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'revision_to_requester_from_gm',
                'gm_approved',
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
                'received_by_requester_by_fc',
                'on_hold_by_fc',
                'approved',
                'rejected',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE purchase_requests
            MODIFY COLUMN status ENUM(
                'draft',
                'submitted',
                'revision_from_purchasing',
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'revision_from_accounting',
                'revision_to_purchasing_from_accounting',
                'revision_to_requester_from_accounting',
                'submitted_to_gm',
                'on_hold_by_gm',
                'revision_from_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'revision_to_requester_from_gm',
                'approved',
                'rejected',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
