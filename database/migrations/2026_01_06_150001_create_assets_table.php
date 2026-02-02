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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            // ข้อมูลหลัก
            $table->string('asset_name', 255);
            $table->string('asset_code', 100)->nullable();
            $table->foreignId('category_id')->constrained('asset_categories')->onDelete('restrict');
            $table->string('gfmis_number', 100)->nullable();
            
            // ข้อมูลการจัดซื้อ
            $table->date('acquisition_date');
            $table->string('document_number', 100)->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->integer('quantity')->default(1);
            $table->smallInteger('budget_type')->nullable()->comment('1:งบประมาณ, 2:นอกงบ, 3:บริจาค, 4:อื่นๆ');
            $table->smallInteger('acquisition_method')->nullable()->comment('1:เฉพาะเจาะจง, 2:คัดเลือก, 3:สอบราคา, 4:พิเศษ, 5:รับบริจาค');
            
            // ข้อมูลค่าเสื่อม (nullable = ใช้ค่าจาก category)
            $table->integer('useful_life_years')->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            
            // ข้อมูลผู้ขาย/ผู้บริจาค
            $table->string('supplier_name', 255)->nullable();
            $table->string('supplier_phone', 20)->nullable();
            
            // สถานะและหมายเหตุ
            $table->smallInteger('status')->default(1)->comment('1:ใช้งาน, 2:ไม่ได้ใช้, 3:จำหน่าย, 4:ซ่อมแซม, 5:ไม่ทราบ');
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            // Unique constraint (allows multiple NULLs in MySQL)
            $table->unique(['school_id', 'asset_code']);

            // Indexes
            $table->index('status');
            $table->index('acquisition_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
