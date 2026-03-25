<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_request_id')
                ->constrained('purchase_requests')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // snapshot, so history stays readable even if user data changes later
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('role_name')->nullable();

            $table->string('action');
            // examples:
            // created, submitted, updated, sent_to_accounting, sent_to_gm,
            // held, rejected_for_revision, approved, priority_changed,
            // vendor_offer_added, vendor_selected, reminder_sent, email_sent

            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->text('message')->nullable();

            // extra details if needed later
            $table->json('meta')->nullable();

            $table->timestamp('acted_at')->nullable();

            $table->timestamps();

            $table->index(['purchase_request_id', 'acted_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_logs');
    }
};
