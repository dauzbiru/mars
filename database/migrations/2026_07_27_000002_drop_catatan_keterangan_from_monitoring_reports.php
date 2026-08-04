<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            $table->dropColumn(['catatan', 'keterangan']);
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('periode_label');
            $table->text('keterangan')->nullable()->after('catatan');
        });
    }
};