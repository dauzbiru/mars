<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\MonitoringReport;
use App\Models\Ranking;
use App\Models\ReMonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\SemesterPeriod;
use App\Models\User;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        if ($search) $search = str_replace(['%', '_'], '', $search);

        $existingLabels = $this->getExistingPeriodLabels();

        $periodeLabels = SemesterPeriod::orderByDesc('year')->orderByDesc('start_month')
            ->get()
            ->filter(fn($p) => $existingLabels->contains($p->label))
            ->pluck('label');

        $query = MonitoringReport::with('gerai', 'user')
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at');

        if ($search) {
            $query->whereHas('gerai', function ($q) use ($search) {
                $q->where('kode_gerai', 'like', "%{$search}%")
                  ->orWhere('nama_gerai', 'like', "%{$search}%");
            });
        }

        $reports = $query
            ->select('monitoring_reports.*')
            ->with(['results.item.criteria'])
            ->join('gerais', 'monitoring_reports.gerai_id', '=', 'gerais.id')
            ->orderBy('gerais.kode_gerai')
            ->orderBy('monitoring_reports.submit_at', 'desc')
            ->paginate(50)
            ->through(function ($report) {
                $total = ScoreCalculator::calculateForReport($report);
                return [
                    'id' => $report->id,
                    'gerai' => $report->gerai,
                    'petugas' => $report->user?->name ?? '-',
                    'tanggal' => $report->submit_at,
                    'skor' => $total,
                    'periode_label' => $report->periode_label,
                ];
            });

        $gerais = Gerai::orderBy('kode_gerai')->get(['kode_gerai', 'nama_gerai']);

        return view('ranking.index', compact('reports', 'periodeLabels', 'search', 'gerais'));
    }

    public function excel(Request $request)
    {
        $data = $this->loadRanking($request);
        $reports = $data['reports'];

        $writer = new Writer();

        $periodes = $reports->pluck('periode_label')->filter()->unique()->sort()->values();
        if ($periodes->count() > 1) {
            $periodeSuffix = $periodes->first() . ' s/d ' . $periodes->last();
        } elseif ($periodes->count() === 1) {
            $periodeSuffix = $periodes->first();
        } else {
            $periodeSuffix = 'Semua';
        }
        $filename = storage_path('app/peringkat-monitoring-' . $periodeSuffix . '-' . uniqid('', true) . '.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Peringkat', 'Gerai', 'Kode', 'Franchisee', 'Petugas', 'Tanggal', 'Periode', 'Skor']));

        foreach ($reports as $i => $r) {
            $writer->addRow(Row::fromValues([
                $i + 1,
                $r['gerai']->nama_gerai,
                $r['gerai']->kode_gerai,
                $r['gerai']->franchisee,
                $r['petugas'],
                $r['tanggal']->format('d-m-Y'),
                $r['periode_label'] ?? '-',
                $r['skor'],
            ]));
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function performa(Request $request)
    {
        $gerais = Gerai::active()->orderBy('kode_gerai')->get();
        $geraiId = $request->input('gerai_id');

        $chartLabels = [];
        $chartData = [];
        $reportData = [];
        $geraiNama = '';

        if ($geraiId) {
            $gerai = Gerai::find($geraiId);
            $geraiNama = $gerai ? $gerai->nama_gerai : '';

            $reports = MonitoringReport::with('results.item.criteria')
                ->where('gerai_id', $geraiId)
                ->whereIn('type', ['monitoring', 'import'])
                ->whereNotNull('submit_at')
                ->orderBy('submit_at', 'asc')
                ->take(10)
                ->get();

            foreach ($reports as $report) {
                $total = ScoreCalculator::calculateForReport($report);
                $chartLabels[] = $report->submit_at->format('d-m-Y');
                $chartData[] = round($total, 2);
                $reportData[] = [
                    'tanggal' => $report->submit_at->format('d-m-Y'),
                    'skor' => round($total, 2),
                ];
            }
        }

        return view('ranking.performa', compact('gerais', 'geraiId', 'geraiNama', 'chartLabels', 'chartData', 'reportData'));
    }

    public function pendampingan()
    {
        $now = now();
        $bulan = $now->month;

        $period = SemesterPeriod::where('year', $now->year)
            ->where('start_month', '<=', $bulan)
            ->where('end_month', '>=', $bulan)
            ->first()
            ?? SemesterPeriod::where(function ($q) use ($now, $bulan) {
                    $q->where('year', '<', $now->year)
                      ->orWhere(function ($q2) use ($now, $bulan) {
                          $q2->where('year', $now->year)
                             ->where('end_month', '<', $bulan);
                      });
                })
                ->orderByDesc('year')
                ->orderByDesc('end_month')
                ->first();

        $reports = MonitoringReport::with('gerai', 'user')
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->where('grade', 'C')
            ->when(auth()->user()?->role !== 'admin', fn($q) => $q->where('user_id', auth()->id()))
            ->where('periode_label', $period->label)
            ->join('gerais', 'monitoring_reports.gerai_id', '=', 'gerais.id')
            ->orderBy('gerais.kode_gerai')
            ->select('monitoring_reports.*')
            ->get();

        return view('ranking.pendampingan', compact('reports', 'period'));
    }

    public function nilaiPairing(Request $request)
    {
        $geraiId = $request->input('gerai_id');

        $geraiWithPairing = \App\Models\MonitoringReport::withoutGlobalScope('no_pairing')
            ->where('is_pairing', true)
            ->whereNotNull('submit_at')
            ->distinct()
            ->pluck('gerai_id');

        $gerais = \App\Models\Gerai::whereIn('id', $geraiWithPairing)
            ->orderBy('kode_gerai')
            ->get();

        $selectedGerai = null;
        $items = collect();
        $pairingUsers = collect();
        $nonPairingUsers = collect();
        $resultsByUser = [];

            if ($geraiId && $gerais->contains('id', $geraiId)) {
                $selectedGerai = $gerais->firstWhere('id', $geraiId);
                $pairingData = $this->loadPairingDataForGerai($geraiId);
                $items = $pairingData['items'];
                $pairingUsers = $pairingData['pairingUsers'];
                $nonPairingUsers = $pairingData['nonPairingUsers'];
                $resultsByUser = $pairingData['resultsByUser'];
            }

        return view('ranking.nilai-pairing', compact(
            'gerais', 'selectedGerai', 'items',
            'pairingUsers', 'nonPairingUsers', 'resultsByUser'
        ));
    }

    public function nilaiPairingExcel(Request $request)
    {
        $geraiId = $request->input('gerai_id');
        $gerai = \App\Models\Gerai::find($geraiId);
        if (!$gerai) abort(404);

        $pairingData = $this->loadPairingDataForGerai($geraiId);

        return $this->buildNilaiPairingExcel($gerai, $pairingData['items'], $pairingData['pairingUsers'], $pairingData['nonPairingUsers'], $pairingData['resultsByUser']);
    }

    private function buildNilaiPairingExcel($gerai, $items, $pairingUsers, $nonPairingUsers, $resultsByUser)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($gerai->kode_gerai);

        $col = 1;
        $sheet->getCellByColumnAndRow($col++, 1)->setValue('Kriteria');
        $sheet->getCellByColumnAndRow($col++, 1)->setValue('Bobot');
        foreach ($nonPairingUsers as $u) {
            $sheet->getCellByColumnAndRow($col++, 1)->setValue($u->name);
        }
        foreach ($pairingUsers as $u) {
            $sheet->getCellByColumnAndRow($col++, 1)->setValue($u->name);
        }

        $sheet->getCellByColumnAndRow(1, 2)->setValue('');
        $sheet->getCellByColumnAndRow(2, 2)->setValue('');
        for ($i = 0; $i < $nonPairingUsers->count(); $i++) {
            $sheet->getCellByColumnAndRow(3 + $i, 2)->setValue('Non-Pairing');
        }
        for ($i = 0; $i < $pairingUsers->count(); $i++) {
            $sheet->getCellByColumnAndRow(3 + $nonPairingUsers->count() + $i, 2)->setValue('Pairing');
        }

        for ($c = 1; $c < $col; $c++) {
            $sheet->getCellByColumnAndRow($c, 1)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($c, 2)->getFont()->setBold(true);
        }

        $row = 3;
        foreach ($items as $cat) {
            foreach ($cat->items as $item) {
                $c = 1;
                $sheet->getCellByColumnAndRow($c++, $row)->setValue($item->name);
                $sheet->getCellByColumnAndRow($c++, $row)->setValue($item->bobot);
                foreach ($nonPairingUsers as $u) {
                    $sheet->getCellByColumnAndRow($c++, $row)->setValue($resultsByUser[$u->id][$item->id] ?? '-');
                }
                foreach ($pairingUsers as $u) {
                    $sheet->getCellByColumnAndRow($c++, $row)->setValue($resultsByUser[$u->id][$item->id] ?? '-');
                }
                $row++;
            }
        }

        for ($c = 1; $c < $col; $c++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'nilai-pairing-' . $gerai->kode_gerai . '.xlsx';
        $writer->save(storage_path('app/' . $filename));

        return response()->download(storage_path('app/' . $filename), $filename)->deleteFileAfterSend(true);
    }

    public function markWaSent($reportId)
    {
        $report = MonitoringReport::findOrFail($reportId);
        $sent = !$report->wa_sent_at;
        $report->update(['wa_sent_at' => $sent ? now() : null]);
        return response()->json(['ok' => true, 'sent' => $sent]);
    }

    public function praMonitoring(Request $request)
    {
        $search = $request->input('search');
        if ($search) $search = str_replace(['%', '_'], '', $search);

        $query = \App\Models\PraMonitoringReport::with('gerai', 'user')
            ->whereNotNull('submit_at');

        if ($search) {
            $query->whereHas('gerai', function ($q) use ($search) {
                $q->where('kode_gerai', 'like', "%{$search}%")
                  ->orWhere('nama_gerai', 'like', "%{$search}%");
            });
        }

        $reports = $query
            ->select('pra_monitoring_reports.*')
            ->with(['results.item.criteria'])
            ->join('gerais', 'pra_monitoring_reports.gerai_id', '=', 'gerais.id')
            ->orderBy('gerais.kode_gerai')
            ->orderBy('pra_monitoring_reports.submit_at', 'desc')
            ->paginate(50)
            ->through(function ($report) {
                $total = ScoreCalculator::calculateForReport($report);
                return [
                    'gerai' => $report->gerai,
                    'petugas' => $report->user?->name ?? '-',
                    'tanggal' => $report->submit_at,
                    'skor' => $total,
                ];
            });

        $gerais = Gerai::orderBy('kode_gerai')->get(['kode_gerai', 'nama_gerai']);
        return view('ranking.pra-monitoring', compact('reports', 'search', 'gerais'));
    }

    public function peringkat(Request $request)
    {
        $data = $this->loadPeringkat($request->input('periode'));
        return view('ranking.peringkat', $data);
    }

    public function peringkatExcel(Request $request)
    {
        $selectedPeriode = $request->input('periode');
        $data = $this->loadPeringkat($selectedPeriode);
        $rows = $data['rows'];
        $colLabels = $data['colLabels'];

        $writer = new Writer();
        $periodeSuffix = $selectedPeriode ?? 'Semua';
        $filename = storage_path('app/Peringkat Monitoring Gabungan (' . $periodeSuffix . ')-' . uniqid('', true) . '.xlsx');
        $writer->openToFile($filename);

        $headers = ['No', 'Kode Gerai', 'Nama Gerai'];
        $headers[] = $colLabels[2] ?? 'Terlama';
        $headers[] = $colLabels[1] ?? 'Sebelumnya';
        $headers[] = $colLabels[0] ?? 'Terbaru';
        $writer->addRow(Row::fromValues($headers));

        foreach ($rows as $i => $r) {
            $writer->addRow(Row::fromValues([
                $i + 1,
                $r['gerai']->kode_gerai,
                $r['gerai']->nama_gerai,
                isset($r['p1']['skor']) ? round($r['p1']['skor']) : '-',
                isset($r['p2']['skor']) ? round($r['p2']['skor']) : '-',
                isset($r['p3']['skor']) ? round($r['p3']['skor']) : '-',
            ]));
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function peringkatRankings(Request $request)
    {
        $selectedPeriode = $request->input('periode');

        $query = Ranking::with('gerai');
        if ($selectedPeriode) {
            $query->where('periode_label', $selectedPeriode);
        }

        $rankings = $query->get()
            ->sort(fn($a, $b) => [$a->gerai->kode_gerai, $a->periode_label] <=> [$b->gerai->kode_gerai, $b->periode_label])
            ->values();

        $writer = new Writer();
        $periodeSuffix = $selectedPeriode ?? 'Semua';
        $filename = storage_path('app/Urutan Peringkat (' . $periodeSuffix . ')-' . uniqid('', true) . '.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Kode Gerai', 'Nama Gerai', 'Peringkat', 'Total Gerai', 'Periode']));

        foreach ($rankings as $r) {
            $writer->addRow(Row::fromValues([
                $r->gerai->kode_gerai ?? '-',
                $r->gerai->nama_gerai ?? '-',
                $r->rank . ' / ' . $r->total,
                $r->total,
                $r->periode_label,
            ]));
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    private function loadPeringkat($selectedPeriode = null)
    {
        $existingLabels = $this->getExistingPeriodLabels();

        $periodeLabels = SemesterPeriod::orderByDesc('year')->orderByDesc('start_month')
            ->get()
            ->filter(fn($p) => $existingLabels->contains($p->label))
            ->pluck('label')
            ->values();

        if (!$selectedPeriode && $periodeLabels->isNotEmpty()) {
            $selectedPeriode = $periodeLabels->first();
        }

        if ($selectedPeriode && $periodeLabels->contains($selectedPeriode)) {
            $idx = $periodeLabels->search($selectedPeriode);
            $periodKeys = [
                $periodeLabels[$idx] ?? null,
                $periodeLabels[$idx + 1] ?? null,
                $periodeLabels[$idx + 2] ?? null,
            ];
        } else {
            $periodKeys = [null, null, null];
        }

        $periodKeys = array_filter($periodKeys);
        $selectedKey = $periodKeys[0] ?? null;

        $geraiIds = $selectedKey
            ? MonitoringReport::whereIn('type', ['monitoring', 'import'])
                ->whereNotNull('submit_at')
                ->where('periode_label', $selectedKey)
                ->distinct()
                ->pluck('gerai_id')
            : collect();

        $gerais = $geraiIds->isNotEmpty()
            ? Gerai::whereIn('id', $geraiIds)->orderBy('kode_gerai')->get()
            : collect();

        $allReports = $geraiIds->isNotEmpty()
            ? MonitoringReport::whereIn('gerai_id', $geraiIds)
                ->whereIn('type', ['monitoring', 'import'])
                ->whereNotNull('submit_at')
                ->whereIn('periode_label', $periodKeys)
                ->get()
                ->groupBy('gerai_id')
            : collect();

        $rows = [];
        foreach ($gerais as $gerai) {
            $reports = $allReports->get($gerai->id, collect())->keyBy('periode_label');

            $scores = [];
            foreach ($periodKeys as $k) {
                $r = $k && isset($reports[$k]) ? $reports[$k] : null;
                $scores[] = $r ? [
                    'periode' => $k,
                    'skor' => $r->nilai !== null ? round((float) $r->nilai) : 0,
                ] : null;
            }

            $rows[] = [
                'gerai' => $gerai,
                'p3' => $scores[0] ?? null,
                'p2' => $scores[1] ?? null,
                'p1' => $scores[2] ?? null,
            ];
        }

        usort($rows, function ($a, $b) {
            $sa = $a['p3']['skor'] ?? 0;
            $sb = $b['p3']['skor'] ?? 0;
            if ($sb !== $sa) return $sb <=> $sa;
            $sa = $a['p2']['skor'] ?? 0;
            $sb = $b['p2']['skor'] ?? 0;
            if ($sb !== $sa) return $sb <=> $sa;
            $sa = $a['p1']['skor'] ?? 0;
            $sb = $b['p1']['skor'] ?? 0;
            if ($sb !== $sa) return $sb <=> $sa;
            $ta = $a['gerai']->opening_at?->timestamp ?? 0;
            $tb = $b['gerai']->opening_at?->timestamp ?? 0;
            return $ta <=> $tb;
        });

        $colLabels = [
            $periodKeys[0] ?? 'Terbaru',
            $periodKeys[1] ?? 'Sebelumnya',
            $periodKeys[2] ?? 'Terlama',
        ];

        $latestScores = array_filter(array_column(array_column($rows, 'p3'), 'skor'), fn($v) => $v !== null);
        $totalLatest = count($latestScores);
        $threshold = MonitoringReport::GRADE_B_THRESHOLD;
        $countGe975 = count(array_filter($latestScores, fn($s) => round($s) >= $threshold));
        $countLe974 = $totalLatest - $countGe975;
        $pctGe975 = $totalLatest > 0 ? round($countGe975 / $totalLatest * 100, 2) : 0;
        $pctLe974 = $totalLatest > 0 ? round($countLe974 / $totalLatest * 100, 2) : 0;

        $gradeCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
        foreach ($latestScores as $skor) {
            $grade = \App\Models\MonitoringReport::gradeFromScore($skor);
            $gradeCounts[$grade]++;
        }
        $gradePcts = [];
        foreach ($gradeCounts as $grade => $count) {
            $gradePcts[$grade] = $totalLatest > 0 ? round($count / $totalLatest * 100, 2) : 0;
        }

        return compact('rows', 'colLabels', 'pctGe975', 'pctLe974', 'totalLatest', 'gradeCounts', 'gradePcts', 'periodeLabels', 'selectedPeriode');
    }

    public function importForm()
    {
        return view('ranking.import');
    }

    public function template()
    {
        $writer = new Writer();
        $filename = storage_path('app/template-import-nilai-' . uniqid('', true) . '.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Kode Gerai', 'Nama Gerai', 'Tanggal', 'Petugas', 'Skor']));
        $writer->addRow(Row::fromValues(['G001', 'Gerai A', '15-01-2022', 'username', '85.5']));
        $writer->addRow(Row::fromValues(['G002', 'Gerai B', '20-02-2022', 'username', '72']));

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx']);

        $reader = new XLSXReader();
        $reader->open($request->file('file'));

        $rows = [];
        $errors = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $isFirst = true;
            foreach ($sheet->getRowIterator() as $rowObj) {
                $cells = $rowObj->cells;
                $values = array_map(fn($c) => trim((string) $c->getValue()), $cells);

                if ($isFirst) {
                    $isFirst = false;
                    continue;
                }

                if (empty($values[0])) continue;

                $rows[] = $values;
            }
        }

        $reader->close();

        // Validate all rows first, reject all if any error
        $validatedRows = [];
        foreach ($rows as $values) {
            $namaGerai = $values[1] ?? '';
            $gerai = null;
            if ($namaGerai) {
                $gerai = Gerai::where('kode_gerai', $values[0])->where('nama_gerai', $namaGerai)->first();
            }
            if (!$gerai) {
                $gerai = Gerai::active()->where('kode_gerai', $values[0])->first()
                    ?? Gerai::where('kode_gerai', $values[0])->latest()->first();
            }
            if (!$gerai) {
                $errors[] = "Kode gerai '{$values[0]}' tidak ditemukan";
                continue;
            }

            $tanggalRaw = $values[2] ?? null;
            if (!$tanggalRaw) {
                $errors[] = "Tanggal tidak valid untuk gerai {$values[0]}";
                continue;
            }
            try {
                $tanggal = \Carbon\Carbon::createFromFormat('d-m-Y', $tanggalRaw);
            } catch (\Exception $e) {
                try {
                    $tanggal = \Carbon\Carbon::parse($tanggalRaw);
                } catch (\Exception $e2) {
                    $errors[] = "Format tanggal salah untuk gerai {$values[0]}: {$tanggalRaw} (harus DD-MM-YYYY)";
                    continue;
                }
            }

            $petugas = User::where('name', $values[3] ?? '')->orWhere('username', $values[3] ?? '')->first();
            if (!$petugas) {
                $errors[] = "Petugas '{$values[3]}' tidak ditemukan (gerai {$values[0]})";
                continue;
            }

            $skor = is_numeric($values[4] ?? '') ? (float) $values[4] : null;

            $matched = SemesterPeriod::where('year', $tanggal->year)
                ->where('start_month', '<=', $tanggal->month)
                ->where('end_month', '>=', $tanggal->month)
                ->first();

            $validatedRows[] = compact('gerai', 'petugas', 'skor', 'tanggal', 'matched');
        }

        if (!empty($errors)) {
            $safeErrors = array_map(fn($e) => e($e), $errors);
            return redirect('/daftar-nilai/import')->with('error',
                'Import dibatalkan. ' . count($safeErrors) . ' error ditemukan:<br>' . implode('<br>', $safeErrors));
        }

        // All valid — import
        $unmatchedDates = [];
        foreach ($validatedRows as $v) {
            $periodeLabel = $v['matched'] ? $v['matched']->label : null;

            $existing = $periodeLabel
                ? MonitoringReport::where('gerai_id', $v['gerai']->id)
                    ->where('type', 'import')
                    ->where('periode_label', $periodeLabel)
                    ->whereNotNull('submit_at')
                    ->first()
                : null;

            if ($existing) {
                $existing->update([
                    'user_id' => $v['petugas']->id,
                    'nilai' => $v['skor'],
                    'grade' => $v['skor'] !== null ? \App\Models\MonitoringReport::gradeFromScore($v['skor']) : null,
                    'submit_at' => $v['tanggal'],
                ]);
            } else {
                MonitoringReport::create([
                    'gerai_id' => $v['gerai']->id,
                    'user_id' => $v['petugas']->id,
                    'type' => 'import',
                    'nilai' => $v['skor'],
                    'grade' => $v['skor'] !== null ? \App\Models\MonitoringReport::gradeFromScore($v['skor']) : null,
                    'periode_label' => $periodeLabel,
                    'checkin_at' => $v['tanggal'],
                    'submit_at' => $v['tanggal'],
                ]);
            }

            if (!$v['matched']) {
                $unmatchedDates[] = $v['tanggal']->copy();
            }
        }

        if ($unmatchedDates) {
            $minDate = min($unmatchedDates);
            $maxDate = max($unmatchedDates);

            $period = SemesterPeriod::firstOrCreate([
                'start_month' => $minDate->month,
                'end_month' => $maxDate->month,
                'year' => $minDate->year,
            ]);

            MonitoringReport::where('type', 'import')
                ->whereNull('periode_label')
                ->whereDate('submit_at', '>=', $minDate->startOfMonth())
                ->whereDate('submit_at', '<=', $maxDate->endOfMonth())
                ->update(['periode_label' => $period->label]);
        }

        $total = count($validatedRows);
        return redirect('/daftar-nilai/import')->with('success', "Berhasil import {$total} data.");
    }

    private function loadRanking(Request $request)
    {
        $periodeLabel = $request->input('periode_label');
        $search = $request->input('search');
        if ($search) $search = str_replace(['%', '_'], '', $search);

        $existingLabels = $this->getExistingPeriodLabels();

        $periodeLabels = SemesterPeriod::orderByDesc('year')->orderByDesc('start_month')
            ->get()
            ->filter(fn($p) => $existingLabels->contains($p->label))
            ->pluck('label');

        $query = MonitoringReport::with('gerai', 'user', 'results.item.criteria')
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at');

        if ($periodeLabel) {
            $query->where('periode_label', $periodeLabel);
        }

        if ($search) {
            $query->whereHas('gerai', function ($q) use ($search) {
                $q->where('kode_gerai', 'like', "%{$search}%")
                  ->orWhere('nama_gerai', 'like', "%{$search}%");
            });
        }

        $reports = $query->get()->map(function ($report) {
            $total = ScoreCalculator::calculateForReport($report);
            return [
                'id' => $report->id,
                'gerai' => $report->gerai,
                'petugas' => $report->user?->name ?? '-',
                'tanggal' => $report->submit_at,
                'skor' => $total,
                'periode_label' => $report->periode_label,
            ];
        });

        $reports = $reports->sort(function ($a, $b) {
            $cmp = strcmp($a['gerai']->kode_gerai, $b['gerai']->kode_gerai);
            if ($cmp !== 0) return $cmp;
            return $a['tanggal']->timestamp <=> $b['tanggal']->timestamp;
        })->values();

        return compact('reports', 'periodeLabels', 'periodeLabel', 'search');
    }

    public function update(Request $request, MonitoringReport $report)
    {
        $request->validate([
            'nilai' => 'required|numeric|min:0|max:1000',
            'checkin_at' => 'required|date',
            'petugas' => 'required|string|max:255',
        ]);

        $petugas = User::where('name', $request->input('petugas'))->orWhere('username', $request->input('petugas'))->first();
        if ($petugas) {
            $report->user_id = $petugas->id;
        }

        $nilai = (float) $request->input('nilai');
        $report->nilai = $nilai;
        $report->grade = MonitoringReport::gradeFromScore($nilai);
        $report->checkin_at = \Carbon\Carbon::parse($request->input('checkin_at'));
        $report->submit_at = \Carbon\Carbon::parse($request->input('checkin_at'));
        $report->save();

        return redirect('/daftar-nilai')->with('success', 'Nilai berhasil diperbarui.');
    }

    public function destroy(MonitoringReport $report)
    {
        $report->results()->delete();
        
        $report->delete();

        return redirect('/daftar-nilai')->with('success', 'Nilai berhasil dihapus.');
    }

    public function hapusPeriode(Request $request)
    {
        $periodeLabel = $request->input('periode_label');

        if (!$periodeLabel) {
            return redirect('/daftar-nilai')->with('error', 'Pilih periode terlebih dahulu.');
        }

        $reports = MonitoringReport::whereIn('type', ['monitoring', 'import'])
            ->where('periode_label', $periodeLabel)
            ->whereNotNull('submit_at')
            ->get();

        $count = 0;
        foreach ($reports as $report) {
            $report->results()->delete();
            
            $report->delete();
            $count++;
        }

        return redirect('/daftar-nilai')->with('success', "Berhasil menghapus {$count} data nilai periode {$periodeLabel}.");
    }

    private function loadPairingDataForGerai(int $geraiId): array
    {
        $items = \App\Models\Category::whereNull('parent_id')
            ->with(['items.criteria'])
            ->orderBy('sort')
            ->get();

        $allReports = \App\Models\MonitoringReport::where('gerai_id', $geraiId)
            ->whereNotNull('submit_at')
            ->get(['id', 'is_pairing']);

        $pairingReportIds = $allReports->where('is_pairing', true)->pluck('id');
        $nonPairingReportIds = $allReports->where('is_pairing', false)->pluck('id');

        $allResults = \App\Models\Result::where(function ($q) use ($pairingReportIds, $nonPairingReportIds) {
                $q->whereIn('reportable_id', $pairingReportIds)
                  ->orWhereIn('reportable_id', $nonPairingReportIds);
            })
            ->where('reportable_type', \App\Models\MonitoringReport::class)
            ->whereNotNull('criterion_id')
            ->with('user', 'criterion')
            ->get();

        $pairingUsers = $allResults->filter(fn($r) => $pairingReportIds->contains($r->reportable_id))
            ->pluck('user')->unique('id')->values();

        $nonPairingUsers = $allResults->filter(fn($r) => $nonPairingReportIds->contains($r->reportable_id))
            ->pluck('user')->unique('id')->values();

        $resultsByUser = [];
        foreach ($allResults as $r) {
            $item = $r->item;
            $resultsByUser[$r->user_id][$r->item_id] = \App\Services\ScoreCalculator::calculateItemScore($item, $r);
        }

        return compact('items', 'pairingUsers', 'nonPairingUsers', 'resultsByUser');
    }

    private function getExistingPeriodLabels(): \Illuminate\Support\Collection
    {
        return MonitoringReport::whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->whereNotNull('periode_label')
            ->distinct()
            ->pluck('periode_label');
    }
}
