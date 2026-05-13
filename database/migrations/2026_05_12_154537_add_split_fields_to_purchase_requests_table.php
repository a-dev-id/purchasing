<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_requests', 'parent_purchase_request_id')) {
                $table->unsignedBigInteger('parent_purchase_request_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('purchase_requests', 'deferred_until')) {
                $table->date('deferred_until')->nullable()->after('date_needed');
            }

            if (! Schema::hasColumn('purchase_requests', 'split_reason')) {
                $table->text('split_reason')->nullable()->after('request_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requests', 'split_reason')) {
                $table->dropColumn('split_reason');
            }

            if (Schema::hasColumn('purchase_requests', 'deferred_until')) {
                $table->dropColumn('deferred_until');
            }

            if (Schema::hasColumn('purchase_requests', 'parent_purchase_request_id')) {
                $table->dropColumn('parent_purchase_request_id');
            }
        });
    }
};
