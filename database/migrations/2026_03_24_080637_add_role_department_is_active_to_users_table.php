<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('email');
            $table->string('department_name')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('department_name');

            $table->index('role');
            $table->index('department_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['department_name']);
            $table->dropIndex(['is_active']);

            $table->dropColumn([
                'role',
                'department_name',
                'is_active',
            ]);
        });
    }
};
