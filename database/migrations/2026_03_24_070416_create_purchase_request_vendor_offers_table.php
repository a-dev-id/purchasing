<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_vendor_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_request_id')
                ->constrained('purchase_requests')
                ->cascadeOnDelete();

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();

            $table->string('vendor_name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->decimal('offer_total', 15, 2)->nullable();
            $table->string('currency', 10)->default('IDR');

            $table->integer('lead_time_days')->nullable();
            $table->text('offer_notes')->nullable();
            $table->string('quotation_file')->nullable();

            $table->unsignedTinyInteger('offer_rank')->nullable(); // 1, 2, 3
            $table->boolean('is_selected_by_accounting')->default(false);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['purchase_request_id', 'offer_rank'],
                'pr_vendor_offer_rank_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_vendor_offers');
    }
};
