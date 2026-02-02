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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('school_code')->unique();
            $table->string('address')->nullable();
            $table->string('road')->nullable();
            $table->foreignId('district_id')->constrained('subdistricts');
            $table->foreignId('amphure_id')->constrained('amphures');
            $table->foreignId('province_id')->constrained('provinces');
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('last_edited_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
