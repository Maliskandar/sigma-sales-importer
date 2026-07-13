<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained('platforms')->cascadeOnDelete();
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('hpp', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'platform_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
