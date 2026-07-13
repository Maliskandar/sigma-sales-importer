<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Kode toko hasil parsing kolom Toko, mis. SC / TB / RAYA');
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->nullOnDelete();
            $table->string('admin_name', 100)->nullable()->comment('Admin/CS penanggung jawab toko');
            $table->string('default_advertiser', 100)->nullable()->comment('Advertiser default bila kolom ADV kosong (mis. file marketplace)');
            $table->timestamps();

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
