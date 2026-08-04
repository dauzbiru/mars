<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tampilan_gerai_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
        });

        Schema::create('tampilan_gerai_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('tampilan_gerai_blocks')->cascadeOnDelete();
            $table->string('foto');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('block_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tampilan_gerai_photos');
        Schema::dropIfExists('tampilan_gerai_blocks');
    }
};
