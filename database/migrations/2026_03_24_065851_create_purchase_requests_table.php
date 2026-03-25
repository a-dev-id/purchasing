<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number')->unique()->nullable();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Snapshot so department name stays even if user profile changes later
            $table->string('department_name');

            $table->string('title');
            $table->enum('priority', ['urgent', 'normal'])->default('normal');

            $table->enum('status', [
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
                'cancelled',
            ])->default('draft');

            $table->longText('request_notes')->nullable();

            // Useful later for 3-day stuck reminder
            $table->timestamp('current_status_at')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};