<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE purchase_requests
            MODIFY status ENUM(
                'draft',
                'submitted',
                'revision_from_purchasing',
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'revision_from_accounting',
                'submitted_to_gm',
                'on_hold_by_gm',
                'revision_from_gm',
                'approved',
                'rejected'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE purchase_requests
            MODIFY status ENUM(
                'draft',
                'submitted',
                'revision_from_purchasing',
                'waiting_accounting',
                'held_by_accounting',
                'revision_from_accounting',
                'waiting_gm',
                'held_by_gm',
                'revision_from_gm',
                'approved',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
