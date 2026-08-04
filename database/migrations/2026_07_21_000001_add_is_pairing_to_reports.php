<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            $table->boolean('is_pairing')->default(false);
        });

        Schema::table('pra_monitoring_reports', function (Blueprint $table) {
            $table->boolean('is_pairing')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            $table->dropColumn('is_pairing');
        });

        Schema::table('pra_monitoring_reports', function (Blueprint $table) {
            $table->dropColumn('is_pairing');
        });
    }
};
