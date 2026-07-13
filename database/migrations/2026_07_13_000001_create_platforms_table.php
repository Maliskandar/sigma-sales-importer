<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('channel_type', 50)->nullable()->comment('marketplace, direct, social_commerce');
            $table->string('output_label', 50)->nullable()->comment('Label Platform di file output, mis. WEB / TIKTOK SHOP / SHOPEE');
            $table->string('payment_label', 50)->nullable()->comment('Nilai kolom Payment type di output; null = pakai metode_bayar transaksi');
            $table->json('aliases')->nullable()->comment('Daftar variasi nama Kanal di Excel yang dipetakan ke platform ini');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
