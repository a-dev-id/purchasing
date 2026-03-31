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
            $table->timestamp('last_activity_at')->nullable()->after('current_status_at');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('last_activity_at');
        });

        DB::table('purchase_requests')->update([
            'last_activity_at' => DB::raw('COALESCE(current_status_at, submitted_at, updated_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'last_reminder_sent_at',
            ]);
        });
    }
};
