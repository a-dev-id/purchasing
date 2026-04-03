<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->date('date_needed')->nullable()->after('priority');
        });

        // Optional backfill from old item-level field, if it exists.
        if (Schema::hasTable('purchase_request_items') && Schema::hasColumn('purchase_request_items', 'needed_by')) {
            DB::statement("
                UPDATE purchase_requests pr
                INNER JOIN (
                    SELECT purchase_request_id, MIN(needed_by) AS first_needed_date
                    FROM purchase_request_items
                    WHERE needed_by IS NOT NULL
                    GROUP BY purchase_request_id
                ) pri ON pri.purchase_request_id = pr.id
                SET pr.date_needed = pri.first_needed_date
                WHERE pr.date_needed IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('date_needed');
        });
    }
};
