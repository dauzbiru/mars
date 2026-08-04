<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn('monitoring_report_id');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->unsignedBigInteger('monitoring_report_id')->nullable()->after('id');
        });
    }
};