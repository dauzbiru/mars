<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('monitoring_findings')
            ->whereNull('reportable_type')
            ->orWhereNull('reportable_id')
            ->delete();

        Schema::table('monitoring_findings', function (Blueprint $table) {
            $table->unique(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_findings', function (Blueprint $table) {
            $table->dropUnique(['reportable_type', 'reportable_id']);
        });
    }
};
