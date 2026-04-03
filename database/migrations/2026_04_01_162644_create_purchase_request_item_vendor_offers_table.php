<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_item_vendor_offers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_request_item_id');
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->string('vendor_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->decimal('offer_total', 15, 2)->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->integer('lead_time_days')->nullable();

            $table->unsignedTinyInteger('offer_rank')->nullable();
            $table->boolean('is_selected_by_accounting')->default(false);

            $table->text('offer_notes')->nullable();
            $table->string('quotation_file')->nullable();

            $table->timestamps();

            $table->foreign('purchase_request_item_id', 'privo_item_fk')
                ->references('id')
                ->on('purchase_request_items')
                ->cascadeOnDelete();

            $table->foreign('vendor_id', 'privo_vendor_fk')
                ->references('id')
                ->on('vendors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_item_vendor_offers');
    }
};
