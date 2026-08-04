<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = function (Blueprint $table) {
            $table->foreignId('editing_user_id')->nullable()->after('submit_at')->constrained('users')->nullOnDelete();
            $table->dateTime('editing_at')->nullable()->after('editing_user_id');
            $table->json('editing_snapshot')->nullable()->after('editing_at');
        };

        Schema::table('monitoring_reports', $columns);
        Schema::table('pra_monitoring_reports', $columns);
        Schema::table('re_monitoring_reports', $columns);
    }

    public function down(): void
    {
        $drop = function (Blueprint $table) {
            $table->dropForeign(['editing_user_id']);
            $table->dropColumn(['editing_user_id', 'editing_at', 'editing_snapshot']);
        };

        Schema::table('monitoring_reports', $drop);
        Schema::table('pra_monitoring_reports', $drop);
        Schema::table('re_monitoring_reports', $drop);
    }
};
