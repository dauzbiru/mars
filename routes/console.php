<?php

use App\Models\EvaluasiReport;
use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\ReMonitoringReport;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:cleanup', function () {
    $now = Carbon::now();
    $total = 0;

    // Monitoring: pending > 3 jam
    $cutoffMon = $now->copy()->subHours(3);
    $monIds = MonitoringReport::whereNull('submit_at')
        ->where('checkin_at', '<', $cutoffMon)
        ->pluck('id');
    $monCount = $monIds->count();
    if ($monCount > 0) {
        Result::where('reportable_type', MonitoringReport::class)->whereIn('reportable_id', $monIds)->delete();
        MonitoringReport::whereIn('id', $monIds)->delete();
        $total += $monCount;
    }
    $this->info("Monitoring: deleted {$monCount} report(s).");

    // Pra-Monitoring: pending > 24 jam
    $cutoffPra = $now->copy()->subHours(24);
    $praIds = PraMonitoringReport::whereNull('submit_at')
        ->where('checkin_at', '<', $cutoffPra)
        ->pluck('id');
    $praCount = $praIds->count();
    if ($praCount > 0) {
        Result::where('reportable_type', PraMonitoringReport::class)->whereIn('reportable_id', $praIds)->delete();
        PraMonitoringReport::whereIn('id', $praIds)->delete();
        $total += $praCount;
    }
    $this->info("Pra-Monitoring: deleted {$praCount} report(s).");

    // Re-Monitoring: pending > 3 jam
    $cutoffRe = $now->copy()->subHours(3);
    $reIds = ReMonitoringReport::whereNull('submit_at')
        ->where('checkin_at', '<', $cutoffRe)
        ->pluck('id');
    $reCount = $reIds->count();
    if ($reCount > 0) {
        Result::where('reportable_type', ReMonitoringReport::class)->whereIn('reportable_id', $reIds)->delete();
        ReMonitoringReport::whereIn('id', $reIds)->delete();
        $total += $reCount;
    }
    $this->info("Re-Monitoring: deleted {$reCount} report(s).");

    // Evaluasi: pending > 1 jam
    $cutoffEval = $now->copy()->subHours(1);
    $evalCount = EvaluasiReport::whereNull('tanggal')
        ->where('created_at', '<', $cutoffEval)
        ->delete();
    $total += $evalCount;
    $this->info("Evaluasi: deleted {$evalCount} report(s).");

    $this->info("Total deleted: {$total} report(s).");
})->purpose('Delete unsubmitted pending reports based on timeout rules');
