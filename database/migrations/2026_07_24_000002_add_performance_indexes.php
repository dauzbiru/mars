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
            if (!$this->indexExists('monitoring_reports', 'monitoring_reports_user_id_checkin_at_submit_at_index')) {
                $table->index(['user_id', 'checkin_at', 'submit_at']);
            }
            if (!$this->indexExists('monitoring_reports', 'monitoring_reports_periode_label_index')) {
                $table->index('periode_label');
            }
            if (!$this->indexExists('monitoring_reports', 'monitoring_reports_grade_index')) {
                $table->index('grade');
            }
        });

        Schema::table('pra_monitoring_reports', function (Blueprint $table) {
            if (!$this->indexExists('pra_monitoring_reports', 'pra_monitoring_reports_gerai_id_checkin_at_submit_at_index')) {
                $table->index(['gerai_id', 'checkin_at', 'submit_at']);
            }
            if (!$this->indexExists('pra_monitoring_reports', 'pra_monitoring_reports_user_id_checkin_at_submit_at_index')) {
                $table->index(['user_id', 'checkin_at', 'submit_at']);
            }
        });

        Schema::table('re_monitoring_reports', function (Blueprint $table) {
            if (!$this->indexExists('re_monitoring_reports', 're_monitoring_reports_gerai_id_checkin_at_submit_at_index')) {
                $table->index(['gerai_id', 'checkin_at', 'submit_at']);
            }
            if (!$this->indexExists('re_monitoring_reports', 're_monitoring_reports_user_id_checkin_at_submit_at_index')) {
                $table->index(['user_id', 'checkin_at', 'submit_at']);
            }
        });

        Schema::table('gerais', function (Blueprint $table) {
            if (!$this->indexExists('gerais', 'gerais_kode_gerai_is_active_index')) {
                $table->index(['kode_gerai', 'is_active']);
            }
        });

        Schema::table('results', function (Blueprint $table) {
            if (!$this->indexExists('results', 'results_item_id_index')) {
                $table->index('item_id');
            }
        });

        Schema::table('rankings', function (Blueprint $table) {
            if (!$this->indexExists('rankings', 'rankings_gerai_id_periode_label_index')) {
                $table->index(['gerai_id', 'periode_label']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_reports', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'checkin_at', 'submit_at']);
            $table->dropIndex('periode_label');
            $table->dropIndex('grade');
        });

        Schema::table('pra_monitoring_reports', function (Blueprint $table) {
            $table->dropIndex(['gerai_id', 'checkin_at', 'submit_at']);
            $table->dropIndex(['user_id', 'checkin_at', 'submit_at']);
        });

        Schema::table('re_monitoring_reports', function (Blueprint $table) {
            $table->dropIndex(['gerai_id', 'checkin_at', 'submit_at']);
            $table->dropIndex(['user_id', 'checkin_at', 'submit_at']);
        });

        Schema::table('gerais', function (Blueprint $table) {
            $table->dropIndex(['kode_gerai', 'is_active']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex('item_id');
        });

        Schema::table('rankings', function (Blueprint $table) {
            $table->dropIndex(['gerai_id', 'periode_label']);
        });
    }
};
