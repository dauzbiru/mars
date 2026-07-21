<?php

namespace App\Console\Commands;

use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\Result;
use App\Models\MonitoringFinding;
use Illuminate\Console\Command;

class CleanupPairingReports extends Command
{
    protected $signature = 'app:cleanup-pairing-reports';
    protected $description = 'Hapus laporan pairing yang sudah lebih dari 2 bulan';

    public function handle()
    {
        $cutoff = now()->subMonths(2);

        foreach ([MonitoringReport::class, PraMonitoringReport::class] as $model) {
            $reports = $model::withoutGlobalScope('no_pairing')
                ->where('is_pairing', true)
                ->where('created_at', '<', $cutoff)
                ->get();

            foreach ($reports as $report) {
                Result::where('reportable_type', get_class($report))
                    ->where('reportable_id', $report->id)
                    ->delete();

                if (method_exists($report, 'finding') && $report->finding) {
                    $report->finding->delete();
                }

                $report->delete();
            }

            $this->info("Dihapus " . $reports->count() . " laporan pairing dari " . class_basename($model));
        }

        return Command::SUCCESS;
    }
}
