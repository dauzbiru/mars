<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Gerai;
use App\Models\MonitoringReport;
use App\Models\SemesterPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $totalGerai = Gerai::active()->count();

        $periods = SemesterPeriod::orderBy('year', 'desc')->orderBy('start_month', 'desc')->get();

        $latestPeriod = $periods->first();

        $monitoringPeriode = 0;
        $periodeLabel = '';
        if ($latestPeriod) {
            $periodeLabel = $latestPeriod->label;
            $monitoringPeriode = MonitoringReport::where('type', 'monitoring')
                ->where('periode_label', $latestPeriod->label)
                ->whereNotNull('submit_at')
                ->count();
        }

        $praMonitoringBulanIni = \App\Models\PraMonitoringReport::whereMonth('checkin_at', now()->month)
            ->whereYear('checkin_at', now()->year)
            ->whereNotNull('submit_at')
            ->count();

        $monitoringTerbaru = MonitoringReport::with('gerai')
            ->where('type', 'monitoring')
            ->whereNotNull('submit_at')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $praMonitoringTerbaru = \App\Models\PraMonitoringReport::with('gerai')
            ->whereNotNull('submit_at')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $chartPeriods = $periods->take(3);
        $chartLabels = $chartPeriods->pluck('label')->toArray();

        $allNilai = MonitoringReport::whereIn('type', ['monitoring', 'import'])
            ->whereIn('periode_label', $chartLabels)
            ->whereNotNull('nilai')
            ->selectRaw('periode_label, nilai')
            ->get()
            ->groupBy('periode_label');

        $chartData = [];
        foreach ($chartLabels as $label) {
            $nilaiList = $allNilai->get($label, collect())->pluck('nilai');
            $counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
            foreach ($nilaiList as $nilai) {
                $grade = MonitoringReport::gradeFromScore((float) $nilai);
                $counts[$grade]++;
            }
            $chartData[] = $counts;
        }

        $chartLabels = array_reverse($chartLabels);
        $chartData = array_reverse($chartData);

        $selectedPeriod = $chartLabels[count($chartLabels) - 1] ?? null;

        return view('dashboard', compact(
            'totalGerai',
            'monitoringPeriode',
            'praMonitoringBulanIni',
            'periodeLabel',
            'monitoringTerbaru',
            'praMonitoringTerbaru',
            'periods',
            'chartLabels',
            'chartData',
            'selectedPeriod'
        ));
    }

    public function chartData(Request $request)
    {
        $periodLabel = $request->input('period');

        $periods = SemesterPeriod::orderBy('year', 'desc')->orderBy('start_month', 'desc')->get();

        $selectedIndex = $periods->search(fn($p) => $p->label === $periodLabel);
        if ($selectedIndex === false) {
            $selectedIndex = 0;
        }

        $takePeriods = $periods->slice($selectedIndex, 3);
        if ($takePeriods->count() < 3) {
            $takePeriods = $periods->take(3);
        }

        $labels = $takePeriods->pluck('label')->toArray();

        $allNilai = MonitoringReport::whereIn('type', ['monitoring', 'import'])
            ->whereIn('periode_label', $labels)
            ->whereNotNull('nilai')
            ->selectRaw('periode_label, nilai')
            ->get()
            ->groupBy('periode_label');

        $data = [];
        foreach ($labels as $label) {
            $nilaiList = $allNilai->get($label, collect())->pluck('nilai');
            $counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
            foreach ($nilaiList as $nilai) {
                $grade = MonitoringReport::gradeFromScore((float) $nilai);
                $counts[$grade]++;
            }
            $data[] = $counts;
        }

        $labels = array_reverse($labels);
        $data = array_reverse($data);

        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }
}
