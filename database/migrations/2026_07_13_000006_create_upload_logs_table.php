<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('uploads')->cascadeOnDelete();
            $table->string('file_source', 50)->comment('daily, mp, produk');
            $table->integer('row_number')->nullable();
            $table->enum('level', ['info', 'warning', 'error'])->default('info');
            $table->text('message');
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['upload_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_logs');
    }
};
