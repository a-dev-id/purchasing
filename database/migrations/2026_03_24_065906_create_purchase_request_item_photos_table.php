<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_item_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_request_item_id')
                ->constrained('purchase_request_items')
                ->cascadeOnDelete();

            $table->string('file_path');
            $table->string('file_name')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_item_photos');
    }
};
