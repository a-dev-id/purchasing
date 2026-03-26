<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                'revision_from_accounting',
                'on_hold_by_accounting',
                'submitted_to_gm',
                'revision_from_gm',
                'on_hold_by_gm',
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
                'approved',
                'rejected'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
