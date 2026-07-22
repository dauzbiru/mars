<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // 1. Create pra_monitoring_reports
        Schema::create('pra_monitoring_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gerai_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->decimal('nilai', 10, 2)->nullable();
            $table->char('grade', 1)->nullable();
            $table->dateTime('checkin_at')->nullable();
            $table->dateTime('submit_at')->nullable();
            $table->string('periode_label')->nullable();
            $table->timestamps();
        });

        // 2. Create re_monitoring_reports
        Schema::create('re_monitoring_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gerai_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->decimal('nilai', 10, 2)->nullable();
            $table->char('grade', 1)->nullable();
            $table->dateTime('checkin_at')->nullable();
            $table->dateTime('submit_at')->nullable();
            $table->string('periode_label')->nullable();
            $table->timestamps();
        });

        // 3. Create evaluasi_reports
        Schema::create('evaluasi_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gerai_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('checkin_at')->nullable();
            $table->dateTime('submit_at')->nullable();
            $table->text('catatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // 4. Add polymorphic columns to results
        Schema::table('results', function (Blueprint $table) {
            $table->string('reportable_type')->nullable();
            $table->unsignedBigInteger('reportable_id')->nullable();
        });

        // 5. Add polymorphic columns to monitoring_findings
        Schema::table('monitoring_findings', function (Blueprint $table) {
            $table->string('reportable_type')->nullable();
            $table->unsignedBigInteger('reportable_id')->nullable();
        });

        // 6. Populate polymorphic columns for results
        $results = DB::table('results')
            ->join('monitoring_reports', 'results.monitoring_report_id', '=', 'monitoring_reports.id')
            ->select('results.id', 'monitoring_reports.type')
            ->get();

        foreach ($results as $row) {
            $type = match ($row->type) {
                'monitoring', 'import' => 'App\\Models\\MonitoringReport',
                'pra-monitoring' => 'App\\Models\\PraMonitoringReport',
                're-monitoring' => 'App\\Models\\ReMonitoringReport',
                default => 'App\\Models\\MonitoringReport',
            };
            DB::table('results')->where('id', $row->id)->update([
                'reportable_type' => $type,
                'reportable_id' => DB::raw('monitoring_report_id'),
            ]);
        }

        // 7. Populate polymorphic columns for monitoring_findings
        $findings = DB::table('monitoring_findings')
            ->join('monitoring_reports', 'monitoring_findings.monitoring_report_id', '=', 'monitoring_reports.id')
            ->select('monitoring_findings.id', 'monitoring_reports.type')
            ->get();

        foreach ($findings as $row) {
            $type = match ($row->type) {
                'monitoring', 'import' => 'App\\Models\\MonitoringReport',
                'pra-monitoring' => 'App\\Models\\PraMonitoringReport',
                're-monitoring' => 'App\\Models\\ReMonitoringReport',
                default => 'App\\Models\\MonitoringReport',
            };
            DB::table('monitoring_findings')->where('id', $row->id)->update([
                'reportable_type' => $type,
                'reportable_id' => DB::raw('monitoring_report_id'),
            ]);
        }

        // 8. Copy data to new tables
        $this->copyData('pra-monitoring', 'pra_monitoring_reports');
        $this->copyData('re-monitoring', 're_monitoring_reports');
        $this->copyData('evaluasi', 'evaluasi_reports');

        // 9. Drop old FK constraints before deleting rows
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['monitoring_report_id']);
        });
        Schema::table('monitoring_findings', function (Blueprint $table) {
            $table->dropForeign(['monitoring_report_id']);
        });

        // 10. Delete copied rows from monitoring_reports
        DB::table('monitoring_reports')
            ->whereIn('type', ['pra-monitoring', 're-monitoring', 'evaluasi'])
            ->delete();

        // 11. Update unique constraint on results
        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique('results_item_id_user_id_monitoring_report_id_unique');
            $table->unique(['item_id', 'user_id', 'reportable_type', 'reportable_id']);
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function copyData(string $type, string $table): void
    {
        $reports = DB::table('monitoring_reports')->where('type', $type)->get();
        foreach ($reports as $r) {
            $data = [
                'id' => $r->id,
                'gerai_id' => $r->gerai_id,
                'user_id' => $r->user_id,
                'checkin_at' => $r->checkin_at,
                'submit_at' => $r->submit_at,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];

            if ($type !== 'evaluasi') {
                $data['location'] = $r->location;
                $data['nilai'] = $r->nilai;
                $data['grade'] = $r->grade;
                $data['periode_label'] = $r->periode_label;
            } else {
                $data['catatan'] = $r->catatan;
                $data['keterangan'] = $r->keterangan;
            }

            DB::table($table)->insert($data);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pra_monitoring_reports');
        Schema::dropIfExists('re_monitoring_reports');
        Schema::dropIfExists('evaluasi_reports');

        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique('results_item_id_user_id_reportable_type_reportable_id_unique');
            $table->dropColumn(['reportable_type', 'reportable_id']);
        });

        Schema::table('monitoring_findings', function (Blueprint $table) {
            $table->dropColumn(['reportable_type', 'reportable_id']);
        });
    }
};
