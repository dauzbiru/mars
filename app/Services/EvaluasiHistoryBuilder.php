<?php

namespace App\Services;

use App\Models\Gerai;
use App\Models\Ranking;
use App\Models\SemesterPeriod;
use Illuminate\Support\Collection;

class EvaluasiHistoryBuilder
{
    private int $geraiId;
    private Collection $history;
    private Collection $rankingsMap;
    private Collection $totalMap;
    private Collection $periodsMap;

    public function __construct(int $geraiId)
    {
        $this->geraiId = $geraiId;
        $this->build();
    }

    private function build(): void
    {
        $remonReport = \App\Models\ReMonitoringReport::where('gerai_id', $this->geraiId)
            ->whereNotNull('submit_at')->whereNotNull('nilai')
            ->orderByDesc('checkin_at')->first();

        $latestMonReport = \App\Models\MonitoringReport::where('gerai_id', $this->geraiId)
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')->whereNotNull('nilai')
            ->orderByDesc('checkin_at')->first();

        $useRemon = $remonReport && (!$latestMonReport || $remonReport->checkin_at->gt($latestMonReport->checkin_at));

        $monReports = \App\Models\MonitoringReport::where('gerai_id', $this->geraiId)
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')->whereNotNull('nilai')
            ->orderBy('checkin_at')
            ->limit($useRemon ? 9 : 10)
            ->get();

        $this->history = $useRemon ? $monReports->push($remonReport) : $monReports;

        $periodeLabels = $this->history->pluck('periode_label')->filter()->unique()->values()->toArray();

        $rankings = Ranking::where('gerai_id', $this->geraiId)
            ->whereIn('periode_label', $periodeLabels)
            ->get();
        $this->rankingsMap = $rankings->pluck('rank', 'periode_label');
        $this->totalMap = $rankings->groupBy('periode_label')->map(fn($g) => $g->max('total'));

        $this->periodsMap = SemesterPeriod::get()->filter(fn($p) => in_array($p->label, $periodeLabels))->keyBy('label');
    }

    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function getLastReport()
    {
        return $this->history->last();
    }

    public function mapHistoryData(callable $customizer = null): Collection
    {
        $rankingsMap = $this->rankingsMap;
        $totalMap = $this->totalMap;
        $periodsMap = $this->periodsMap;

        return $this->history->map(function ($r) use ($rankingsMap, $totalMap, $periodsMap, $customizer) {
            $periodeLabel = $r->periode_label;
            $rank = null;
            $total = null;
            $periodeShort = null;

            if ($periodeLabel) {
                $period = $periodsMap->get($periodeLabel);
                if ($period) {
                    $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
                    $periodeShort = ($months[$period->start_month] ?? $period->start_month) . '-' . ($months[$period->end_month] ?? $period->end_month);
                } else {
                    $shortMonths = ['Januari'=>'Jan','Februari'=>'Feb','Maret'=>'Mar','April'=>'Apr','Mei'=>'Mei','Juni'=>'Jun','Juli'=>'Jul','Agustus'=>'Agu','September'=>'Sep','Oktober'=>'Okt','November'=>'Nov','Desember'=>'Des'];
                    $cleaned = preg_replace('/\s+\d{4}$/', '', $periodeLabel);
                    $periodeShort = str_replace(array_keys($shortMonths), array_values($shortMonths), $cleaned);
                }
                $rank = $rankingsMap->get($periodeLabel);
                $total = $totalMap->get($periodeLabel);
            } else {
                $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                $periodeShort = $months[(int) $r->checkin_at->format('m')] ?? '';
            }

            $data = [
                'type' => match(class_basename($r)) {
                    'ReMonitoringReport' => 're-monitoring',
                    default => $r->type ?? 'monitoring',
                },
                'year' => $r->checkin_at->format('Y'),
                'periode_label' => $periodeLabel,
                'periode_short' => $periodeShort ?? '',
                'nilai' => $r->nilai,
                'grade' => $r->grade,
                'rank' => $rank,
                'total' => $total,
                'finding' => $r->only([
                    'major', 'minor', 'peringatan_awal', 'note',
                    'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca',
                    'pengawas', 'rata_rata_aj', 'tds', 'mesin_ozon',
                    'penjelasan_isi', 'penjelasan_isi_3',
                ]),
            ];

            if ($customizer) {
                $data = $customizer($data, $r);
            }

            return $data;
        })->values();
    }
}
