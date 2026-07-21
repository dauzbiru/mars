<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MonitoringReport;
use App\Models\Result;
use App\Models\SemesterPeriod;
use App\Models\User;
use App\Services\ScoreCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use ZipArchive;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return $this->listByType($request, MonitoringReport::class, 'monitoring', 'Laporan Monitoring', [
            'filterByType' => true,
        ]);
    }

    public function preMonitoring(Request $request)
    {
        return $this->listByType($request, \App\Models\PraMonitoringReport::class, 'pra-monitoring', 'Laporan Pra-Monitoring');
    }

    public function reMonitoring(Request $request)
    {
        return $this->listByType($request, \App\Models\ReMonitoringReport::class, 're-monitoring', 'Laporan Re-Monitoring');
    }

    public function evaluasi(Request $request)
    {
        return $this->listByType($request, \App\Models\EvaluasiReport::class, 'evaluasi', 'Laporan Evaluasi', [
            'submittedColumn' => 'tanggal',
            'orderBy' => 'tanggal',
            'loadResults' => false,
            'calculateScore' => false,
        ]);
    }

    private function listByType(
        Request $request,
        string $modelClass,
        string $type,
        string $title,
        array $options = []
    ): \Illuminate\View\View {
        $search = $request->input('search');
        if ($search) $search = str_replace(['%', '_'], '', $search);

        $periods = SemesterPeriod::orderBy('year', 'desc')->orderBy('start_month', 'desc')->get();

        $query = $modelClass::with('gerai', 'user')->withoutGlobalScope('no_pairing');

        if ($options['filterByType'] ?? false) {
            $query->where('type', $type);
        }

        $query->whereNotNull($options['submittedColumn'] ?? 'submit_at');

        if (Auth::user()->role === 'guest') {
            $query->where('user_id', Auth::id());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('gerai', function ($g) use ($search) {
                    $g->where('kode_gerai', 'like', "%{$search}%")
                      ->orWhere('nama_gerai', 'like', "%{$search}%");
                })->orWhereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                });
            });
        }

        $orderBy = $options['orderBy'] ?? 'checkin_at';

        $reports = $query
            ->when($options['loadResults'] ?? true, fn($q) => $q->with(['results.item.criteria']))
            ->orderBy($orderBy, 'desc')
            ->paginate(50)
            ->through(function ($report) use ($options) {
                if ($options['calculateScore'] ?? true) {
                    $report->total_score = ScoreCalculator::calculateForReport($report);
                    $report->grade = \App\Models\MonitoringReport::gradeFromScore((float) $report->total_score);
                } else {
                    $report->total_score = null;
                    $report->grade = null;
                }
                return $report;
            });

        $gerais = \App\Models\Gerai::orderBy('kode_gerai')->get(['kode_gerai', 'nama_gerai']);

        return view('report.index', compact('reports', 'title', 'type', 'periods', 'gerais'));
    }

    public function pdf(Request $request)
    {
        $userId = $request->query('user_id');
        $user = $userId ? User::find($userId) : null;

        $categories = Category::whereNull('parent_id')->with('items.criteria')->get();
        $results = Result::with('criterion')->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get()->keyBy('item_id');

        $pdf = Pdf::loadView('report.pdf', compact('categories', 'results', 'user'));
        return $pdf->download('laporan-audit.pdf');
    }

    public function excel(Request $request)
    {
        $userId = $request->query('user_id');
        $user = $userId ? User::find($userId) : null;

        $categories = Category::whereNull('parent_id')->with('items.criteria')->get();
        $results = Result::with('criterion')->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get()->keyBy('item_id');

        $writer = new Writer();
        $filename = storage_path('app/laporan-audit.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['No', 'Tugas', 'Checklist', 'Nilai', 'Catatan']));

        $no = 1;
        foreach ($categories as $cat) {
            foreach ($cat->items as $item) {
                $result = $results->get($item->id);
                $nilai = $result && $result->criterion ? $result->criterion->description : '-';
                $writer->addRow(Row::fromValues([
                    $no++, $cat->name, $item->name, $nilai, $result?->notes ?? '',
                ]));
            }
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function analytics()
    {
        $periods = SemesterPeriod::orderBy('year', 'desc')->orderBy('start_month', 'desc')->get();
        return view('report.analytics', compact('periods'));
    }

    public function analyticsExcel(Request $request)
    {
        $request->validate(['semester_period_id' => 'required|exists:semester_periods,id']);
        $period = SemesterPeriod::findOrFail($request->semester_period_id);
        $periodeLabel = $period->label;

        $reports = MonitoringReport::with('gerai', 'results.item.criteria', 'results.criterion')
            ->where('type', 'monitoring')
            ->whereNotNull('submit_at')
            ->where('periode_label', $periodeLabel)
            ->get();

        $geraiKodes = $reports->pluck('gerai.kode_gerai')->unique()->sort()->values()->toArray();

        $allCategories = Category::with('items.criteria')->get()->keyBy('id');

        $itemScores = [];
        foreach ($allCategories as $cat) {
            foreach ($cat->items as $item) {
                if (!$item->bobot) continue;
                $itemScores[$item->id] = [
                    'bobot' => (float) $item->bobot,
                    'scores' => [],
                ];
            }
        }

        foreach ($reports as $report) {
            $geraiKode = $report->gerai->kode_gerai;
            foreach ($report->results as $result) {
                $itemId = $result->item_id;
                if (!isset($itemScores[$itemId])) continue;
                $item = $result->item;
                if (!$item || !$item->bobot) continue;
                $criteriaCount = $item->criteria->count();
                if ($criteriaCount <= 1) {
                    $itemScores[$itemId]['scores'][$geraiKode] = round((float) $item->bobot, 2);
                } else {
                    $interval = $item->bobot / ($criteriaCount - 1);
                    $idx = $item->criteria->search(fn($c) => $c->id === $result->criterion_id);
                    if ($idx !== false) {
                        $itemScores[$itemId]['scores'][$geraiKode] = round($item->bobot - ($interval * $idx), 2);
                    }
                }
            }
        }

        $sections = [
            [
                'name' => 'Karyawan & Pimpinan Gerai',
                'groups' => [
                    ['name' => 'Pelayanan', 'category_ids' => [2, 3, 4]],
                    ['name' => 'Penampilan & Tingkah Laku Karyawan', 'category_ids' => [6, 7, 8]],
                ],
                'category_ids' => [5, 9],
            ],
            [
                'name' => 'Tampilan Gerai',
                'category_ids' => [10, 11, 12, 13, 14],
            ],
            [
                'name' => 'Produk Operasional',
                'category_ids' => [15, 16, 17, 19, 18, 20],
            ],
        ];

        $writer = new Writer();
        $filename = storage_path('app/analisis-minmax-' . now()->format('Y-m-d_H-i') . '.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Analisis Checklist - ' . $periodeLabel]));
        $writer->addRow(Row::fromValues([]));

        $header = ['Checklist'];
        foreach ($geraiKodes as $kode) {
            $header[] = $kode;
        }
        $header[] = 'Rata-rata';
        $header[] = 'Min';
        $header[] = 'Max';
        $writer->addRow(Row::fromValues($header));

        $helper = new class($allCategories, $itemScores, $geraiKodes, $writer) {
            public function __construct(
                private $categories,
                private $itemScores,
                private $geraiKodes,
                private $writer,
            ) {}

            public function aggregate(array $catIds): array
            {
                $geraiScores = [];
                $geraiPcts = [];
                foreach ($this->geraiKodes as $kode) {
                    $totalScore = 0;
                    $totalBobot = 0;
                    foreach ($catIds as $catId) {
                        $cat = $this->categories->get($catId);
                        if (!$cat) continue;
                        foreach ($cat->items as $item) {
                            if (!isset($this->itemScores[$item->id])) continue;
                            if (isset($this->itemScores[$item->id]['scores'][$kode])) {
                                $totalScore += $this->itemScores[$item->id]['scores'][$kode];
                                $totalBobot += $this->itemScores[$item->id]['bobot'];
                            }
                        }
                    }
                    $geraiScores[$kode] = $totalScore;
                    if ($totalBobot > 0) {
                        $geraiPcts[$kode] = ($totalScore / $totalBobot) * 100;
                    }
                }
                return ['scores' => $geraiScores, 'pcts' => $geraiPcts];
            }

            public function writeRow(string $name, array $catIds, int $depth): void
            {
                $data = $this->aggregate($catIds);
                $pctValues = array_values($data['pcts']);
                $scores = $data['scores'];
                $prefix = str_repeat('  ', $depth);
                $row = [$prefix . $name];
                foreach ($this->geraiKodes as $kode) {
                    $val = $scores[$kode] ?? 0;
                    $row[] = $val > 0 ? (string) $val : '-';
                }
                $row[] = !empty($pctValues) ? round(array_sum($pctValues) / count($pctValues)) : '-';
                $row[] = !empty($pctValues) ? round(min($pctValues)) : '-';
                $row[] = !empty($pctValues) ? round(max($pctValues)) : '-';
                $this->writer->addRow(Row::fromValues($row));
            }

            public function writeCategoryAndItems(array $catIds, int $depth): void
            {
                foreach ($catIds as $catId) {
                    $cat = $this->categories->get($catId);
                    if (!$cat) continue;
                    $this->writeRow($cat->name, [$catId], $depth);
                    foreach ($cat->items as $item) {
                        if (!isset($this->itemScores[$item->id])) continue;
                        $itemPrefix = str_repeat('  ', $depth + 1);
                        $itemRow = [$itemPrefix . $item->name];
                        $scoreValues = [];
                        foreach ($this->geraiKodes as $kode) {
                            $val = $this->itemScores[$item->id]['scores'][$kode] ?? null;
                            if ($val !== null) {
                                $itemRow[] = (string) $val;
                                $pct = ($val / $this->itemScores[$item->id]['bobot']) * 100;
                                $scoreValues[] = $pct;
                            } else {
                                $itemRow[] = '-';
                            }
                        }
                        $itemRow[] = !empty($scoreValues) ? round(array_sum($scoreValues) / count($scoreValues)) : '-';
                        $itemRow[] = !empty($scoreValues) ? round(min($scoreValues)) : '-';
                        $itemRow[] = !empty($scoreValues) ? round(max($scoreValues)) : '-';
                        $this->writer->addRow(Row::fromValues($itemRow));
                    }
                }
            }
        };

        $allCategoryIds = [];

        foreach ($sections as $section) {
            $allCatIds = $section['category_ids'] ?? [];
            foreach ($section['groups'] ?? [] as $group) {
                $allCatIds = array_merge($allCatIds, $group['category_ids']);
            }

            $allCategoryIds = array_merge($allCategoryIds, $allCatIds);

            $helper->writeRow($section['name'], $allCatIds, 0);

            foreach ($section['groups'] ?? [] as $group) {
                $helper->writeRow($group['name'], $group['category_ids'], 1);
                $helper->writeCategoryAndItems($group['category_ids'], 2);
            }

            if (!empty($section['category_ids'])) {
                $helper->writeCategoryAndItems($section['category_ids'], 1);
            }
        }

        $helper->writeRow('Total', $allCategoryIds, 0);

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function ambilData(Request $request)
    {
        $request->validate(['periode_label' => 'required|string']);

        $reports = MonitoringReport::with('gerai', 'user')
            ->where('type', 'monitoring')
            ->whereNotNull('submit_at')
            ->where('periode_label', $request->periode_label)
            ->orderBy('checkin_at')
            ->get();

        $writer = new Writer();
        $filename = storage_path('app/ambil-data-' . now()->format('Y-m-d_H-i') . '.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Gerai', 'Nama Gerai', 'Petugas', 'Tanggal', 'Checkin', 'Submit']));

        foreach ($reports as $r) {
            $writer->addRow(Row::fromValues([
                $r->gerai->kode_gerai ?? '-',
                $r->gerai->nama_gerai ?? '-',
                $r->user->name ?? '-',
                $r->checkin_at->format('d-m-Y'),
                $r->checkin_at->format('H:i'),
                $r->submit_at ? $r->submit_at->format('d-m-Y H:i') : '-',
            ]));
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function checklistTidakSempurna(Request $request)
    {
        $type = $request->input('type', 'monitoring');
        if (in_array($type, ['pra-monitoring', 're-monitoring'])) {
            $request->validate(['month' => 'required|string', 'type' => 'required|string']);
        } else {
            $request->validate(['periode_label' => 'required|string']);
        }

        $modelClass = match ($type) {
            'pra-monitoring' => \App\Models\PraMonitoringReport::class,
            're-monitoring' => \App\Models\ReMonitoringReport::class,
            default => MonitoringReport::class,
        };

        $query = $modelClass::with('gerai', 'results.item.criteria', 'results.criterion')
            ->whereNotNull('submit_at');

        if (in_array($type, ['pra-monitoring', 're-monitoring'])) {
            $month = $request->month;
            $query->whereYear('checkin_at', substr($month, 0, 4))
                  ->whereMonth('checkin_at', substr($month, 5, 2));
        } else {
            $query->where('type', 'monitoring')
                  ->where('periode_label', $request->periode_label);
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk periode tersebut.');
        }

        $geraiKodes = $reports->pluck('gerai.kode_gerai')->unique()->sort()->values()->toArray();

        $allCategories = Category::with('items.criteria')->get()->keyBy('id');

        $itemData = [];
        foreach ($allCategories as $cat) {
            foreach ($cat->items as $item) {
                if (!$item->bobot) continue;
                if ($item->criteria->count() <= 1) continue;
                $itemData[$item->id] = [
                    'name' => $item->name,
                    'category' => $cat->name,
                    'scores' => [],
                    'hasImperfect' => false,
                ];
            }
        }

        foreach ($reports as $report) {
            $geraiKode = $report->gerai->kode_gerai;
            foreach ($report->results as $result) {
                $itemId = $result->item_id;
                if (!isset($itemData[$itemId])) continue;

                $criterion = $result->criterion;
                $desc = $criterion ? $criterion->description : '-';
                $itemData[$itemId]['scores'][$geraiKode] = $desc;

                $item = $result->item;
                if ($item && $item->criteria->isNotEmpty()) {
                    $firstCriterion = $item->criteria->first();
                    if ($result->criterion_id !== $firstCriterion->id) {
                        $itemData[$itemId]['hasImperfect'] = true;
                    }
                }
            }
        }

        $itemData = array_filter($itemData, fn($d) => $d['hasImperfect']);

        if (empty($itemData)) {
            return back()->with('error', 'Semua checklist sudah sempurna untuk periode ini.');
        }

        $writer = new Writer();
        $filename = storage_path('app/checklist-tidak-sempurna-' . now()->format('Y-m-d_H-i') . '.xlsx');
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Checklist Tidak Sempurna - ' . $periodeLabel]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Gerai', 'Checklist']));

        foreach ($reports as $report) {
            $geraiKode = $report->gerai->kode_gerai;
            foreach ($report->results as $result) {
                $itemId = $result->item_id;
                if (!isset($itemData[$itemId])) continue;
                if (!$itemData[$itemId]['hasImperfect']) continue;

                $item = $result->item;
                $isPerfect = false;
                if ($item && $item->criteria->isNotEmpty()) {
                    $firstCriterion = $item->criteria->first();
                    $isPerfect = $result->criterion_id === $firstCriterion->id;
                }

                if (!$isPerfect) {
                    $writer->addRow(Row::fromValues([
                        $geraiKode,
                        $item->name ?? '-',
                    ]));
                }
            }
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function excelDetail(Request $request)
    {
        $rules = ['type' => 'required|in:monitoring,pra-monitoring'];
        if ($request->type === 'pra-monitoring') {
            $rules['month'] = 'required|string';
        } else {
            $rules['periode_label'] = 'required|string';
        }
        $request->validate($rules);

        $query = MonitoringReport::with('gerai', 'finding')
            ->where('type', $request->type)
            ->whereNotNull('submit_at');

        if ($request->type === 'pra-monitoring') {
            $month = $request->month;
            $query->whereYear('checkin_at', substr($month, 0, 4))
                  ->whereMonth('checkin_at', substr($month, 5, 2));
        } else {
            $query->where('periode_label', $request->periode_label);
        }

        $reports = $query->orderBy('checkin_at')->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'Tidak ada laporan untuk periode ini.');
        }

        $safeType = preg_replace('/[^a-z\-]/', '', $request->type);
        $filename = storage_path("app/detail-laporan-{$safeType}-" . now()->format('Ymd_His') . '.xlsx');
        $writer = new Writer();
        $writer->openToFile($filename);

        $sheets = [
            ['name' => 'PS', 'header' => ['Kode Gerai', 'Pengawas'], 'field' => 'pengawas', 'split' => true],
            ['name' => 'Rata-rata AJ', 'header' => ['Kode Gerai', 'Rata-rata AJ'], 'field' => 'rata_rata_aj', 'split' => true],
            ['name' => 'TDS', 'header' => ['Kode Gerai', 'TDS'], 'field' => 'tds', 'split' => true],
            ['name' => 'Mesin Ozon', 'header' => ['Kode Gerai', 'Mesin Ozon'], 'field' => 'mesin_ozon', 'split' => true],
            ['name' => 'Temuan', 'header' => ['Kode Gerai', 'Peringatan Awal'], 'field' => null, 'split' => false],
            ['name' => 'Note', 'header' => ['Kode Gerai', 'Note'], 'field' => 'note', 'split' => true],
            ['name' => 'Cat', 'header' => ['Kode Gerai', 'Kondisi Cat'], 'field' => 'kondisi_cat', 'split' => true],
            ['name' => 'Awning', 'header' => ['Kode Gerai', 'Kondisi Awning'], 'field' => 'kondisi_awning', 'split' => true],
            ['name' => 'Vinyl Reklame', 'header' => ['Kode Gerai', 'Kondisi Vinyl Reklame'], 'field' => 'kondisi_vinyl', 'split' => true],
            ['name' => 'Stiker Kaca', 'header' => ['Kode Gerai', 'Kondisi Stiker Kaca'], 'field' => 'kondisi_stiker_kaca', 'split' => true],
        ];

        $firstSheet = true;
        foreach ($sheets as $sheetDef) {
            if ($request->type === 'pra-monitoring' && $sheetDef['field'] === 'tds') {
                continue;
            }

            if ($firstSheet) {
                $sheet = $writer->getCurrentSheet();
                $firstSheet = false;
            } else {
                $sheet = $writer->addNewSheetAndMakeItCurrent();
            }
            $sheet->setName($sheetDef['name']);

            $writer->addRow(Row::fromValues($sheetDef['header']));

            foreach ($reports as $report) {
                $finding = $report->finding;
                if (!$finding) continue;

                if (!$report->gerai) continue;
                $kode = $report->gerai->kode_gerai;

                if ($sheetDef['name'] === 'Temuan') {
                    $paLines = explode("\n", str_replace("\r\n", "\n", $finding->peringatan_awal ?? ''));
                    foreach ($paLines as $line) {
                        $trimmed = trim($line);
                        if ($trimmed === '') continue;
                        $writer->addRow(Row::fromValues([
                            $kode,
                            $trimmed,
                        ]));
                    }
                } else {
                    $value = $finding->{$sheetDef['field']} ?? '';
                    if ($sheetDef['field'] === 'rata_rata_aj') {
                        $lines = explode("\n", str_replace("\r\n", "\n", $value));
                        foreach ($lines as $line) {
                            $trimmed = trim($line);
                            if ($trimmed === '') continue;
                            $writer->addRow(Row::fromValues([$kode, $trimmed . ' gln/hr']));
                        }
                    } elseif ($sheetDef['field'] === 'tds') {
                        $lines = explode("\n", str_replace("\r\n", "\n", $value));
                        foreach ($lines as $line) {
                            $trimmed = trim($line);
                            if ($trimmed === '') continue;
                            $tdsDisplay = str_replace('/', ' ppm/', $trimmed) . (str_contains($trimmed, '/') ? '°C' : '');
                            $writer->addRow(Row::fromValues([$kode, $tdsDisplay]));
                        }
                    } elseif ($sheetDef['split']) {
                        $lines = explode("\n", str_replace("\r\n", "\n", $value));
                        foreach ($lines as $line) {
                            $trimmed = trim($line);
                            if ($trimmed === '') continue;
                            $writer->addRow(Row::fromValues([$kode, $trimmed]));
                        }
                    } else {
                        $writer->addRow(Row::fromValues([$kode, $value]));
                    }
                }
            }
        }

        $writer->close();
        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function exportAllExcel(Request $request)
    {
        $rules = ['type' => 'required|in:monitoring,pra-monitoring,re-monitoring'];
        if ($request->type === 'pra-monitoring' || $request->type === 're-monitoring') {
            $rules['month'] = 'required|string';
        } else {
            $rules['periode_label'] = 'required|string';
        }
        $request->validate($rules);

        $modelClass = match ($request->type) {
            'pra-monitoring' => \App\Models\PraMonitoringReport::class,
            're-monitoring' => \App\Models\ReMonitoringReport::class,
            default => MonitoringReport::class,
        };
        $query = $modelClass::with('gerai', 'user')
            ->whereNotNull('submit_at');

        if (Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        if ($request->type === 'pra-monitoring' || $request->type === 're-monitoring') {
            $month = $request->month; // YYYY-MM
            $query->whereYear('checkin_at', substr($month, 0, 4))
                  ->whereMonth('checkin_at', substr($month, 5, 2));
        } else {
            $query->where('type', $request->type)
                  ->where('periode_label', $request->periode_label);
        }

        $reports = $query->orderBy('checkin_at')->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'Tidak ada laporan untuk periode ini.');
        }

        $tempDir = storage_path('app/temp-excel-' . now()->format('Ymd_His'));
        mkdir($tempDir, 0755, true);

        $controller = match ($request->type) {
            'pra-monitoring' => app(PraMonitoringController::class),
            're-monitoring' => app(ReMonitoringController::class),
            default => app(MonitoringController::class),
        };

        $generated = [];
        foreach ($reports as $report) {
            try {
                $path = $controller->excel($report->id, $tempDir);
                if ($path) $generated[] = $path;
            } catch (\Throwable $e) {
                \Log::error('Export excel failed', ['report_id' => $report->id, 'error' => $e->getMessage()]);
                continue;
            }
        }

        if (empty($generated)) {
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir);
            return back()->with('error', 'Gagal membuat file Excel.');
        }

        $label = $request->type === 'pra-monitoring' || $request->type === 're-monitoring' ? $request->month : $request->periode_label;
        $safeLabel = preg_replace('/[^a-zA-Z0-9\-\s]/', '', $label);
        $safeType = preg_replace('/[^a-z\-]/', '', $request->type);
        $zipPath = storage_path("app/laporan-{$safeType}-{$safeLabel}-" . now()->format('Y-m-d_H-i') . '.zip');
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir);
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        foreach ($generated as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        array_map('unlink', glob("$tempDir/*"));
        rmdir($tempDir);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function exportAllPdf(Request $request)
    {
        $rules = ['type' => 'required|in:monitoring,pra-monitoring,re-monitoring'];
        if (in_array($request->type, ['pra-monitoring', 're-monitoring'])) {
            $rules['month'] = 'required|string';
        } else {
            $rules['periode_label'] = 'required|string';
        }
        $request->validate($rules);

        $modelClass = match ($request->type) {
            'pra-monitoring' => \App\Models\PraMonitoringReport::class,
            're-monitoring' => \App\Models\ReMonitoringReport::class,
            default => MonitoringReport::class,
        };
        $query = $modelClass::with('gerai', 'user')
            ->whereNotNull('submit_at');

        if (in_array($request->type, ['pra-monitoring', 're-monitoring'])) {
            $month = $request->month;
            $query->whereYear('checkin_at', substr($month, 0, 4))
                  ->whereMonth('checkin_at', substr($month, 5, 2));
        } else {
            $query->where('type', $request->type)
                  ->where('periode_label', $request->periode_label);
        }

        if (Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $reports = $query->orderBy('checkin_at')->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'Tidak ada laporan untuk periode ini.');
        }

        $tempDir = storage_path('app/temp-pdf-' . now()->format('Ymd_His'));
        mkdir($tempDir, 0755, true);

        $controllerClass = match ($request->type) {
            'pra-monitoring' => \App\Http\Controllers\PraMonitoringController::class,
            're-monitoring' => \App\Http\Controllers\ReMonitoringController::class,
            default => \App\Http\Controllers\MonitoringController::class,
        };
        $controller = app($controllerClass);
        $pyScript = base_path('storage/app/xlwings-to-pdf.py');
        $generated = [];

        foreach ($reports as $report) {
            try {
                $excelPath = $controller->excel($report->id, $tempDir);
                if (!$excelPath || !file_exists($excelPath)) continue;

                $pdfName = pathinfo(basename($excelPath), PATHINFO_FILENAME) . '.pdf';
                $pdfPath = $tempDir . '/' . $pdfName;
                $cmd = 'python ' . escapeshellarg($pyScript) . ' ' . escapeshellarg($excelPath) . ' ' . escapeshellarg($pdfPath);
                exec($cmd, $output, $returnCode);

                @unlink($excelPath);

                if ($returnCode === 0 && file_exists($pdfPath)) {
                    $pdfLabel = $report->periode_label ?? $report->checkin_at->format('M-Y');
                    $newName = "{$report->gerai->kode_gerai} - {$pdfLabel}.pdf";
                    $newPath = $tempDir . '/' . $newName;
                    rename($pdfPath, $newPath);
                    $generated[] = $newPath;
                }
            } catch (\Throwable $e) {
                \Log::error('Export pdf failed', ['report_id' => $report->id, 'error' => $e->getMessage()]);
                continue;
            }
        }

        if (empty($generated)) {
            array_map('unlink', glob("$tempDir/*.pdf"));
            rmdir($tempDir);
            return back()->with('error', 'Gagal membuat file PDF.');
        }

        $label = in_array($request->type, ['pra-monitoring', 're-monitoring']) ? $request->month : $request->periode_label;
        $safeLabel = preg_replace('/[^a-zA-Z0-9\-\s]/', '', $label);
        $safeType = preg_replace('/[^a-z\-]/', '', $request->type);
        $zipPath = storage_path("app/laporan-{$safeType}-pdf-{$safeLabel}-" . now()->format('Y-m-d_H-i') . '.zip');
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            array_map('unlink', glob("$tempDir/*.pdf"));
            rmdir($tempDir);
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        foreach ($generated as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        array_map('unlink', glob("$tempDir/*.pdf"));
        rmdir($tempDir);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
