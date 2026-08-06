<?php

namespace App\Console\Commands;

use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\Result;
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

            if ($reports->isNotEmpty()) {
                $class = get_class($reports->first());
                $ids = $reports->pluck('id');

                Result::where('reportable_type', $class)
                    ->whereIn('reportable_id', $ids)
                    ->delete();

                $class::withoutGlobalScope('no_pairing')->whereIn('id', $ids)->get()->each->delete();
            }

            $this->info("Dihapus " . $reports->count() . " laporan pairing dari " . class_basename($model));
        }

        return Command::SUCCESS;
    }
}
