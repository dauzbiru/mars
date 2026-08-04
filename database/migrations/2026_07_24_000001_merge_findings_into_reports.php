<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $findingColumns = [
        'major', 'minor', 'peringatan_awal',
        'ttd_petugas', 'ttd_pimpinan',
        'penjelasan_isi', 'penjelasan_isi_3',
        'pengawas', 'rata_rata_aj', 'tds', 'mesin_ozon', 'note',
        'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca',
    ];

    private array $findingColumnsNoTds = [
        'major', 'minor', 'peringatan_awal',
        'ttd_petugas', 'ttd_pimpinan',
        'penjelasan_isi_3',
        'pengawas', 'rata_rata_aj', 'mesin_ozon', 'note',
        'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca',
    ];

    public function up(): void
    {
        $this->addFindingColumns('monitoring_reports', $this->findingColumns);
        $this->addFindingColumns('pra_monitoring_reports', $this->findingColumnsNoTds);
        $this->addFindingColumns('re_monitoring_reports', $this->findingColumns);

        $this->migrateData('monitoring_reports', 'App\\Models\\MonitoringReport');
        $this->migrateData('pra_monitoring_reports', 'App\\Models\\PraMonitoringReport');
        $this->migrateData('re_monitoring_reports', 'App\\Models\\ReMonitoringReport');

        Schema::dropIfExists('monitoring_findings');
    }

    public function down(): void
    {
        Schema::create('monitoring_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_report_id')->constrained()->cascadeOnDelete();
            $table->text('major')->nullable();
            $table->text('minor')->nullable();
            $table->text('peringatan_awal')->nullable();
            $table->string('ttd_petugas')->nullable();
            $table->string('ttd_pimpinan')->nullable();
            $table->json('penjelasan_isi')->nullable();
            $table->json('penjelasan_isi_3')->nullable();
            $table->text('pengawas')->nullable();
            $table->text('rata_rata_aj')->nullable();
            $table->text('tds')->nullable();
            $table->text('mesin_ozon')->nullable();
            $table->text('note')->nullable();
            $table->text('kondisi_cat')->nullable();
            $table->text('kondisi_awning')->nullable();
            $table->text('kondisi_vinyl')->nullable();
            $table->text('kondisi_stiker_kaca')->nullable();
            $table->timestamps();
        });

        foreach (['monitoring_reports', 'pra_monitoring_reports', 're_monitoring_reports'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn([
                    'major', 'minor', 'peringatan_awal',
                    'ttd_petugas', 'ttd_pimpinan',
                    'penjelasan_isi', 'penjelasan_isi_3',
                    'pengawas', 'rata_rata_aj', 'tds', 'mesin_ozon', 'note',
                    'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca',
                ]);
            });
        }
    }

    private function addFindingColumns(string $table, array $columns): void
    {
        Schema::table($table, function (Blueprint $t) use ($columns) {
            foreach ($columns as $col) {
                if (in_array($col, ['penjelasan_isi', 'penjelasan_isi_3'])) {
                    $t->json($col)->nullable();
                } elseif (in_array($col, ['ttd_petugas', 'ttd_pimpinan'])) {
                    $t->string($col)->nullable();
                } else {
                    $t->text($col)->nullable();
                }
            }
        });
    }

    private function migrateData(string $table, string $reportClass): void
    {
        $findings = DB::table('monitoring_findings')
            ->where('reportable_type', $reportClass)
            ->whereNotNull('reportable_id')
            ->get();

        foreach ($findings as $finding) {
            $updateData = [];
            $cols = $table === 'pra_monitoring_reports' ? $this->findingColumnsNoTds : $this->findingColumns;
            foreach ($cols as $col) {
                if (in_array($col, ['monitoring_report_id', 'reportable_type', 'reportable_id', 'id', 'created_at', 'updated_at'])) {
                    continue;
                }
                if ($col === 'tds' && $table === 'pra_monitoring_reports') {
                    continue;
                }
                $updateData[$col] = $finding->$col ?? null;
            }

            if (!empty($updateData)) {
                DB::table($table)->where('id', $finding->reportable_id)->update($updateData);
            }
        }
    }
};
