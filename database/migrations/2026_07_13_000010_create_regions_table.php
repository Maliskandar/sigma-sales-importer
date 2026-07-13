<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('province', 100)->unique()->comment('Nama provinsi seperti di file input');
            $table->string('region', 100)->comment('Region hasil normalisasi, mis. JAWA');
            $table->timestamps();

            $table->index('province');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
