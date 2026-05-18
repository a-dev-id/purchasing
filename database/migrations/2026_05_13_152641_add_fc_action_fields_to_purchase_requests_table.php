<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_requests', 'fc_action_status')) {
                $table->string('fc_action_status')->nullable()->after('status');
            }

            if (! Schema::hasColumn('purchase_requests', 'fc_remarks')) {
                $table->text('fc_remarks')->nullable()->after('fc_action_status');
            }

            if (! Schema::hasColumn('purchase_requests', 'fc_action_updated_at')) {
                $table->timestamp('fc_action_updated_at')->nullable()->after('fc_remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requests', 'fc_action_updated_at')) {
                $table->dropColumn('fc_action_updated_at');
            }

            if (Schema::hasColumn('purchase_requests', 'fc_remarks')) {
                $table->dropColumn('fc_remarks');
            }

            if (Schema::hasColumn('purchase_requests', 'fc_action_status')) {
                $table->dropColumn('fc_action_status');
            }
        });
    }
};
