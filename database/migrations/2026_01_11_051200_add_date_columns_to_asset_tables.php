<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add created_date and updated_date to asset_categories
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dateTime('created_date')->nullable()->after('updated_by');
            $table->dateTime('updated_date')->nullable()->after('created_date');
        });

        // Add created_date and updated_date to assets
        Schema::table('assets', function (Blueprint $table) {
            $table->dateTime('created_date')->nullable()->after('updated_by');
            $table->dateTime('updated_date')->nullable()->after('created_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn(['created_date', 'updated_date']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['created_date', 'updated_date']);
        });
    }
};
