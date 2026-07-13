<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('column_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('file_type', 50)->comment('daily, mp, produk');
            $table->string('excel_column', 100)->comment('Column name in Excel file');
            $table->string('db_column', 100)->comment('Column name in sales_transactions table');
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->unique(['file_type', 'excel_column']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('column_mappings');
    }
};
