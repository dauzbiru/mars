<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn($i) => $i['name'] === $index);
    }

    public function up(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            if (!$this->indexExists('monitoring_reports', 'monitoring_reports_type_periode_label_submit_at_index')) {
                $table->index(['type', 'periode_label', 'submit_at']);
            }
            if (!$this->indexExists('monitoring_reports', 'monitoring_reports_gerai_id_periode_label_submit_at_index')) {
                $table->index(['gerai_id', 'periode_label', 'submit_at']);
            }
            if (!$this->indexExists('monitoring_reports', 'monitoring_reports_gerai_id_checkin_at_index')) {
                $table->index(['gerai_id', 'checkin_at']);
            }
        });

        Schema::table('results', function (Blueprint $table) {
            if (!$this->indexExists('results', 'results_reportable_type_reportable_id_index')) {
                $table->index(['reportable_type', 'reportable_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            $table->dropIndex(['type', 'periode_label', 'submit_at']);
            $table->dropIndex(['gerai_id', 'periode_label', 'submit_at']);
            $table->dropIndex(['gerai_id', 'checkin_at']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex(['reportable_type', 'reportable_id']);
        });
    }
};
