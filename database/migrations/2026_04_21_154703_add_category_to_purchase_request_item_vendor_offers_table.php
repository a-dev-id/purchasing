<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_item_vendor_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_request_item_vendor_offers', 'category')) {
                $table->string('category')->nullable()->after('vendor_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_item_vendor_offers', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_request_item_vendor_offers', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
