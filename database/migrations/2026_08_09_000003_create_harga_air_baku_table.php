<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harga_air_baku', function (Blueprint $table) {
            $table->id();
            $table->string('kota')->nullable();
            $table->string('nama_supplier');
            $table->decimal('harga_air_baku', 12, 2)->nullable();
            $table->string('pemilik')->nullable();
            $table->string('no_telepon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_air_baku');
    }
};
