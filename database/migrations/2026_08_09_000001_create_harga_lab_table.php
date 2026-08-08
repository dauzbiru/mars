<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harga_lab', function (Blueprint $table) {
            $table->id();
            $table->string('kota')->nullable();
            $table->string('laboratorium');
            $table->decimal('mikrobiologi', 12, 2)->nullable();
            $table->decimal('fisika_kimia', 12, 2)->nullable();
            $table->decimal('lengkap', 12, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_lab');
    }
};
