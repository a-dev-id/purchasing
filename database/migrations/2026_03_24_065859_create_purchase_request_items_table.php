<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_request_id')
                ->constrained('purchase_requests')
                ->cascadeOnDelete();

            $table->string('item_name');
            $table->text('specification')->nullable();

            $table->decimal('qty', 12, 2)->default(1);
            $table->string('unit')->nullable(); // pcs, box, liter, set, etc.

            $table->date('needed_by')->nullable();
            $table->text('purpose')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
