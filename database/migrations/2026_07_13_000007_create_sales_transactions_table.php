<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('uploads')->cascadeOnDelete();
            $table->string('file_source', 50)->comment('daily, mp, produk');
            $table->date('sale_date');
            $table->string('group_code', 20)->nullable();
            $table->string('kanal', 100)->nullable();
            $table->string('metode_bayar', 50)->nullable();
            $table->string('toko', 200)->nullable();
            $table->string('adv', 100)->nullable();
            $table->string('type_transaksi', 20)->nullable()->comment('NC, RN, etc');
            $table->string('order_number', 100);
            $table->string('awb', 100)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_name', 200)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('product_code', 50);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_per_line', 15, 2)->default(0);
            $table->string('ekspedisi', 200)->nullable();
            $table->string('warehouse', 100)->nullable();
            $table->string('status_order', 50)->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('sale_date');
            $table->index('order_number');
            $table->index('product_code');
            $table->index('kanal');
            $table->index(['upload_id', 'file_source']);

            // Unique constraint to prevent duplicate on re-import
            $table->unique(['order_number', 'product_code', 'file_source'], 'unique_order_product_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
