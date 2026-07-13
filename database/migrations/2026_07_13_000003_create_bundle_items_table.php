<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_product_id')->constrained('products')->cascadeOnDelete();
            // Optional link ke produk master, boleh null karena komponen bundle
            // (mis. "BOXL A") bisa jadi bukan produk yang dijual satuan.
            $table->foreignId('item_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('sku', 50)->comment('SKU komponen di file output, mis. BDL01 / BDL02');
            $table->string('name', 200)->comment('Nama komponen bundle, mis. BOXL A');
            $table->integer('quantity')->default(1)->comment('Jumlah komponen dalam 1 bundle');
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('finance_price', 15, 2)->default(0)->comment('Omzet komponen untuk file FINANCE');
            $table->decimal('marketing_price', 15, 2)->default(0)->comment('Omzet komponen untuk file MARKETING');
            $table->decimal('hpp', 15, 2)->default(0)->comment('HPP per komponen bundle');
            $table->timestamps();

            $table->unique(['bundle_product_id', 'sku']);
            $table->index('bundle_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_items');
    }
};
