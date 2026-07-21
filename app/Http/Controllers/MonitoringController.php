<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\Category;
use App\Models\Result;
use App\Models\MonitoringReport;
use App\Models\MonitoringFinding;
use App\Models\PenjelasanFormulir;
use App\Models\SemesterPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FontRegistration;
use App\Services\ExcelXmlHelpers;

class MonitoringController extends Controller
{
    use FontRegistration, ExcelXmlHelpers;
    protected $type = 'monitoring';

    protected function modelClass(): string
    {
        return MonitoringReport::class;
    }

    protected function prefix()
    {
        return match ($this->type) {
            'pra-monitoring' => 'pra-monitoring',
            're-monitoring' => 're-monitoring',
            'evaluasi' => 'evaluasi',
            default => 'monitoring',
        };
    }

    protected function hasPenjelasanFormulir2(): bool
    {
        return $this->type !== 'pra-monitoring';
    }

    protected function ensureCellXfStyle(DOMDocument $stylesDoc, string $stylesNs, int $fontId, int $borderId, string $horizontal = 'left', string $vertical = 'center', bool $wrapText = true): int
    {
        $cellXfs = $stylesDoc->getElementsByTagNameNS($stylesNs, 'cellXfs')->item(0);
        if (!$cellXfs) return 0;

        // Search for existing xf matching our criteria
        $xfNodes = $cellXfs->getElementsByTagNameNS($stylesNs, 'xf');
        for ($i = 0; $i < $xfNodes->length; $i++) {
            $xf = $xfNodes->item($i);
            if ((int)$xf->getAttribute('fontId') === $fontId && (int)$xf->getAttribute('borderId') === $borderId) {
                $al = $xf->getElementsByTagNameNS($stylesNs, 'alignment')->item(0);
                if ($al && $al->getAttribute('horizontal') === $horizontal && $al->getAttribute('vertical') === $vertical) {
                    return $i;
                }
            }
        }

        // Not found — create new xf
        $idx = $xfNodes->length;
        $xf = $stylesDoc->createElementNS($stylesNs, 'xf');
        $xf->setAttribute('numFmtId', '0');
        $xf->setAttribute('fontId', (string)$fontId);
        $xf->setAttribute('fillId', '0');
        $xf->setAttribute('borderId', (string)$borderId);
        $xf->setAttribute('xfId', '0');
        $xf->setAttribute('applyFont', '1');
        $xf->setAttribute('applyAlignment', '1');
        $align = $stylesDoc->createElementNS($stylesNs, 'alignment');
        $align->setAttribute('horizontal', $horizontal);
        $align->setAttribute('vertical', $vertical);
        if ($wrapText) $align->setAttribute('wrapText', '1');
        $xf->appendChild($align);
        $cellXfs->appendChild($xf);
        $cellXfs->setAttribute('count', (string)$idx);
        return $idx;
    }

    protected function fillSheet1Custom(DOMDocument $dom1, DOMXPath $xpath1, string $ns, float $totalScore, string $grade, string $kesimpulanText, int $wrapStyleIdx = 0): void
    {
        // Override in child controllers for custom sheet1 logic
    }

    protected function fillSheet2Custom(DOMDocument $dom2, DOMXPath $xpath2, string $ns2, array $findingLines, $finding, array $lowItems, array $sheet3ZeroItems, array $items, $zip): bool
    {
        return false;
    }

    protected function fillSheet3Custom(DOMDocument $dom3, DOMXPath $xpath3, string $ns3, float $totalScore, string $tanggalLengkap): void
    {
    }

    protected function onPhase3Cell(string $sheetName, int $ssIndex, array $ssIndexText, array $ssIndexScore, DOMElement $cell, DOMDocument $dom, array $items): void
    {
    }

    protected function getTemplateName(): string
    {
        return $this->type;
    }

    protected function getTargetPeriode($report): ?string
    {
        return $report->periode_label;
    }

    protected function getPreviousScore($report, float $totalScore): ?float
    {
        $periods = MonitoringReport::where('gerai_id', $report->gerai_id)
            ->whereNotNull('submit_at')
            ->selectRaw('periode_label, MAX(checkin_at) as last_checkin')
            ->groupBy('periode_label')
            ->orderByRaw('MAX(checkin_at) desc')
            ->pluck('periode_label');
        $currentIdx = $periods->search($report->periode_label);

        if ($currentIdx !== false && $currentIdx < $periods->count() - 1) {
            $prevPeriodLabel = $periods->values()[$currentIdx + 1];
            $prevReport = MonitoringReport::where('gerai_id', $report->gerai_id)
                ->where('periode_label', $prevPeriodLabel)
                ->whereNotNull('submit_at')
                ->latest('checkin_at')
                ->first();
            if ($prevReport) {
                if (is_numeric($prevReport->nilai)) {
                    return (float) $prevReport->nilai;
                }
                $prevResults = Result::where('reportable_type', get_class($prevReport))
                    ->where('reportable_id', $prevReport->id)
                    ->get()->keyBy('item_id');
                $prevCategories = Category::whereNull('parent_id')->with('items.criteria')->orderBy('sort')->get();
                $score = 0;
                foreach ($prevCategories as $cat) {
                    foreach ($cat->items as $item) {
                        $r = $prevResults->get($item->id);
                        if (!$r || !$r->criterion_id) continue;
                        $criteriaCount = $item->criteria->count();
                        if (!$item->bobot) continue;
                        if ($criteriaCount <= 1) {
                            $score += $item->bobot;
                        } else {
                            $interval = $item->bobot / ($criteriaCount - 1);
                            $idx = $item->criteria->search(fn($c) => $c->id === $r->criterion_id);
                            if ($idx !== false) {
                                $score += $item->bobot - ($interval * $idx);
                            }
                        }
                    }
                }
                return $score;
            }
        }
        return null;
    }

    protected function getReportDateForFilename($report, string $tz): string
    {
        return $report->checkin_at->setTimezone($tz)->format('Y-m-d_H.i');
    }

    protected function requiredFindingFields(): array
    {
        return ['pengawas', 'rata_rata_aj', 'mesin_ozon', 'peringatan_awal', 'tds'];
    }

    protected function filterFindingData(array $data): array
    {
        return $data;
    }

    protected function useExcelPdf(): bool
    {
        return false;
    }

    protected function postProcessExcel(string $outPath): void
    {
    }

    protected function appendChartColumn($dom4, $xpath4, array $filledCols, $report, float $totalScore, ?float $prevTotalScore, string $tz, array $columns, string $ns4): array
    {
        return $filledCols;
    }

    public function selectGerai()
    {
        $pending = $this->pendingReport();
        if ($pending) {
            $pendingUrl = $this->type === 'evaluasi'
                ? "/{$this->prefix()}/{$pending->id}"
                : "/{$this->prefix()}/{$pending->id}/assessment";
            return redirect($pendingUrl)
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan. Selesaikan dulu.');
        }

        $gerais = Gerai::active()->orderBy('kode_gerai')->get();

        if ($this->type === 'evaluasi') {
            $pendingByOthers = $this->modelClass()::where('user_id', '!=', Auth::id())
                ->whereNull('tanggal')
                ->with('user')
                ->get()
                ->pluck('user.name', 'gerai_id')
                ->toArray();
        } else {
            $pendingByOthers = $this->modelClass()::where('user_id', '!=', Auth::id())
                ->whereNotNull('checkin_at')
                ->whereNull('submit_at')
                ->with('user')
                ->get()
                ->pluck('user.name', 'gerai_id')
                ->toArray();
        }

        return view('monitoring.select-gerai', compact('gerais', 'pendingByOthers') + ['prefix' => $this->prefix()]);
    }

    public function checkinForm(Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending && request('pairing') !== '1') {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan. Selesaikan dulu.');
        }

        $periods = SemesterPeriod::where('year', now()->year)->orderBy('start_month')->get();

        $existingPeriods = MonitoringReport::where('gerai_id', $gerai->id)
            ->where(function ($q) {
                $q->where('type', 'import')
                    ->orWhere(function ($q2) {
                        $q2->where('type', $this->type)
                            ->whereNotNull('submit_at');
                    });
            })
            ->whereNotNull('periode_label')
            ->pluck('periode_label')
            ->unique()
            ->values()
            ->toArray();

        return view('monitoring.checkin', compact('gerai', 'periods', 'existingPeriods') + ['prefix' => $this->prefix()]);
    }

    public function doCheckin(Request $request, Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending && $request->input('pairing') !== '1') {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan.');
        }

        $data = $request->validate([
            'location' => 'required|string|max:255',
            'periode_label' => 'required|string|max:100',
            'checkin_at' => 'required|date',
        ]);

        $pairing = $request->input('pairing') === '1';

        $report = DB::transaction(function () use ($gerai, $data, $pairing) {
            if (!$pairing) {
                $duplicate = $this->modelClass()::where('gerai_id', $gerai->id)
                    ->where('periode_label', $data['periode_label'])
                    ->whereNotNull('submit_at')
                    ->exists();

                if ($duplicate) {
                    return null;
                }
            }

            $report = $this->modelClass()::create([
                'gerai_id' => $gerai->id,
                'user_id' => Auth::id(),
                'location' => $data['location'],
                'periode_label' => $data['periode_label'],
                'checkin_at' => \Carbon\Carbon::parse($data['checkin_at'] . ' ' . now()->format('H:i:s')),
                'is_pairing' => $pairing,
            ]);

            $categories = Category::whereNull('parent_id')->with('items.criteria')->get();
            foreach ($categories as $cat) {
                foreach ($cat->items as $item) {
                    if ($item->criteria->isNotEmpty()) {
                        Result::create([
                            'item_id' => $item->id,
                            'user_id' => Auth::id(),
                            'reportable_type' => get_class($report),
                            'reportable_id' => $report->id,
                            'criterion_id' => $item->criteria->first()->id,
                        ]);
                    }
                }
            }

            return $report;
        });

        if ($report === null) {
            return redirect("/{$this->prefix()}/checkin/{$gerai->id}")->with('warning', 'Nilai untuk gerai dan periode ini sudah ada. Hapus data yang ada terlebih dahulu jika ingin mengganti.');
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment");
    }

    public function assessment($id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $categories = Category::whereNull('parent_id')
            ->with('items.criteria')
            ->orderBy('sort')
            ->get();

        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->get()
            ->keyBy('item_id');

        $totalScore = 0;
        $catScores = [];
        foreach ($categories as $cat) {
            $catScore = 0;
            $catMax = 0;
            foreach ($cat->items as $item) {
                $result = $results->get($item->id);
                if ($item->bobot) {
                    $catMax += $item->bobot;
                }
                if ($result && $result->criterion_id && $item->bobot) {
                    $criteria = $item->criteria;
                    $count = $criteria->count();
                    if ($count > 1) {
                        $interval = $item->bobot / ($count - 1);
                        $idx = $criteria->search(fn($c) => $c->id === $result->criterion_id);
                        if ($idx !== false) {
                            $val = $item->bobot - ($interval * $idx);
                            $catScore += $val;
                            $totalScore += $val;
                        }
                    } else {
                        $catScore += $item->bobot;
                        $totalScore += $item->bobot;
                    }
                }
            }
            $catScores[$cat->id] = ['score' => $catScore, 'max' => $catMax];
        }

        // check temuan completeness
        $finding = $report->finding;
        $incomplete = [];
        $prefix = $this->prefix();

        if (!$finding) {
            $incomplete[] = 'Pengisian Temuan';
        } else {
            $temuanFields = $this->requiredFindingFields();
            foreach ($temuanFields as $f) {
                if (empty(trim($finding->$f ?? ''))) {
                    $incomplete[] = 'Pengisian Temuan';
                    break;
                }
            }

            if (empty($finding->ttd_petugas)) {
                $incomplete[] = 'TTD Petugas';
            }
            if (empty($finding->ttd_pimpinan)) {
                $incomplete[] = 'TTD Pimpinan';
            }

            // check penjelasan formulir 2
            if ($this->hasPenjelasanFormulir2() && !empty($finding->penjelasan_isi) && is_array($finding->penjelasan_isi)) {
                foreach ($finding->penjelasan_isi as $val) {
                    if (empty(trim($val))) {
                        $incomplete[] = 'Penjelasan Formulir 2';
                        break;
                    }
                }
            }

            // check penjelasan formulir 3
            $allItems = \App\Models\Item::with('criteria')->get();
            $zeroScoreItemIds = [];
            foreach ($allItems as $item) {
                if (!$item->bobot || $item->criteria->count() <= 1) continue;
                $result = $results->get($item->id);
                if (!$result || !$result->criterion_id) continue;
                $criteria = $item->criteria;
                $idx = $criteria->search(fn($c) => $c->id === $result->criterion_id);
                if ($idx === $criteria->count() - 1) {
                    $zeroScoreItemIds[] = $item->id;
                }
            }
            if (!empty($zeroScoreItemIds)) {
                $penjelasan3 = $finding->penjelasan_isi_3 ?? [];
                $allFilled = true;
                foreach ($zeroScoreItemIds as $itemId) {
                    if (empty($penjelasan3[$itemId]) || empty(trim($penjelasan3[$itemId]))) {
                        $allFilled = false;
                        break;
                    }
                }
                if (!$allFilled) {
                    $incomplete[] = 'Penjelasan Formulir 3';
                }
            }
        }

        $snapshot = null;
        if ($report->submit_at) {
            $snapshotKey = 'assessment_snapshot_' . $report->id;
            if (!session()->has($snapshotKey)) {
                $snapshot = [
                    'results' => $results->map(fn($r) => ['item_id' => $r->item_id, 'criterion_id' => $r->criterion_id])->values()->toArray(),
                    'finding' => $finding ? $finding->toArray() : null,
                ];
                session()->put($snapshotKey, $snapshot);
            } else {
                $snapshot = session()->get($snapshotKey);
            }
        }

        return view('monitoring.assessment', compact('report', 'categories', 'results', 'totalScore', 'catScores', 'incomplete', 'snapshot') + ['prefix' => $prefix]);
    }

    public function cancelAssessment(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $snapshotKey = 'assessment_snapshot_' . $report->id;
        $snapshot = session()->get($snapshotKey);

        if (!$snapshot) {
            return redirect("/{$this->prefix()}")->with('warning', 'Session snapshot tidak ditemukan. Mungkin sudah kedaluwarsa.');
        }

        if ($snapshot['results'] !== null) {
            Result::where('reportable_type', get_class($report))
                ->where('reportable_id', $report->id)->delete();
            foreach ($snapshot['results'] as $resultData) {
                Result::create([
                    'reportable_type' => get_class($report),
                    'reportable_id' => $report->id,
                    'user_id' => Auth::id(),
                    'item_id' => $resultData['item_id'],
                    'criterion_id' => $resultData['criterion_id'],
                ]);
            }
        }

        if (array_key_exists('finding', $snapshot)) {
            if ($snapshot['finding']) {
                $findingData = $snapshot['finding'];
                unset($findingData['id'], $findingData['created_at'], $findingData['updated_at']);
                MonitoringFinding::updateOrCreate(
                    ['reportable_type' => get_class($report), 'reportable_id' => $report->id],
                    $findingData
                );
            } else {
                $report->finding?->delete();
            }
        }

        session()->forget($snapshotKey);

        $redirect = "/{$this->prefix()}/{$report->id}";

        return redirect($redirect)->with('success', 'Perubahan berhasil dibatalkan.');
    }

    public function itemForm($id, \App\Models\Item $item)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $result = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->where('item_id', $item->id)
            ->where('user_id', Auth::id())
            ->first();
        return view('monitoring.item-form', compact('report', 'item', 'result') + ['prefix' => $this->prefix()]);
    }

    public function assessmentForm($id, Category $category)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $category->load('items.criteria');

        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->whereIn('item_id', $category->items->pluck('id'))
            ->get()
            ->keyBy('item_id');

        return view('monitoring.assessment-form', compact('report', 'category', 'results') + ['prefix' => $this->prefix()]);
    }

    public function saveAssessmentForm(Request $request, $id, ?Category $category = null)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $category->load('items.criteria');

        foreach ($category->items as $item) {
            $criterionId = $request->input("criterion.{$item->id}");
            if ($criterionId) {
                Result::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'user_id' => Auth::id(),
                        'reportable_type' => get_class($report),
                        'reportable_id' => $report->id,
                    ],
                    ['criterion_id' => $criterionId]
                );
            }
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment")->with('success', 'Penilaian berhasil disimpan.');
    }

    public function temuanForm($id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $finding = $report->finding;

        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)->get()->keyBy('item_id');

        $groups = $this->penjelasanGroups();
        $groupLabels = [];

        foreach ($groups as $group) {
            $achieved = 0;
            $maxBobot = 0;
            foreach ($group['item_ids'] as $itemId) {
                $result = $results->get($itemId);
                if (!$result) continue;
                $item = \App\Models\Item::with('criteria')->find($itemId);
                if (!$item || !$item->bobot) continue;
                $criteria = $item->criteria;
                $count = $criteria->count();
                if ($count <= 1) continue;
                $interval = $item->bobot / ($count - 1);
                $idx = $criteria->search(fn($c) => $c->id === $result->criterion_id);
                if ($idx !== false) {
                    $achieved += $item->bobot - ($interval * $idx);
                }
                $maxBobot += $item->bobot;
            }
            if ($maxBobot > 0) {
                $pct = ($achieved / $maxBobot) * 100;
                if ($pct <= 85) {
                    $groupLabels[] = $group['name'];
                }
            }
        }

        if (empty($groupLabels)) {
            $groupLabels[] = 'Non Temuan';
        }

        $penjelasanItems = $this->hasPenjelasanFormulir2()
            ? PenjelasanFormulir::where('formulir', 2)->orderBy('sort')->get()
            : collect();
        $penjelasanItems3 = PenjelasanFormulir::where('formulir', 3)->orderBy('sort')->get();

        $zeroScoreItems = [];
        $allItems = \App\Models\Item::with('criteria')->get();
        foreach ($allItems as $item) {
            if (!$item->bobot || $item->criteria->count() <= 1) continue;
            $result = $results->get($item->id);
            if (!$result || !$result->criterion_id) continue;
            $criteria = $item->criteria;
            $idx = $criteria->search(fn($c) => $c->id === $result->criterion_id);
            if ($idx === $criteria->count() - 1) {
                $zeroScoreItems[] = ['id' => $item->id, 'name' => $item->name];
            }
        }

        return view('monitoring.temuan', compact('report', 'finding', 'groupLabels', 'penjelasanItems', 'penjelasanItems3', 'zeroScoreItems') + ['prefix' => $this->prefix()]);
    }

    private function penjelasanGroups(): array
    {
        return [
            ['name' => 'Keramah-tamahan, Kesigapan, Konsistensi, Kerjasama Tim', 'item_ids' => [1,2,5,6, 3,7,8,9, 10, 11]],
            ['name' => 'Kedisiplinan', 'item_ids' => [12,13,14]],
            ['name' => 'Kebersihan diri, Sikap dan tingkah laku, Bahasa, tutur kata dan bahasa tubuh', 'item_ids' => [15,16,17, 18,19,20,21, 22,23,24]],
            ['name' => 'Keterlibatan Pimpinan Gerai', 'item_ids' => [25,26,27,28,29,30,31]],
            ['name' => 'Kebersihan dan kondisi pelataran parkir', 'item_ids' => [32,33,34,35]],
            ['name' => 'Kebersihan ruang toko', 'item_ids' => [36,37,38,39,40,41]],
            ['name' => 'Kebersihan lemari pengisian, barang dagangan, identitas perusahaan & merek', 'item_ids' => [42,43,44,45,46,47,48]],
            ['name' => 'Kebersihan ruang tandon', 'item_ids' => [49,50,51,52,53,54,55]],
            ['name' => 'Kebersihan peralatan kerja dan gerai', 'item_ids' => [56,57,58,59]],
            ['name' => 'Kualitas air baku', 'item_ids' => [60,61,62,63,64]],
            ['name' => 'Kualitas air minum', 'item_ids' => [65,66,67]],
            ['name' => 'Standar kegiatan di ruang toko', 'item_ids' => [68,69,70,71,80,72,73,74]],
            ['name' => 'Standar kegiatan di ruang tandon', 'item_ids' => [81,82,83]],
            ['name' => 'Kelengkapan formulir operasional & dokumen', 'item_ids' => [75,76,77,78,79]],
        ];
    }

    public function saveTemuan(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $validationRules = [
            'major' => 'nullable|string',
            'minor' => 'nullable|string',
            'peringatan_awal' => 'nullable|string',
            'pengawas' => 'nullable|string',
            'rata_rata_aj' => 'nullable|string',
            'tds' => 'nullable|string',
            'mesin_ozon' => 'nullable|string',
            'note' => 'nullable|string',
            'kondisi_cat' => 'nullable|string',
            'kondisi_awning' => 'nullable|string',
            'kondisi_vinyl' => 'nullable|string',
            'kondisi_stiker_kaca' => 'nullable|string',
            'ttd_petugas' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ttd_pimpinan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'penjelasan_isi_3' => 'nullable|array',
            'penjelasan_isi_3.*' => 'nullable|string|max:5000',
        ];

        if ($this->hasPenjelasanFormulir2()) {
            $validationRules['penjelasan_isi'] = 'nullable|array';
            $validationRules['penjelasan_isi.*'] = 'nullable|string|max:5000';
        }

        $data = $request->validate($validationRules);

        if ($this->hasPenjelasanFormulir2() && $request->has('penjelasan_isi')) {
            $data['penjelasan_isi'] = $request->penjelasan_isi;
        }
        if ($request->has('penjelasan_isi_3')) {
            $data['penjelasan_isi_3'] = $request->penjelasan_isi_3;
        }

        if ($request->hasFile('ttd_petugas')) {
            $data['ttd_petugas'] = $request->file('ttd_petugas')->store('ttd', 'public');
        }
        if ($request->hasFile('ttd_pimpinan')) {
            $data['ttd_pimpinan'] = $request->file('ttd_pimpinan')->store('ttd', 'public');
        }

        if (isset($data['peringatan_awal'])) {
            $lines = preg_split('/\r?\n/', $data['peringatan_awal']);
            $counter = 1;
            foreach ($lines as &$line) {
                $trimmed = trim($line);
                if (preg_match('/^(\d+)\.\s*/', $trimmed)) {
                    $rest = preg_replace('/^(\d+)\.\s*/', '', $trimmed);
                    $indent = substr($line, 0, strpos($line, $trimmed[0]));
                    $line = $indent . $counter . '. ' . $rest;
                    $counter++;
                }
            }
            $data['peringatan_awal'] = implode("\n", $lines);
        }

        $data = $this->filterFindingData($data);

        MonitoringFinding::updateOrCreate(
            ['reportable_type' => get_class($report), 'reportable_id' => $report->id],
            $data
        );

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment")->with('success', 'Temuan monitoring berhasil disimpan.');
    }

    public function submit(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $savedResults = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->where('user_id', Auth::id())
            ->whereNotNull('criterion_id')
            ->pluck('item_id')
            ->toArray();

        $allItemIds = Category::whereNull('parent_id')
            ->with('items')
            ->get()
            ->pluck('items.*.id')
            ->flatten()
            ->toArray();

        $unfilled = array_diff($allItemIds, $savedResults);

        if (!empty($unfilled)) {
            $names = Category::whereNull('parent_id')->with('items')->get();
            $unfilledNames = [];
            foreach ($names as $cat) {
                foreach ($cat->items as $item) {
                    if (in_array($item->id, $unfilled)) {
                        $unfilledNames[] = $cat->name . ' → ' . $item->name;
                    }
                }
            }
            return back()->with('warning', 'Lengkapi semua penilaian terlebih dahulu:')
                ->with('unfilled', $unfilledNames);
        }

        // validate temuan completeness
        $finding = $report->finding;
        $prefix = $this->prefix();
        $incomplete = [];

        if (!$finding) {
            $incomplete[] = 'Pengisian Temuan';
        } else {
            $temuanFields = $this->requiredFindingFields();
            foreach ($temuanFields as $f) {
                if (empty(trim($finding->$f ?? ''))) {
                    $incomplete[] = 'Pengisian Temuan';
                    break;
                }
            }

            if (empty($finding->ttd_petugas)) {
                $incomplete[] = 'TTD Petugas';
            }
            if (empty($finding->ttd_pimpinan)) {
                $incomplete[] = 'TTD Pimpinan';
            }

            if ($this->hasPenjelasanFormulir2() && !empty($finding->penjelasan_isi) && is_array($finding->penjelasan_isi)) {
                foreach ($finding->penjelasan_isi as $val) {
                    if (empty(trim($val))) {
                        $incomplete[] = 'Penjelasan Formulir 2';
                        break;
                    }
                }
            }

            $results = Result::where('reportable_type', get_class($report))
                ->where('reportable_id', $report->id)
                ->whereNotNull('criterion_id')
                ->get()
                ->keyBy('item_id');
            $allItems = \App\Models\Item::with('criteria')->get();
            $zeroScoreItemIds = [];
            foreach ($allItems as $item) {
                if (!$item->bobot || $item->criteria->count() <= 1) continue;
                $result = $results->get($item->id);
                if (!$result || !$result->criterion_id) continue;
                $criteria = $item->criteria;
                $idx = $criteria->search(fn($c) => $c->id === $result->criterion_id);
                if ($idx === $criteria->count() - 1) {
                    $zeroScoreItemIds[] = $item->id;
                }
            }
            if (!empty($zeroScoreItemIds)) {
                $penjelasan3 = $finding->penjelasan_isi_3 ?? [];
                $missing = false;
                foreach ($zeroScoreItemIds as $itemId) {
                    if (empty($penjelasan3[$itemId]) || empty(trim($penjelasan3[$itemId]))) {
                        $missing = true;
                        break;
                    }
                }
                if ($missing) {
                    $incomplete[] = 'Penjelasan Formulir 3';
                }
            }
        }

        if (!empty($incomplete)) {
            return back()->with('warning', 'Lengkapi bagian berikut sebelum submit:')
                ->with('incomplete', $incomplete);
        }

        $total = 0;
        $report->load('results.item.criteria');
        foreach ($report->results as $result) {
            $item = $result->item;
            if (!$item || !$item->bobot) continue;
            $criteriaCount = $item->criteria->count();
            if ($criteriaCount <= 1) {
                $total += $item->bobot;
            } else {
                $interval = $item->bobot / ($criteriaCount - 1);
                $idx = $item->criteria->search(fn($c) => $c->id === $result->criterion_id);
                if ($idx !== false) {
                    $total += $item->bobot - ($interval * $idx);
                }
            }
        }

        $grade = $this->modelClass()::gradeFromScore($total);

        $updateData = ['nilai' => $total, 'grade' => $grade];
        if (!$report->submit_at) {
            $updateData['submit_at'] = now();
        }
        $report->update($updateData);

        if ($report->periode_label) {
            $this->recalculateRankings($report->periode_label);
        }

        session()->forget('assessment_snapshot_' . $report->id);

        return redirect("/{$this->prefix()}/{$report->id}")->with('success', 'Laporan berhasil disubmit.');
    }

    public function show($id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $categories = Category::whereNull('parent_id')
            ->with('items.criteria')
            ->orderBy('sort')
            ->get();

        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->with('criterion')
            ->get()
            ->keyBy('item_id');

        $totalScore = 0;
        foreach ($categories as $cat) {
            foreach ($cat->items as $item) {
                $r = $results->get($item->id);
                if (!$r || !$r->criterion_id) continue;
                $criteriaCount = $item->criteria->count();
                if (!$item->bobot) continue;
                if ($criteriaCount <= 1) {
                    $totalScore += $item->bobot;
                } else {
                    $interval = $item->bobot / ($criteriaCount - 1);
                    $idx = $item->criteria->search(fn($c) => $c->id === $r->criterion_id);
                    if ($idx !== false) {
                        $totalScore += $item->bobot - ($interval * $idx);
                    }
                }
            }
        }

        $filteredCategories = $categories->map(function ($cat) use ($results) {
            $cat->items = $cat->items->filter(function ($item) use ($results) {
                $r = $results->get($item->id);
                if (!$r || !$r->criterion_id) return false;
                $firstCriterionId = $item->criteria->first()?->id;
                return $r->criterion_id !== $firstCriterionId;
            })->values();
            return $cat;
        })->filter(fn($cat) => $cat->items->isNotEmpty())->values();

        return view('monitoring.show', compact('report', 'filteredCategories', 'results', 'totalScore') + [
            'prefix' => $this->prefix(),
            'allGerais' => \App\Models\Gerai::active()->get(['id', 'kode_gerai', 'nama_gerai', 'franchisee', 'no_telepon']),
            'allPgs' => \App\Models\Pg::orderBy('nama_pg')->get(['id', 'nama_pg', 'kota', 'no_telepon']),
        ]);
    }

    public function pdf($id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        $revisi = request()->boolean('revisi');
        if (request()->boolean('excel')) {
            $filename = "{$report->gerai->kode_gerai} - {$report->periode_label}";
        } else {
            $filename = ($revisi ? 'revisi-' : '') . "laporan-{$this->type}-{$report->gerai->kode_gerai}-" . $this->getReportDateForFilename($report, 'Asia/Jakarta');
        }

        if (request()->boolean('excel')) {
            $tempDir = storage_path('app/temp-pdf');
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

            $excelPath = $this->excel($report->id, $tempDir);
            if ($excelPath && file_exists($excelPath)) {
                $pdfPath = $tempDir . '/' . $filename . '.pdf';
                $pyScript = base_path('storage/app/xlwings-to-pdf.py');
                $cmd = 'python ' . escapeshellarg($pyScript) . ' ' . escapeshellarg($excelPath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
                exec($cmd, $output, $returnCode);
                @unlink($excelPath);

                if ($returnCode === 0 && file_exists($pdfPath)) {
                    return response()->download($pdfPath, $filename . '.pdf')->deleteFileAfterSend(true);
                }
            }
        }

        // Fallback: DomPDF
        return $this->pdfDompdf($report, $revisi, $filename);
    }

    private function pdfDompdf($report, $revisi, $filename)
    {
        $categories = Category::whereNull('parent_id')
            ->with('items.criteria')
            ->orderBy('sort')
            ->get();

        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->with('criterion')
            ->get()
            ->keyBy('item_id');

        $totalScore = 0;
        foreach ($categories as $cat) {
            foreach ($cat->items as $item) {
                $r = $results->get($item->id);
                if (!$r || !$r->criterion_id) continue;
                $criteriaCount = $item->criteria->count();
                if (!$item->bobot) continue;
                if ($criteriaCount <= 1) {
                    $totalScore += $item->bobot;
                } else {
                    $interval = $item->bobot / ($criteriaCount - 1);
                    $idx = $item->criteria->search(fn($c) => $c->id === $r->criterion_id);
                    if ($idx !== false) {
                        $totalScore += $item->bobot - ($interval * $idx);
                    }
                }
            }
        }

        $finding = $report->finding;

        // resize TTD images to base64 to reduce PDF size
        $ttdImages = [];
        if ($finding) {
            foreach (['ttd_petugas', 'ttd_pimpinan'] as $field) {
                $ttdImages[$field] = null;
                if (!empty($finding->$field)) {
                    $path = storage_path('app/public/' . $finding->$field);
                    if (file_exists($path)) {
                        $info = @getimagesize($path);
                        if ($info) {
                            $src = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($path) : (@imagecreatefrompng($path) ?: null);
                            if ($src) {
                                $w = $info[0];
                                $h = $info[1];
                                if ($w > 200 || $h > 200) {
                                    $ratio = min(200 / $w, 200 / $h);
                                    $w = round($w * $ratio);
                                    $h = round($h * $ratio);
                                    $thumb = imagecreatetruecolor($w, $h);
                                    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
                                    imagedestroy($src);
                                    $src = $thumb;
                                }
                                ob_start();
                                imagejpeg($src, null, 70);
                                $data = ob_get_clean();
                                imagedestroy($src);
                                $ttdImages[$field] = 'data:image/jpeg;base64,' . base64_encode($data);
                            }
                        }
                    }
                }
            }
        }

        $fontLoaded = $this->registerArimoFont();

        $pdf = Pdf::loadView('monitoring.pdf', compact('report', 'categories', 'results', 'totalScore', 'finding', 'fontLoaded', 'ttdImages', 'revisi') + ['prefix' => $this->prefix()]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['dpi' => 72, 'defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
        return $pdf->download($filename . '.pdf');
    }

    public function excel($id, $outputDir = null)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);
        set_time_limit(120);

        $tz = 'Asia/Jakarta';

        $headerReplacements = [
            '{nama_gerai}'       => $report->gerai->nama_gerai,
            '{kode_gerai}'       => $report->gerai->kode_gerai,
            '{franchisee}'       => strtoupper($report->gerai->franchisee),
            '{lokasi}'           => $report->location ?? '-',
            '{tanggal}'          => $report->checkin_at->setTimezone($tz)->format('d-m-Y'),
            '{tanggal_lengkap}'  => $report->checkin_at->setTimezone($tz)->locale('id')->isoFormat('D MMMM YYYY'),
            '{checkin}'          => $report->checkin_at->setTimezone($tz)->format('d-m-Y H:i:s'),
            '{submit}'           => $report->submit_at ? $report->submit_at->setTimezone($tz)->format('d-m-Y H:i:s') : '-',
            '{petugas}'          => $report->user?->name ?? '-',
            '{periode}'          => strtoupper($report->periode_label ?? $report->checkin_at->setTimezone($tz)->locale('id')->isoFormat('MMMM YYYY') ?? '-'),
            '{type}'             => 'Monitoring',
            '{nama_kota}'        => $report->gerai->nama_kota ?? '-',
            '{area}'             => $report->gerai->area ?? '-',
            '{opening_at}'       => $report->gerai->opening_at ? strtoupper($report->gerai->opening_at->locale('id')->isoFormat('D MMMM YYYY')) : '-',
        ];

        return $this->buildExcel($report, $headerReplacements, $outputDir);
    }

    protected function buildExcel($report, array $headerReplacements, $outputDir = null)
    {
        $templateFile = 'excel-template-' . $this->getTemplateName() . '.xlsx';

        if (!Storage::exists($templateFile)) {
            return back()->with('error', 'Upload template Excel terlebih dahulu di menu Template Excel.');
        }

        // calculate total score & build data
        $categories = Category::whereNull('parent_id')->with('items.criteria')->orderBy('sort')->get();
        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->with('criterion')->get()->keyBy('item_id');
        $totalScore = 0;
        $items = [];
        foreach ($categories as $cat) {
            foreach ($cat->items as $item) {
                $r = $results->get($item->id);
                if (!$r || !$r->criterion_id) continue;
                $criteriaCount = $item->criteria->count();
                if (!$item->bobot) continue;
                if ($criteriaCount <= 1) {
                    $score = $item->bobot;
                } else {
                    $interval = $item->bobot / ($criteriaCount - 1);
                    $idx = $item->criteria->search(fn($c) => $c->id === $r->criterion_id);
                    $score = ($idx !== false) ? $item->bobot - ($interval * $idx) : 0;
                }
                $totalScore += $score;
                $items[$item->name] = [
                    'category' => $cat->name,
                    'value'    => $r->criterion->description ?? '-',
                    'score'    => $score,
                    'bobot'    => $item->bobot,
                    'notes'    => $r->notes ?? '-',
                    'item_id'  => $item->id,
                ];
            }
        }

        // Calculate avg/min per item per gerai (matching analyticsExcel approach)
        $geraiScores = [];
        $geraiBobots = [];

        $targetPeriode = $this->getTargetPeriode($report);

        $periodReports = MonitoringReport::with('gerai', 'results.item.criteria', 'results.criterion')
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->where('periode_label', $targetPeriode)
            ->get();

        foreach ($periodReports as $pr) {
            $geraiKode = $pr->gerai->kode_gerai ?? 'unknown';
            foreach ($pr->results as $result) {
                $itemId = $result->item_id;
                $item = $result->item;
                if (!$item || !$item->bobot) continue;
                $criteriaCount = $item->criteria->count();
                if ($criteriaCount <= 1) {
                    $score = $item->bobot;
                } else {
                    $interval = $item->bobot / ($criteriaCount - 1);
                    $idx = $item->criteria->search(fn($c) => $c->id === $result->criterion_id);
                    $score = ($idx !== false) ? $item->bobot - ($interval * $idx) : 0;
                }
                $geraiScores[$itemId][$geraiKode] = $score;
                $geraiBobots[$itemId] = $item->bobot;
            }
        }



        $finding = $report->finding;
        $tz = 'Asia/Jakarta';

        // --- Calculate group labels ---
        $groupLabels = [];
        $penjelasanGroups = $this->penjelasanGroups();
        foreach ($penjelasanGroups as $group) {
            $achieved = 0;
            $maxBobot = 0;
            foreach ($group['item_ids'] as $itemId) {
                $gr = $results->get($itemId);
                if (!$gr) continue;
                $gItem = \App\Models\Item::with('criteria')->find($itemId);
                if (!$gItem || !$gItem->bobot) continue;
                $gCriteria = $gItem->criteria;
                $gCount = $gCriteria->count();
                if ($gCount <= 1) continue;
                $gInterval = $gItem->bobot / ($gCount - 1);
                $gIdx = $gCriteria->search(fn($c) => $c->id === $gr->criterion_id);
                if ($gIdx !== false) {
                    $achieved += $gItem->bobot - ($gInterval * $gIdx);
                }
                $maxBobot += $gItem->bobot;
            }
            if ($maxBobot > 0) {
                $pct = ($achieved / $maxBobot) * 100;
                if ($pct <= 85) {
                    $groupLabels[] = $group['name'];
                }
            }
        }
        if (empty($groupLabels)) {
            $groupLabels[] = 'Non Temuan';
        }

        $findingLines = [];
        $fieldModelMap = ['minor' => 'minor', 'mayor' => 'major', 'peringatan_awal' => 'peringatan_awal'];
        foreach (['minor', 'mayor', 'peringatan_awal'] as $field) {
            $modelField = $fieldModelMap[$field];
            $text = $finding?->$modelField ?? '';
            $findingLines[$field] = $text !== '' ? preg_split('/\r?\n/', $text) : [];
        }

        $headerReplacements = array_merge($headerReplacements, [
            '{total_score}'      => str_replace('.', ',', (string) $totalScore),
            '{minor}'            => $finding?->minor ?? '-',
            '{mayor}'            => $finding?->major ?? '-',
            '{peringatan_awal}'  => $finding?->peringatan_awal ?? '-',
        ]);

        // --- Build all replacements ---
        $replacements = $headerReplacements;

        // Per-sentence finding lines
        foreach ($findingLines as $field => $lines) {
            for ($i = 0; $i < count($lines); $i++) {
                $replacements['{' . $field . '_' . ($i + 1) . '}'] = $lines[$i];
            }
        }

        // Per-item replacements
        foreach ($items as $name => $data) {
            $replacements['{item_score:' . $name . '}'] = str_replace('.', ',', (string) $data['score']);
            $replacements['{item_value:' . $name . '}'] = $data['value'];
            $replacements['{item_notes:' . $name . '}'] = $data['notes'];
        }

        // Sort by placeholder length descending to avoid partial matches
        uksort($replacements, fn($a, $b) => strlen($b) - strlen($a));

        // Build a map of item_score placeholder → numeric value (with dot separator)
        $numericScores = [];
        foreach ($items as $name => $data) {
            $numericScores['{item_score:' . $name . '}'] = $data['score'];
        }

        $templatePath = Storage::path($templateFile);
        $dateSuffix = $this->getReportDateForFilename($report, $tz);
        $filename = "laporan-{$this->type}-{$report->gerai->kode_gerai}-" . $dateSuffix . '.xlsx';
        $outPath = $outputDir ? rtrim($outputDir, '\\/') . DIRECTORY_SEPARATOR . $filename : Storage::path($filename);

        // Copy template → output (preserves all charts, images, formulas, formatting)
        copy($templatePath, $outPath);

        // Open output as ZIP and replace placeholders directly in the XML
        $zip = new ZipArchive;
        if ($zip->open($outPath) !== true) {
            return back()->with('error', 'Gagal membuka file Excel.');
        }

        // Normalize item names for matching (Unicode quotes → ASCII, collapse whitespace)
        $norm = function($s) {
            $s = str_replace(["\xC2\xAB", "\xC2\xBB", "\xE2\x80\x98", "\xE2\x80\x99",
                "\xE2\x80\x9A", "\xE2\x80\x9B", "\xE2\x80\x9C", "\xE2\x80\x9D",
                "\xE2\x80\x9E", "\xE2\x80\x9F", "\xE2\x80\xB9", "\xE2\x80\xBA"], '"', $s);
            return trim(preg_replace('/\s+/', ' ', $s));
        };

        // Build normalized lookup for item_score placeholders
        $normNumericScores = [];
        foreach ($numericScores as $placeholder => $score) {
            $normNumericScores[$norm($placeholder)] = $score;
        }

        // --- Phase 1: parse sharedStrings.xml to find indices of item_score placeholders ---
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        $ssIndexScore = []; // shared string index → numeric score
        $ssIndexText = [];  // shared string index → original text (for Phase 2 search key)
        if ($ssContent !== false) {
            $ssDom = new DOMDocument;
            $ssDom->loadXML($ssContent);
            $siNodes = $ssDom->getElementsByTagName('si');
            for ($i = 0; $i < $siNodes->length; $i++) {
                $t = $siNodes->item($i)->getElementsByTagName('t')->item(0);
                $val = $t ? $t->textContent : '';
                $normVal = $norm($val);
                if (isset($normNumericScores[$normVal])) {
                    $ssIndexScore[$i] = $normNumericScores[$normVal];
                    $ssIndexText[$i] = $val;
                }
            }
        }

        // --- Phase 2: str_replace on ALL XML files (simple text replacement) ---
        // Use database keys for general replacements
        $searchKeys = array_map(
            fn($k) => htmlspecialchars((string) $k, ENT_XML1 | ENT_NOQUOTES, 'UTF-8'),
            array_keys($replacements)
        );
        $replaceValues = array_map(
            fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_NOQUOTES, 'UTF-8'),
            array_values($replacements)
        );

        // For item_score in sharedStrings.xml, use the actual XML text as search key
        $ssSearchKeys = $searchKeys;
        $ssReplaceValues = $replaceValues;
        foreach ($ssIndexText as $idx => $text) {
            $score = $ssIndexScore[$idx];
            $ssSearchKeys[] = htmlspecialchars($text, ENT_XML1 | ENT_NOQUOTES, 'UTF-8');
            $ssReplaceValues[] = str_replace('.', ',', (string) $score);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!str_starts_with($name, 'xl/')) continue;
            if (!str_ends_with($name, '.xml')) continue;

            $content = $zip->getFromIndex($i);
            if ($content === false || $content === '') continue;

            $keys = ($name === 'xl/sharedStrings.xml') ? $ssSearchKeys : $searchKeys;
            $vals = ($name === 'xl/sharedStrings.xml') ? $ssReplaceValues : $replaceValues;

            $newContent = str_replace($keys, $vals, $content);

            if ($newContent !== $content) {
                $zip->addFromString($name, $newContent);
            }
        }

        // Force full recalculation of formulas on open
        $wbContent = $zip->getFromName('xl/workbook.xml');
        if ($wbContent !== false) {
            $wbContent = preg_replace('/<calcPr\s[^>]*\/>/', '<calcPr calcId="181029" fullCalcOnLoad="1"/>', $wbContent, 1);
            $zip->addFromString('xl/workbook.xml', $wbContent);
        }

        // --- Fill FORMULIR HASIL 1 sheet1 cells ---
        $grade = $this->modelClass()::gradeFromScore((float) $totalScore);

        if (in_array($grade, ['A', 'B'])) {
            $kesimpulanText = 'Penerapan standar operasional telah berjalan dengan baik dan sesuai ketentuan. Pertahankan konsistensi pelaksanaan serta lakukan peningkatan berkelanjutan pada aspek yang masih dapat dioptimalkan agar kualitas layanan tetap terjaga.';
        } elseif ($grade === 'C') {
            $kesimpulanText = 'Penerapan standar operasional telah berjalan cukup baik, namun masih terdapat beberapa ketidaksesuaian yang perlu segera diperbaiki. Diperlukan tindak lanjut terhadap temuan serta monitoring berkala untuk meningkatkan kualitas operasional.';
        } else {
            $kesimpulanText = 'Penerapan standar operasional belum memenuhi standar yang ditetapkan. Diperlukan perbaikan menyeluruh terhadap temuan, pembinaan kepada karyawan gerai, serta evaluasi.';
        }

        $sheet1Content = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet1Content !== false) {
            $dom1 = new DOMDocument;
            $dom1->loadXML($sheet1Content);
            $xpath1 = new DOMXPath($dom1);
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $xpath1->registerNamespace('s', $ns);

            // (Excel XML helpers via ExcelXmlHelpers trait)

            // --- Ensure wrap+center+noBorder xf exists in styles.xml for A41 ---
            $wrapStyleIdx = 0;
            if ($this->type === 'pra-monitoring') {
                $stylesContent = $zip->getFromName('xl/styles.xml');
                if ($stylesContent !== false) {
                    $stylesDoc = new DOMDocument;
                    $stylesDoc->loadXML($stylesContent);
                    $stylesNs = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                    // fontId=2 is Arimo 12
                    $wrapStyleIdx = $this->ensureCellXfStyle($stylesDoc, $stylesNs, 2, 0, 'left', 'center', true);
                    $zip->addFromString('xl/styles.xml', $stylesDoc->saveXML());
                }
            }

            // --- Pra-Monitoring specific cells ---
            $this->fillSheet1Custom($dom1, $xpath1, $ns, $totalScore, $grade, $kesimpulanText, $wrapStyleIdx);

            // --- Fill B44: Grade label with bold grade letter (via DOMDocument) ---
            $b44cells = $xpath1->query("//s:c[@r='B44']");
            if ($b44cells->length > 0) {
                $b44 = $b44cells->item(0);
                $b44->setAttribute('t', 'inlineStr');
                foreach (['v', 'f'] as $tag) {
                    $existing = $b44->getElementsByTagNameNS($ns, $tag)->item(0);
                    if ($existing) $b44->removeChild($existing);
                }
                $is = $dom1->createElementNS($ns, 'is');
                $is->appendChild(static::xmlMakeRun($dom1, $ns, 'Gerai masuk dalam '));
                $is->appendChild(static::xmlMakeRun($dom1, $ns, 'Grade ' . $grade, true));
                $is->appendChild(static::xmlMakeRun($dom1, $ns, ' dengan kategori:'));
                $b44->appendChild($is);
            }

            // --- Fill E9 (previous period score) and G9 (current score) ---
            $prevTotalScore = $this->getPreviousScore($report, $totalScore);

            foreach (['E' => $prevTotalScore, 'G' => $totalScore] as $col => $score) {
                if ($score === null) continue;
                $ref = $col . '9';
                static::xmlSetNumber($xpath1, $dom1, $ns, $ref, round($score));
            }

            $zip->addFromString('xl/worksheets/sheet1.xml', $dom1->saveXML());
        }

        // --- Phase 3: convert item_score shared string cells to number type ---
        $sheet3ZeroItems = [];
        if (!empty($ssIndexScore)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (!str_starts_with($name, 'xl/worksheets/')) continue;
                if (!str_ends_with($name, '.xml')) continue;

                $content = $zip->getFromIndex($i);
                if ($content === false || $content === '') continue;

                $dom = new DOMDocument;
                $dom->loadXML($content);
                $changed = false;

                foreach ($dom->getElementsByTagName('c') as $cell) {
                    if ($cell->getAttribute('t') !== 's') continue;

                    $v = $cell->getElementsByTagName('v')->item(0);
                    if (!$v) continue;

                    $idx = (int) $v->textContent;
                    if (!isset($ssIndexScore[$idx])) continue;

                    // Convert to number cell
                    $cell->removeAttribute('t');
                    $v->textContent = $ssIndexScore[$idx]; // decimal dot separator
                    $changed = true;

                    // Track zero-score checkbox items in sheet3 (extract name from placeholder)
                    if ($name === 'xl/worksheets/sheet3.xml' && (int)$ssIndexScore[$idx] === 0) {
                        $placeholder = $ssIndexText[$idx] ?? '';
                        if (preg_match('/\{item_score:(.*?)\}/', $placeholder, $m)) {
                            $sheet3ZeroItems[] = trim($m[1]);
                        }
                    }

                    $this->onPhase3Cell($name, $idx, $ssIndexText, $ssIndexScore, $cell, $dom, $items);
                }

                if ($changed) {
                    $newContent = $dom->saveXML();
                    $zip->addFromString($name, $newContent);
                }
            }
        }

        // --- Phase 0 (early): Build group→items mapping from original template ---
        $getItemRows = function($xpath3, $rowNum, &$visited) use (&$getItemRows) {
            if (in_array($rowNum, $visited)) return [];
            $visited[] = $rowNum;
            $iRef = 'I' . $rowNum;
            $cells = $xpath3->query("//s:c[@r='$iRef']");
            foreach ($cells as $cell) {
                $f = $cell->getElementsByTagName('f')->item(0);
                if (!$f) {
                    if ($cell->getAttribute('t') === 's') {
                        return [$rowNum];
                    }
                    return [];
                }
                $formula = $f->textContent;
                if (preg_match('/^I(\d+)$/', $formula, $m)) {
                    return $getItemRows($xpath3, (int)$m[1], $visited);
                }
                if (preg_match('/^SUM\(I(\d+):I(\d+)\)$/', $formula, $m)) {
                    $results = [];
                    for ($r = (int)$m[1]; $r <= (int)$m[2]; $r++) {
                        $results = array_merge($results, $getItemRows($xpath3, $r, $visited));
                    }
                    return $results;
                }
                if (preg_match_all('/I(\d+)(?!\d)/', $formula, $m)) {
                    $results = [];
                    foreach ($m[1] as $r) {
                        $r = (int)$r;
                        if ($r !== $rowNum) $results = array_merge($results, $getItemRows($xpath3, $r, $visited));
                    }
                    return array_unique($results);
                }
                return [];
            }
            return [];
        };

        $tmpZip = new ZipArchive;
        if ($tmpZip->open($templatePath) === true) {
            $tmpSS = $tmpZip->getFromName('xl/sharedStrings.xml');
            $tmpS3 = $tmpZip->getFromName('xl/worksheets/sheet3.xml');
            $tmpS2 = $tmpZip->getFromName('xl/worksheets/sheet2.xml');
            $tmpZip->close();

            if ($tmpSS && $tmpS3 && $tmpS2) {
                $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

                $ssDom0 = new DOMDocument;
                $ssDom0->loadXML($tmpSS);
                $siNodes0 = $ssDom0->getElementsByTagName('si');
                $ssText0 = [];
                for ($i = 0; $i < $siNodes0->length; $i++) {
                    $t = $siNodes0->item($i)->getElementsByTagName('t')->item(0);
                    $ssText0[$i] = $t ? $t->textContent : '';
                }

                $dom3 = new DOMDocument;
                $dom3->loadXML($tmpS3);
                $xpath3 = new DOMXPath($dom3);
                $xpath3->registerNamespace('s', $ns);

                $dom2 = new DOMDocument;
                $dom2->loadXML($tmpS2);
                $xpath2 = new DOMXPath($dom2);
                $xpath2->registerNamespace('s', $ns);

                // Parse sheet2 H cells → J row mapping
                $hCells0 = [];
                foreach ($xpath2->query('//s:c[s:f]') as $cell) {
                    $ref = $cell->getAttribute('r');
                    if (!str_starts_with($ref, 'H')) continue;
                    $hRow = (int)substr($ref, 1);
                    $f = $cell->getElementsByTagName('f')->item(0);
                    if (preg_match('/J(\d+)/', $f->textContent, $m)) {
                        $jRow = (int)$m[1];
                        $cRef = 'C' . $hRow;
                        $cLabel = '';
                        foreach ($xpath2->query("//s:c[@r='$cRef']") as $cc) {
                            if ($cc->getAttribute('t') === 's') {
                                $v = $cc->getElementsByTagName('v')->item(0);
                                if ($v) {
                                    $idx = (int)$v->textContent;
                                    if (isset($ssText0[$idx])) $cLabel = trim($ssText0[$idx]);
                                }
                            }
                        }
                        $hCells0[$hRow] = ['jRow' => $jRow, 'cLabel' => $cLabel];
                    }
                }

                // Build normalized lookup from $items keyed by norm({item_score:name})
                $itemsByPlaceholder = [];
                foreach ($items as $name => $data) {
                    $key = $norm('{item_score:' . $name . '}');
                    $itemsByPlaceholder[$key] = $data['score'];
                }

                // For each H cell, resolve I formula recursively → get item rows
                foreach ($hCells0 as $hRow => &$info) {
                    $catRow = $info['jRow'];
                    $visited = [];
                    $itemRows = $getItemRows($xpath3, $catRow, $visited);
                    $info['itemRows'] = $itemRows;

                    // Get F value from template
                    $fRef = 'F' . $catRow;
                    $fValue = 0;
                    foreach ($xpath3->query("//s:c[@r='$fRef']") as $fc) {
                        $fv = $fc->getElementsByTagName('v')->item(0);
                        if ($fv) $fValue = (float)$fv->textContent;
                    }
                    $info['fValue'] = $fValue;

                    // Get item_score placeholder text from each terminal item row's I column
                    $itemPlaceholders = [];
                    foreach ($itemRows as $ir) {
                        $iRef = 'I' . $ir;
                        foreach ($xpath3->query("//s:c[@r='$iRef']") as $ic) {
                            if ($ic->getAttribute('t') === 's') {
                                $v = $ic->getElementsByTagName('v')->item(0);
                                if ($v) {
                                    $idx = (int)$v->textContent;
                                    if (isset($ssText0[$idx])) {
                                        $itemPlaceholders[] = $ssText0[$idx];
                                    }
                                }
                            }
                        }
                    }
                    $info['itemPlaceholders'] = $itemPlaceholders;
                }
                unset($info);

                // Build low-score list
                $lowItems = [];
                foreach ($hCells0 as $info) {
                    if (trim($info['cLabel']) === '') continue;
                    if ($info['fValue'] <= 0) continue;

                    $groupTotal = 0;
                    $matchedAny = false;
                    foreach ($info['itemPlaceholders'] as $placeholder) {
                        $normKey = $norm($placeholder);
                        if (isset($itemsByPlaceholder[$normKey])) {
                            $groupTotal += $itemsByPlaceholder[$normKey];
                            $matchedAny = true;
                        }
                    }
                    if ($matchedAny) {
                        $pct = ($groupTotal / $info['fValue']) * 100;
                        if ($pct <= 85) $lowItems[] = $info['cLabel'];
                    }
                }

                // Write to sheet2
                $sheet2Content = $zip->getFromName('xl/worksheets/sheet2.xml');
                if ($sheet2Content !== false) {
                    $dom2 = new DOMDocument;
                    $dom2->preserveWhiteSpace = false;
                    $dom2->loadXML($sheet2Content);
                    $xpath2 = new DOMXPath($dom2);
                    $ns2 = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                    $xpath2->registerNamespace('s', $ns2);

                    $isNonTemuan = count($groupLabels) === 1 && $groupLabels[0] === 'Non Temuan';
                    $penjelasanIsi = ($this->hasPenjelasanFormulir2() && $finding) ? ($finding->penjelasan_isi ?? []) : [];

                    if ($this->fillSheet2Custom($dom2, $xpath2, $ns2, $findingLines, $finding, $lowItems, $sheet3ZeroItems, $items, $zip)) {
                        // Custom handler (pra-monitoring) took care of Sheet2

                    } elseif ($isNonTemuan) {
                        // === Non Temuan: template unchanged, paste below PENJELASAN ===
                        // Load shared strings to resolve cell text
                        $ssContent2 = $zip->getFromName('xl/sharedStrings.xml');
                        $ssTextMap = [];
                        if ($ssContent2 !== false) {
                            $ssDom2 = new DOMDocument;
                            $ssDom2->loadXML($ssContent2);
                            foreach ($ssDom2->getElementsByTagName('si') as $idx => $si) {
                                $t = $si->getElementsByTagName('t')->item(0);
                                $ssTextMap[$idx] = $t ? $t->textContent : '';
                            }
                        }

                        // Find PENJELASAN row
                        $penjelasanRowNum = 0;
                        foreach ($xpath2->query('//s:sheetData/s:row') as $row) {
                            $r = (int)$row->getAttribute('r');
                            foreach ($xpath2->query('s:c', $row) as $cell) {
                                $text = '';
                                $type = $cell->getAttribute('t');
                                if ($type === 's') {
                                    $v = $cell->getElementsByTagName('v')->item(0);
                                    if ($v) { $idx = (int)$v->textContent; $text = $ssTextMap[$idx] ?? ''; }
                                } elseif ($type === 'inlineStr') {
                                    $t = $cell->getElementsByTagName('t')->item(0);
                                    $text = $t ? $t->textContent : '';
                                }
                                if (str_contains($text, 'PENJELASAN')) { $penjelasanRowNum = $r; break 2; }
                            }
                        }

                         // Filter only non-temuan entries
                        $nonTemuanIsi = [];
                        foreach ($penjelasanIsi as $i => $teks) {
                            $label = $groupLabels[$i] ?? '';
                            if ($label === 'Non Temuan') {
                                $nonTemuanIsi[] = $teks;
                            }
                        }

                        if ($penjelasanRowNum > 0 && $nonTemuanIsi) {
                            $sheetData = $xpath2->query('//s:sheetData')->item(0);
                            // Remove rows after PENJELASAN
                            foreach ($xpath2->query('//s:sheetData/s:row[@r > ' . $penjelasanRowNum . ']') as $row) {
                                $sheetData->removeChild($row);
                            }
                            // Insert penjelasan in A after PENJELASAN row
                            $rn = $penjelasanRowNum + 1;
                            foreach ($nonTemuanIsi as $i => $teks) {
                                if (trim($teks) === '') continue;
                                $line = ($i + 1) . '. ' . trim($teks);
                                $newRow = $dom2->createElementNS($ns2, 'row');
                            $newRow->setAttribute('r', (string)$rn);
                            $newRow->setAttribute('spans', '1:13');
                            $newRow->setAttribute('ht', '15.5');
                                for ($col = 'A'; $col !== 'N'; $col++) {
                                    $ref = $col . $rn;
                                    $cell = $dom2->createElementNS($ns2, 'c');
                                    $cell->setAttribute('r', $ref);
                                    if ($col === 'A') {
                                        $cell->setAttribute('s', '1');
                                        $cell->setAttribute('t', 'inlineStr');
                                        $is = $dom2->createElementNS($ns2, 'is');
                                        $t = $dom2->createElementNS($ns2, 't');
                                        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                                        $t->appendChild($dom2->createTextNode($line));
                                        $is->appendChild($t);
                                        $cell->appendChild($is);
                                    } else { $cell->setAttribute('s', '1'); }
                                    $newRow->appendChild($cell);
                                }
                                $sheetData->appendChild($newRow);
                                $rn++;
                            }
                        }
                    } else {
                        // === Bukan Non Temuan: delete + rewrite rows 30+ ===
                        $sheetData = $xpath2->query('//s:sheetData')->item(0);

                        // Remove all rows from row 30 onwards
                        foreach ($xpath2->query('//s:sheetData/s:row[@r >= 30]') as $row) {
                            $sheetData->removeChild($row);
                        }

                        // Helper: create data row
                        $makeDataRow = function($rowNum, $colLetter, $text = null) use ($dom2, $ns2) {
                            $row = $dom2->createElementNS($ns2, 'row');
                            $row->setAttribute('r', (string)$rowNum);
                            $row->setAttribute('spans', '1:13');
                            $row->setAttribute('ht', '15.5');
                            for ($col = 'A'; $col !== 'N'; $col++) {
                                $ref = $col . $rowNum;
                                $cell = $dom2->createElementNS($ns2, 'c');
                                $cell->setAttribute('r', $ref);
                                if ($col === $colLetter && $text !== null) {
                                    $cell->setAttribute('s', '1');
                                    $cell->setAttribute('t', 'inlineStr');
                                    $is = $dom2->createElementNS($ns2, 'is');
                                    $t = $dom2->createElementNS($ns2, 't');
                                    $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                                    $t->appendChild($dom2->createTextNode($text));
                                    $is->appendChild($t);
                                    $cell->appendChild($is);
                                } else { $cell->setAttribute('s', '1'); }
                                $row->appendChild($cell);
                            }
                            return $row;
                        };

                        $rn = 30;
                        // --- Low-score list (B column) ---
                        if (!empty($lowItems)) {
                            foreach ($lowItems as $i => $label) {
                                $line = ($i + 1) . '. ' . $label;
                                $sheetData->appendChild($makeDataRow($rn, 'B', $line));
                                $rn++;
                            }
                            $sheetData->appendChild($makeDataRow($rn, 'A'));
                            $rn++;
                        }
                        // PENJELASAN row
                        $penjelasanRow = $dom2->createElementNS($ns2, 'row');
                        $penjelasanRow->setAttribute('r', (string)$rn);
                        $penjelasanRow->setAttribute('spans', '1:13');
                        $penjelasanRow->setAttribute('ht', '15.5');
                        for ($col = 'A'; $col !== 'N'; $col++) {
                            $ref = $col . $rn;
                            $cell = $dom2->createElementNS($ns2, 'c');
                            $cell->setAttribute('r', $ref);
                            if ($col === 'A') {
                                $cell->setAttribute('s', '12');
                                $cell->setAttribute('t', 'inlineStr');
                                $is = $dom2->createElementNS($ns2, 'is');
                                $t = $dom2->createElementNS($ns2, 't');
                                $t->appendChild($dom2->createTextNode('PENJELASAN:'));
                                $is->appendChild($t);
                                $cell->appendChild($is);
                            } else { $cell->setAttribute('s', '1'); }
                            $penjelasanRow->appendChild($cell);
                        }
                        $sheetData->appendChild($penjelasanRow);
                        $rn++;
                        // --- Penjelasan items in B ---
                            if ($penjelasanIsi) {
                                foreach ($penjelasanIsi as $i => $teks) {
                                    if (trim($teks) === '') continue;
                                    $line = ($i + 1) . '. ' . trim($teks);
                                    $sheetData->appendChild($makeDataRow($rn, 'B', $line));
                                    $rn++;
                                }
                            }
                        }

                        $zip->addFromString('xl/worksheets/sheet2.xml', $dom2->saveXML());
                }

                // --- Sheet3: Zero-score items ---
                $sheet3Content = $zip->getFromName('xl/worksheets/sheet3.xml');
                if ($sheet3Content !== false) {
                    $dom3 = new DOMDocument;
                    $dom3->preserveWhiteSpace = false;
                    $dom3->loadXML($sheet3Content);
                    $xpath3 = new DOMXPath($dom3);
                    $ns3 = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                    $xpath3->registerNamespace('s', $ns3);

                    $sheetData3 = $xpath3->query('//s:sheetData')->item(0);

                    // --- Pra-Monitoring: fill datachart Sheet3 ---
                    if ($this->type === 'pra-monitoring') {
                        $this->fillSheet3Custom($dom3, $xpath3, $ns3, $totalScore, $headerReplacements['{tanggal_lengkap}']);
                        $zip->addFromString('xl/worksheets/sheet3.xml', $dom3->saveXML());
                    } else {

                    // --- Sheet3: Info block rows (before zero-score, inserted between PA and NOTE) ---
                    // Load shared strings for PA/NOTE text lookup
                    $ssContent3 = $zip->getFromName('xl/sharedStrings.xml');
                    $ssTextByIndex = [];
                    if ($ssContent3 !== false) {
                        $ssDom3 = new DOMDocument;
                        $ssDom3->loadXML($ssContent3);
                        $ssXpath3 = new DOMXPath($ssDom3);
                        $ssXpath3->registerNamespace('s', $ns3);
                        foreach ($ssXpath3->query('//s:si') as $idx => $si) {
                            $t = $ssXpath3->query('.//s:t', $si)->item(0);
                            $ssTextByIndex[$idx] = $t ? $t->textContent : '';
                        }
                    }

                    $paRn = 0;
                    $noteRn = 0;
                    $paRow = null;
                    $noteRow = null;
                    foreach ($xpath3->query('//s:sheetData/s:row') as $row) {
                        $r = (int)$row->getAttribute('r');
                        foreach ($xpath3->query('s:c', $row) as $cell) {
                            $type = $cell->getAttribute('t');
                            $text = '';
                            if ($type === 's') {
                                $v = $cell->getElementsByTagName('v')->item(0);
                                if ($v) {
                                    $idx = (int)$v->textContent;
                                    $text = $ssTextByIndex[$idx] ?? '';
                                }
                            } elseif ($type === 'inlineStr') {
                                $t = $cell->getElementsByTagName('t')->item(0);
                                $text = $t ? $t->textContent : '';
                            }
                            if (str_contains($text, 'Temuan dengan kategori Peringatan Awal')) {
                                $paRn = $r;
                                $paRow = $row;
                            } elseif ($text === 'NOTE:' && $paRn > 0 && $noteRn === 0) {
                                $noteRn = $r;
                                $noteRow = $row;
                            }
                        }
                    }

                    if ($paRow && $noteRow && $finding) {
                        // Remove gap rows between PA header and NOTE
                        $rowsToRemove = [];
                        foreach ($xpath3->query('//s:sheetData/s:row[@r > ' . $paRn . ' and @r < ' . $noteRn . ']') as $row) {
                            $rowsToRemove[] = $row;
                        }
                        foreach ($rowsToRemove as $row) {
                            $sheetData3->removeChild($row);
                        }

                        // Build info block rows
                        $infoRn = $paRn + 1;
                        // (makeBRow via ExcelXmlHelpers trait)

                        $infoRows = [];
                        $pengawas = $finding?->pengawas ?? '';
                        if ($pengawas !== '') {
                            foreach (preg_split('/\r?\n/', $pengawas) as $line) {
                                if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom3, $ns3, trim($line), $infoRn);
                            }
                        }
                        $aj = $finding?->rata_rata_aj ?? '';
                        if ($aj !== '') {
                            $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Rerata AJ ± ' . $aj . ' gln/hr', $infoRn);
                        }
                        if ($this->type !== 'pra-monitoring') {
                            $tds = $finding?->tds ?? '';
                            if ($tds !== '') {
                                $tdsDisplay = str_replace('/', ' ppm/', $tds);
                                if (str_contains($tds, '/')) $tdsDisplay .= '°C';
                                $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'TDS: ' . $tdsDisplay, $infoRn);
                            }
                        }
                        $mo = $finding?->mesin_ozon ?? '';
                        if ($mo !== '') {
                            $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'MO: ' . $mo, $infoRn);
                        }
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, '', $infoRn);
                        $paLines = $findingLines['peringatan_awal'] ?? [];
                        foreach ($paLines as $line) {
                            if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom3, $ns3, $line, $infoRn);
                        }
                        $noteContent = $finding?->note ?? '';
                        if ($noteContent !== '') {
                            $infoRows[] = static::xmlMakeBRow($dom3, $ns3, '', $infoRn);
                            $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Note:', $infoRn);
                            foreach (preg_split('/\r?\n/', $noteContent) as $line) {
                                if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom3, $ns3, trim($line), $infoRn);
                            }
                        }
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, '', $infoRn);
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Checklist tampilan gerai:', $infoRn);
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Kondisi cat: ' . ($finding?->kondisi_cat ?: 'Baik'), $infoRn);
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Kondisi awning: ' . ($finding?->kondisi_awning ?: 'Baik'), $infoRn);
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Kondisi vinyl reklame dinding/jalan: ' . ($finding?->kondisi_vinyl ?: 'Baik'), $infoRn);
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, 'Kondisi stiker kaca: ' . ($finding?->kondisi_stiker_kaca ?: 'Baik'), $infoRn);
                        $infoRows[] = static::xmlMakeBRow($dom3, $ns3, '', $infoRn);

                        // Insert all info rows before NOTE
                        foreach ($infoRows as $row) {
                            $sheetData3->insertBefore($row, $noteRow);
                        }

                        // Renumber rows AFTER info block (skip info rows, renumber NOTE+rest)
                        $allRows = [];
                        foreach ($sheetData3->childNodes as $child) {
                            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'row') {
                                $allRows[] = $child;
                            }
                        }
                        $renumberRn = $infoRn;
                        $pastPa = false;
                        $infoIdx = 0;
                        foreach ($allRows as $row) {
                            $rAttr = (int)$row->getAttribute('r');
                            if (!$pastPa) {
                                if ($rAttr === $paRn) $pastPa = true;
                                continue;
                            }
                            if ($infoIdx < count($infoRows)) {
                                $infoIdx++;
                                continue;
                            }
                            $row->setAttribute('r', (string)$renumberRn);
                            foreach ($xpath3->query('s:c', $row) as $cell) {
                                $ref = $cell->getAttribute('r');
                                $cell->setAttribute('r', preg_replace('/\d+$/', (string)$renumberRn, $ref));
                            }
                            $renumberRn++;
                        }
                    }

                    // --- Sheet3: Zero-score items ---
                    if (!empty($sheet3ZeroItems)) {
                        $n = count($sheet3ZeroItems);

                        $makeRow = function($rn, $bText = null) use ($dom3, $ns3) {
                            $row = $dom3->createElementNS($ns3, 'row');
                            $row->setAttribute('r', (string)$rn);
                            $row->setAttribute('spans', '1:13');
                            for ($col = 'A'; $col !== 'N'; $col++) {
                                $ref = $col . $rn;
                                $cell = $dom3->createElementNS($ns3, 'c');
                                $cell->setAttribute('r', $ref);
                                $cell->setAttribute('s', '1');
                                if ($col === 'B' && $bText !== null) {
                                    $cell->setAttribute('t', 'inlineStr');
                                    $is = $dom3->createElementNS($ns3, 'is');
                                    $t = $dom3->createElementNS($ns3, 't');
                                    $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                                    $t->appendChild($dom3->createTextNode($bText));
                                    $is->appendChild($t);
                                    $cell->appendChild($is);
                                }
                                $row->appendChild($cell);
                            }
                            return $row;
                        };

                        $firstLine = '1. ' . $sheet3ZeroItems[0];
                        foreach ($xpath3->query('//s:sheetData/s:row[@r=120]/s:c[@r="B120"]') as $cell) {
                            while ($cell->firstChild) $cell->removeChild($cell->firstChild);
                            $cell->setAttribute('s', '1');
                            $cell->setAttribute('t', 'inlineStr');
                            $is = $dom3->createElementNS($ns3, 'is');
                            $t = $dom3->createElementNS($ns3, 't');
                            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                            $t->appendChild($dom3->createTextNode($firstLine));
                            $is->appendChild($t);
                            $cell->appendChild($is);
                        }

                        if ($n >= 2) {
                            $refRow121 = null;
                            foreach ($xpath3->query('//s:sheetData/s:row[@r=121]') as $row) {
                                $refRow121 = $row;
                            }

                            $delta = $n - 1;
                            $rowsToShift = [];
                            foreach ($xpath3->query('//s:sheetData/s:row[@r >= 121]') as $row) {
                                $rowsToShift[] = $row;
                            }
                            foreach ($rowsToShift as $row) {
                                $oldR = (int)$row->getAttribute('r');
                                $newR = $oldR + $delta;
                                $row->setAttribute('r', (string)$newR);
                                foreach ($xpath3->query('s:c', $row) as $cell) {
                                    $ref = $cell->getAttribute('r');
                                    $cell->setAttribute('r', preg_replace('/\d+$/', (string)$newR, $ref));
                                }
                            }

                            $rn = 121;
                            for ($i = 1; $i < $n; $i++) {
                                $line = ($i + 1) . '. ' . $sheet3ZeroItems[$i];
                                $sheetData3->insertBefore($makeRow($rn, $line), $refRow121);
                                $rn++;
                            }
                        }
                    } else {
                        $rowsToRemove = [];
                        foreach ($xpath3->query('//s:sheetData/s:row[@r >= 121 and @r <= 122]') as $row) {
                            $rowsToRemove[] = $row;
                        }
                        foreach ($rowsToRemove as $row) {
                            $sheetData3->removeChild($row);
                        }

                        $rowsToShift = [];
                        foreach ($xpath3->query('//s:sheetData/s:row[@r >= 123]') as $row) {
                            $rowsToShift[] = $row;
                        }
                        foreach ($rowsToShift as $row) {
                            $oldR = (int)$row->getAttribute('r');
                            $newR = $oldR - 2;
                            $row->setAttribute('r', (string)$newR);
                            foreach ($xpath3->query('s:c', $row) as $cell) {
                                $ref = $cell->getAttribute('r');
                                $cell->setAttribute('r', preg_replace('/\d+$/', (string)$newR, $ref));
                            }
                        }
                    }



                    // --- Sheet3: MINOR and MAJOR sections ---
                    $minorRn = 0;
                    $majorRn = 0;
                    foreach ($xpath3->query('//s:sheetData/s:row') as $row) {
                        $r = (int)$row->getAttribute('r');
                        foreach ($xpath3->query('s:c', $row) as $cell) {
                            $ref = $cell->getAttribute('r');
                            if (!str_starts_with($ref, 'B')) continue;
                            $type = $cell->getAttribute('t');
                            $text = '';
                            if ($type === 's') {
                                $v = $cell->getElementsByTagName('v')->item(0);
                                if ($v) {
                                    $idx = (int)$v->textContent;
                                    $text = $ssTextByIndex[$idx] ?? '';
                                }
                            } elseif ($type === 'inlineStr') {
                                $t = $cell->getElementsByTagName('t')->item(0);
                                $text = $t ? $t->textContent : '';
                            }
                            if (str_contains($text, 'Temuan dengan kategori MINOR')) {
                                $minorRn = $r;
                            } elseif (str_contains($text, 'Temuan dengan kategori MAJOR')) {
                                $majorRn = $r;
                            }
                        }
                    }

                    if ($minorRn > 0 && $majorRn > 0 && $majorRn > $minorRn) {
                        $minorPlaceholders = [];
                        $majorPlaceholders = [];
                        $foundMinor = false;
                        $foundMajor = false;
                        foreach ($sheetData3->childNodes as $child) {
                            if ($child->nodeType !== XML_ELEMENT_NODE || $child->localName !== 'row') continue;
                            $r = (int)$child->getAttribute('r');
                            if ($r === $minorRn) { $foundMinor = true; continue; }
                            if ($r === $majorRn) { $foundMajor = true; continue; }
                            if ($foundMajor) {
                                $majorPlaceholders[] = $child;
                            } elseif ($foundMinor) {
                                $minorPlaceholders[] = $child;
                            }
                        }

                        foreach ($minorPlaceholders as $row) $sheetData3->removeChild($row);
                        foreach ($majorPlaceholders as $row) $sheetData3->removeChild($row);

                        $majorRowElement = null;
                        foreach ($sheetData3->childNodes as $child) {
                            if ($child->nodeType !== XML_ELEMENT_NODE || $child->localName !== 'row') continue;
                            if ((int)$child->getAttribute('r') === $majorRn) {
                                $majorRowElement = $child;
                                break;
                            }
                        }

                        if ($majorRowElement) {
                            $makeDataRow = function ($rn, $bText = null) use ($dom3, $ns3) {
                                $row = $dom3->createElementNS($ns3, 'row');
                                $row->setAttribute('r', (string)$rn);
                                $row->setAttribute('spans', '1:15');
                                $cell = $dom3->createElementNS($ns3, 'c');
                                $cell->setAttribute('r', 'B' . $rn);
                                $cell->setAttribute('s', '1');
                                if ($bText !== null) {
                                    $cell->setAttribute('t', 'inlineStr');
                                    $is = $dom3->createElementNS($ns3, 'is');
                                    $t = $dom3->createElementNS($ns3, 't');
                                    $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                                    $t->appendChild($dom3->createTextNode($bText));
                                    $is->appendChild($t);
                                    $cell->appendChild($is);
                                }
                                $row->appendChild($cell);
                                return $row;
                            };

                            $rn = $minorRn + 1;

                            foreach (($findingLines['minor'] ?? []) as $line) {
                                if (trim($line) === '') continue;
                                $sheetData3->insertBefore($makeDataRow($rn++, trim($line)), $majorRowElement);
                            }

                            $sheetData3->insertBefore($makeDataRow($rn++), $majorRowElement);

                            $majorRowElement->setAttribute('r', (string)$rn);
                            foreach ($xpath3->query('s:c', $majorRowElement) as $cell) {
                                $ref = $cell->getAttribute('r');
                                $cell->setAttribute('r', preg_replace('/\d+$/', (string)$rn, $ref));
                            }
                            $rn++;

                            foreach (($findingLines['mayor'] ?? []) as $line) {
                                if (trim($line) === '') continue;
                                $sheetData3->appendChild($makeDataRow($rn++, trim($line)));
                            }
                        }
                    }

                    // --- Paste avg/min dari min max hierarchy (sequential, tanpa match nama) ---
                    $allCats = Category::with('items.criteria')->get()->keyBy('id');

                    $aggregateFn = function(array $catIds) use ($geraiScores, $geraiBobots, $allCats) {
                        $pcts = [];
                        $byGerai = [];
                        foreach ($geraiScores as $itemId => $geraiScoreMap) {
                            foreach ($geraiScoreMap as $kode => $score) {
                                $byGerai[$kode][$itemId] = $score;
                            }
                        }
                        $geraiKodes = array_keys($byGerai);
                        foreach ($geraiKodes as $kode) {
                            $totalScore = 0;
                            $totalBobot = 0;
                            foreach ($catIds as $catId) {
                                $cat = $allCats->get($catId);
                                if (!$cat) continue;
                                foreach ($cat->items as $item) {
                                    $score = $geraiScores[$item->id][$kode] ?? null;
                                    if ($score !== null) {
                                        $totalScore += $score;
                                        $totalBobot += $geraiBobots[$item->id];
                                    }
                                }
                            }
                            if ($totalBobot > 0) {
                                $pcts[] = ($totalScore / $totalBobot) * 100;
                            }
                        }
                        return $pcts;
                    };

                    $itemAggregateFn = function($itemId) use ($geraiScores, $geraiBobots) {
                        $bobot = $geraiBobots[$itemId] ?? 0;
                        if ($bobot <= 0) return [];
                        $pcts = [];
                        foreach (($geraiScores[$itemId] ?? []) as $score) {
                            $pcts[] = ($score / $bobot) * 100;
                        }
                        return $pcts;
                    };

                    $sections = [
                        ['name' => 'Karyawan & Pimpinan Gerai', 'groups' => [
                            ['name' => 'Pelayanan', 'category_ids' => [2, 3, 4]],
                            ['name' => 'Penampilan & Tingkah Laku Karyawan', 'category_ids' => [6, 7, 8]],
                        ], 'category_ids' => [5, 9]],
                        ['name' => 'Tampilan Gerai', 'category_ids' => [10, 11, 12, 13, 14]],
                        ['name' => 'Produk Operasional', 'category_ids' => [15, 16, 17, 19, 18, 20]],
                    ];

                    $hierarchyValues = []; // [['avg'=>..., 'min'=>...], ...]

                    $allCategoryIds = [];
                    foreach ($sections as $section) {
                        $secCatIds = $section['category_ids'] ?? [];
                        foreach ($section['groups'] ?? [] as $group) {
                            $secCatIds = array_merge($secCatIds, $group['category_ids']);
                        }
                        $allCategoryIds = array_merge($allCategoryIds, $secCatIds);

                        // Section aggregate
                        $pcts = $aggregateFn($secCatIds);
                        $hierarchyValues[] = [
                            'avg' => !empty($pcts) ? round(array_sum($pcts) / count($pcts)) : 0,
                            'min' => !empty($pcts) ? round(min($pcts)) : 0,
                        ];

                        foreach ($section['groups'] ?? [] as $group) {
                            // Group aggregate
                            $pcts = $aggregateFn($group['category_ids']);
                            $hierarchyValues[] = [
                                'avg' => !empty($pcts) ? round(array_sum($pcts) / count($pcts)) : 0,
                                'min' => !empty($pcts) ? round(min($pcts)) : 0,
                            ];

                            // Each category and its items
                            foreach ($group['category_ids'] as $catId) {
                                $pcts = $aggregateFn([$catId]);
                                $hierarchyValues[] = [
                                    'avg' => !empty($pcts) ? round(array_sum($pcts) / count($pcts)) : 0,
                                    'min' => !empty($pcts) ? round(min($pcts)) : 0,
                                ];
                                $cat = $allCats->get($catId);
                                if ($cat) {
                                    foreach ($cat->items as $item) {
                                        $ipcts = $itemAggregateFn($item->id);
                                        $hierarchyValues[] = [
                                            'avg' => !empty($ipcts) ? round(array_sum($ipcts) / count($ipcts)) : 0,
                                            'min' => !empty($ipcts) ? round(min($ipcts)) : 0,
                                        ];
                                    }
                                }
                            }
                        }

                        // Ungrouped categories and their items
                        foreach ($section['category_ids'] ?? [] as $catId) {
                            $pcts = $aggregateFn([$catId]);
                            $hierarchyValues[] = [
                                'avg' => !empty($pcts) ? round(array_sum($pcts) / count($pcts)) : 0,
                                'min' => !empty($pcts) ? round(min($pcts)) : 0,
                            ];
                            $cat = $allCats->get($catId);
                            if ($cat) {
                                foreach ($cat->items as $item) {
                                    $ipcts = $itemAggregateFn($item->id);
                                    $hierarchyValues[] = [
                                        'avg' => !empty($ipcts) ? round(array_sum($ipcts) / count($ipcts)) : 0,
                                        'min' => !empty($ipcts) ? round(min($ipcts)) : 0,
                                    ];
                                }
                            }
                        }
                    }

                    // Total
                    $pcts = $aggregateFn($allCategoryIds);
                    $hierarchyValues[] = [
                        'avg' => !empty($pcts) ? round(array_sum($pcts) / count($pcts)) : 0,
                        'min' => !empty($pcts) ? round(min($pcts)) : 0,
                    ];

                    // Paste sequential ke sheet3 L9 dan M9
                    $setPctCell = function($targetDom, $targetRow, $targetCol, $pctValue) use ($ns) {
                        $ref = $targetCol . $targetRow;
                        $xpath = new DOMXPath($targetDom);
                        $xpath->registerNamespace('s', $ns);
                        $cells = $xpath->query("//s:c[@r='$ref']");
                        if ($cells->length > 0) {
                            $cell = $cells->item(0);
                            foreach (['v', 'is', 'f'] as $tag) {
                                $existing = $cell->getElementsByTagNameNS($ns, $tag)->item(0);
                                if ($existing) $cell->removeChild($existing);
                            }
                        } else {
                            $rowEls = $xpath->query("//s:row[@r='$targetRow']");
                            if ($rowEls->length === 0) return;
                            $rowEl = $rowEls->item(0);
                            $cell = $targetDom->createElementNS($ns, 'c');
                            $cell->setAttribute('r', $ref);
                            $rowEl->appendChild($cell);
                        }
                        $cell->removeAttribute('t');
                        $vEl = $targetDom->createElementNS($ns, 'v');
                        $vEl->textContent = (string) $pctValue;
                        $cell->appendChild($vEl);
                    };

                    $pasteRow = 9;
                    foreach ($hierarchyValues as $hv) {
                        if ($pasteRow > 115) break;
                        $setPctCell($dom3, $pasteRow, 'L', $hv['avg']);
                        $setPctCell($dom3, $pasteRow, 'M', $hv['min']);
                        $pasteRow++;
                    }

                    // --- Sheet3: Penjelasan Formulir 3 ---
                    $penjelasanIsi3 = $finding ? ($finding->penjelasan_isi_3 ?? []) : [];
                    $penjelasanIsi3 = array_filter($penjelasanIsi3, fn($v) => trim($v) !== '');

                    if (!empty($penjelasanIsi3)) {
                        $zeroScoreItemIds = [];
                        foreach ($categories as $zcat) {
                            foreach ($zcat->items as $zitem) {
                                if (!$zitem->bobot || $zitem->criteria->count() <= 1) continue;
                                $zr = $results->get($zitem->id);
                                if (!$zr || !$zr->criterion_id) continue;
                                $zidx = $zitem->criteria->search(fn($c) => $c->id === $zr->criterion_id);
                                if ($zidx === $zitem->criteria->count() - 1) {
                                    $zeroScoreItemIds[] = $zitem->id;
                                }
                            }
                        }
                        $penjelasanIsi3 = array_filter($penjelasanIsi3, fn($k) => in_array((int)$k, $zeroScoreItemIds), ARRAY_FILTER_USE_KEY);
                    }

                    if (!empty($penjelasanIsi3)) {
                        $ssContent3b = $zip->getFromName('xl/sharedStrings.xml');
                        $ssTextByIndex3 = [];
                        if ($ssContent3b !== false) {
                            $ssDom3b = new DOMDocument;
                            $ssDom3b->loadXML($ssContent3b);
                            foreach ($ssDom3b->getElementsByTagName('si') as $idx => $si) {
                                $t = $si->getElementsByTagName('t')->item(0);
                                $ssTextByIndex3[$idx] = $t ? $t->textContent : '';
                            }
                        }

                        // Find PENJELASAN row
                        $penjelasanRn3 = 0;
                        $penjelasanRow3 = null;
                        foreach ($xpath3->query('//s:sheetData/s:row') as $row) {
                            $r = (int)$row->getAttribute('r');
                            foreach ($xpath3->query('s:c', $row) as $cell) {
                                $text = '';
                                $type = $cell->getAttribute('t');
                                if ($type === 's') {
                                    $v = $cell->getElementsByTagName('v')->item(0);
                                    if ($v) { $idx = (int)$v->textContent; $text = $ssTextByIndex3[$idx] ?? ''; }
                                } elseif ($type === 'inlineStr') {
                                    $t = $cell->getElementsByTagName('t')->item(0);
                                    $text = $t ? $t->textContent : '';
                                }
                                if (str_contains($text, 'PENJELASAN')) {
                                    $penjelasanRn3 = $r;
                                    $penjelasanRow3 = $row;
                                    break 2;
                                }
                            }
                        }

                        if (!$penjelasanRow3) {
                            // Create PENJELASAN row at end of sheet data
                            $lastRn = 0;
                            foreach ($xpath3->query('//s:sheetData/s:row') as $row) {
                                $rr = (int)$row->getAttribute('r');
                                if ($rr > $lastRn) $lastRn = $rr;
                            }
                            $penjelasanRn3 = $lastRn + 1;
                            $penjelasanRow3 = $dom3->createElementNS($ns3, 'row');
                            $penjelasanRow3->setAttribute('r', (string)$penjelasanRn3);
                            $penjelasanRow3->setAttribute('spans', '1:15');
                            $cell = $dom3->createElementNS($ns3, 'c');
                            $cell->setAttribute('r', 'A' . $penjelasanRn3);
                            $cell->setAttribute('t', 'inlineStr');
                            $cell->setAttribute('s', '12');
                            $is = $dom3->createElementNS($ns3, 'is');
                            $t = $dom3->createElementNS($ns3, 't');
                            $t->appendChild($dom3->createTextNode('PENJELASAN:'));
                            $is->appendChild($t);
                            $cell->appendChild($is);
                            $penjelasanRow3->appendChild($cell);
                            $sheetData3->appendChild($penjelasanRow3);
                        }

                        // Insert Formulir 3 data below PENJELASAN row
                        $rn = $penjelasanRn3 + 1;
                        $newRows = [];
                        $i = 1;
                        foreach ($penjelasanIsi3 as $teks) {
                            $row = $dom3->createElementNS($ns3, 'row');
                            $row->setAttribute('r', (string)$rn);
                            $row->setAttribute('spans', '1:15');
                            $cell = $dom3->createElementNS($ns3, 'c');
                            $cell->setAttribute('r', 'B' . $rn);
                            $cell->setAttribute('t', 'inlineStr');
                            $cell->setAttribute('s', '1');
                            $is = $dom3->createElementNS($ns3, 'is');
                            $t = $dom3->createElementNS($ns3, 't');
                            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                            $t->appendChild($dom3->createTextNode(($i++) . '. ' . trim($teks)));
                            $is->appendChild($t);
                            $cell->appendChild($is);
                            $row->appendChild($cell);
                            $newRows[] = $row;
                            $rn++;
                        }
                        $ref = $penjelasanRow3->nextSibling;
                        foreach ($newRows as $row) {
                            $sheetData3->insertBefore($row, $ref);
                        }

                        // Renumber rows after section
                        $allRows = [];
                        foreach ($sheetData3->childNodes as $child) {
                            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'row') {
                                $allRows[] = $child;
                            }
                        }
                        $pastSection = false;
                        $inserted = count($newRows);
                        $skipped = 0;
                        foreach ($allRows as $row) {
                            $rAttr = (int)$row->getAttribute('r');
                            if (!$pastSection) {
                                if ($rAttr === $penjelasanRn3) $pastSection = true;
                                continue;
                            }
                            if ($skipped < $inserted) { $skipped++; continue; }
                            $row->setAttribute('r', (string)$rn);
                            foreach ($xpath3->query('s:c', $row) as $cell) {
                                $ref = $cell->getAttribute('r');
                                $cell->setAttribute('r', preg_replace('/\d+$/', (string)$rn, $ref));
                            }
                            $rn++;
                        }
                    }

                    $zip->addFromString('xl/worksheets/sheet3.xml', $dom3->saveXML());
                    } // end else (not pra-monitoring)
                }
            }
        }

        // --- Fill datachart sheet (sheet4) with period data ---
        $sheet4Content = $zip->getFromName('xl/worksheets/sheet4.xml');
        if ($sheet4Content !== false) {
            $dom4 = new DOMDocument;
            $dom4->loadXML($sheet4Content);
            $xpath4 = new DOMXPath($dom4);
            $ns4 = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $xpath4->registerNamespace('s', $ns4);

            // Get periods for this gerai ascending (oldest first)
            $chartPeriodLabels = MonitoringReport::where('gerai_id', $report->gerai_id)
                ->whereNotNull('submit_at')
                ->selectRaw('periode_label, MAX(checkin_at) as last_checkin')
                ->groupBy('periode_label')
                ->orderByRaw('MAX(checkin_at) asc')
                ->pluck('periode_label')
                ->values();

            // Take the most recent 9 periods (reversed to fill B-J left to right)
            $chartPeriodLabels = $chartPeriodLabels->slice(-9, 9)->values();

            // Fetch all reports for those periods (latest checkin per period)
            $chartAllReports = MonitoringReport::where('gerai_id', $report->gerai_id)
                ->whereNotNull('submit_at')
                ->whereIn('periode_label', $chartPeriodLabels)
                ->orderBy('checkin_at')
                ->get()
                ->groupBy('periode_label')
                ->map(fn($group) => $group->last());

            $columns = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
            $filledCols = []; // track columns that got data

            foreach ($chartPeriodLabels as $colIdx => $periodLabel) {
                $col = $columns[$colIdx];
                $pReport = $chartAllReports->get($periodLabel);
                if (!$pReport) continue;
                $filledCols[] = $col;

                // Get rounded score
                $periodScore = 0;
                if (is_numeric($pReport->nilai)) {
                    $periodScore = round((float) $pReport->nilai);
                }

                // Date string for row 1 (dd-mmm-yy format)
                $dateStr = $pReport->checkin_at->setTimezone($tz)->format('d-M-y');

                foreach ([1 => $dateStr, 2 => (string) $periodScore] as $rowNum => $cellValue) {
                    $ref = $col . $rowNum;
                    $cells = $xpath4->query("//s:c[@r='$ref']");
                    if ($cells->length === 0) continue;
                    $cell = $cells->item(0);

                    // Remove existing f, v, is elements
                    foreach (['v', 'is', 'f'] as $tag) {
                        $existing = $cell->getElementsByTagNameNS($ns4, $tag)->item(0);
                        if ($existing) $cell->removeChild($existing);
                    }

                    if ($rowNum === 1) {
                        // Date as inline string
                        $cell->setAttribute('t', 'inlineStr');
                        $is = $dom4->createElementNS($ns4, 'is');
                        $t = $dom4->createElementNS($ns4, 't');
                        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                        $t->textContent = $cellValue;
                        $is->appendChild($t);
                        $cell->appendChild($is);
                    } else {
                        // Score as numeric value
                        $cell->removeAttribute('t');
                        $v = $dom4->createElementNS($ns4, 'v');
                        $v->textContent = $cellValue;
                        $cell->appendChild($v);
                    }
                }
            }

            // Set row 3 (RERATA KINERJA) to 975 for filled columns only
            foreach ($filledCols as $col) {
                $ref = $col . '3';
                $cells = $xpath4->query("//s:c[@r='$ref']");
                if ($cells->length === 0) continue;
                $cell = $cells->item(0);
                foreach (['v', 'is', 'f'] as $tag) {
                    $existing = $cell->getElementsByTagNameNS($ns4, $tag)->item(0);
                    if ($existing) $cell->removeChild($existing);
                }
                $cell->removeAttribute('t');
                $v = $dom4->createElementNS($ns4, 'v');
                $v->textContent = '975';
                $cell->appendChild($v);
            }

            $filledCols = $this->appendChartColumn($dom4, $xpath4, $filledCols, $report, $totalScore, $prevTotalScore, $tz, $columns, $ns4);

            // Delete unused columns (rows 1-3) after last filled column
            $lastFilledIdx = array_search(end($filledCols), $columns, true);
            if ($lastFilledIdx !== false && $lastFilledIdx < 8) {
                $delCols = array_slice($columns, $lastFilledIdx + 1);
                foreach ($delCols as $delCol) {
                    foreach ([1, 2, 3] as $delRow) {
                        $ref = $delCol . $delRow;
                        $cells = $xpath4->query("//s:c[@r='$ref']");
                        for ($i = $cells->length - 1; $i >= 0; $i--) {
                            $cells->item($i)->parentNode->removeChild($cells->item($i));
                        }
                    }
                }

                // Update chart1.xml range references to match
                $chartLastCol = end($filledCols);
                $chartContent = $zip->getFromName('xl/charts/chart1.xml');
                if ($chartContent !== false) {
                    $chartContent = str_replace(
                        ['datachart!$B$1:$J$1', 'datachart!$B$2:$J$2', 'datachart!$B$3:$J$3'],
                        ["datachart!\$B\$1:\${$chartLastCol}\$1", "datachart!\$B\$2:\${$chartLastCol}\$2", "datachart!\$B\$3:\${$chartLastCol}\$3"],
                        $chartContent
                    );
                    $zip->addFromString('xl/charts/chart1.xml', $chartContent);
                }
            }

            $zip->addFromString('xl/worksheets/sheet4.xml', $dom4->saveXML());
        }

        // Remove calcChain to prevent "Removed Records: Formula" repair warning
        if ($zip->locateName('xl/calcChain.xml') !== false) {
            $zip->deleteName('xl/calcChain.xml');
        }

        $zip->close();

        // Post-process peringatan awal: merge B:O + wrap text + auto-width
        $pyScript = base_path('scripts/format_pa_rows.py');
        exec('python ' . escapeshellarg($pyScript) . ' ' . escapeshellarg($outPath) . ' 2>&1', $pyOut, $pyErr);
        if ($pyErr !== 0 || !empty($pyOut)) {
            \Log::info('format_pa_rows', ['output' => $pyOut, 'exit' => $pyErr]);
        }

        $this->postProcessExcel($outPath);

        // Kill orphaned Excel processes left by xlwings (non-blocking)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            popen('taskkill /F /IM EXCEL.EXE /FI "USERNAME eq ' . get_current_user() . '" >NUL 2>&1', 'r');
        }

        if ($outputDir) {
            return $outPath;
        }
        return response()->download($outPath)->deleteFileAfterSend(true);
    }

    public static function uploadTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'template' => 'required|file|mimes:xlsx',
            'type' => 'required|in:monitoring,pra-monitoring,re-monitoring',
        ]);

        $filename = 'excel-template-' . $request->type . '.xlsx';

        $label = match ($request->type) {
            'pra-monitoring' => 'Pra-Monitoring',
            're-monitoring' => 'Re-Monitoring',
            default => 'Monitoring',
        };

        try {
            $request->file('template')->storeAs('', $filename);
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal upload template {$label}: file mungkin sedang dibuka di program lain. Tutup file tersebut lalu coba lagi.");
        }

        return back()->with('success', "Template Excel {$label} berhasil diupload.");
    }

    public static function deleteTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate(['type' => 'required|in:monitoring,pra-monitoring,re-monitoring']);

        $filename = 'excel-template-' . $request->type . '.xlsx';

        $label = match ($request->type) {
            'pra-monitoring' => 'Pra-Monitoring',
            're-monitoring' => 'Re-Monitoring',
            default => 'Monitoring',
        };

        if (!Storage::exists($filename)) {
            return back()->with('error', "Template {$label} tidak ditemukan.");
        }

        if (!Storage::delete($filename)) {
            return back()->with('error', "Gagal menghapus template {$label}: file mungkin sedang dibuka di program lain. Tutup file tersebut lalu coba lagi.");
        }

        return back()->with('success', "Template Excel {$label} berhasil dihapus.");
    }

    public static function uploadTemplateEvaluasi(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'template' => 'required|file|mimes:xlsx',
        ]);

        try {
            $request->file('template')->storeAs('', 'excel-template-evaluasi.xlsx');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal upload template Evaluasi: file mungkin sedang dibuka di program lain. Tutup file tersebut lalu coba lagi.');
        }

        return back()->with('success', 'Template Evaluasi berhasil diupload.');
    }

    public static function deleteTemplateEvaluasi(\Illuminate\Http\Request $request)
    {
        if (!Storage::exists('excel-template-evaluasi.xlsx')) {
            return back()->with('error', 'Template Evaluasi tidak ditemukan.');
        }

        if (!Storage::delete('excel-template-evaluasi.xlsx')) {
            return back()->with('error', 'Gagal menghapus template Evaluasi: file mungkin sedang dibuka di program lain. Tutup file tersebut lalu coba lagi.');
        }

        return back()->with('success', 'Template Evaluasi berhasil dihapus.');
    }

    public static function downloadExampleTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan');

        // header
        $sheet->setCellValue('A1', 'LAPORAN MONITORING');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:E1');

        $sheet->setCellValue('A3', 'Nama Gerai');
        $sheet->setCellValue('B3', '{nama_gerai}');
        $sheet->setCellValue('D3', 'Kode Gerai');
        $sheet->setCellValue('E3', '{kode_gerai}');

        $sheet->setCellValue('A4', 'Tanggal');
        $sheet->setCellValue('B4', '{tanggal}');
        $sheet->setCellValue('D4', 'Tanggal Lengkap');
        $sheet->setCellValue('E4', '{tanggal_lengkap}');

        $sheet->setCellValue('A5', 'Petugas');
        $sheet->setCellValue('B5', '{petugas}');

        $sheet->setCellValue('A7', 'Checkin');
        $sheet->setCellValue('B7', '{checkin}');
        $sheet->setCellValue('D7', 'Submit');
        $sheet->setCellValue('E7', '{submit}');

        $sheet->setCellValue('A8', 'Lokasi');
        $sheet->setCellValue('B8', '{lokasi}');
        $sheet->setCellValue('D8', 'Periode');
        $sheet->setCellValue('E8', '{periode}');

        $sheet->setCellValue('A10', 'Total Nilai');
        $sheet->setCellValue('B10', '{total_score}');

        // finding
        $sheet->setCellValue('A12', 'Minor');
        $sheet->setCellValue('B12', '{minor}');
        $sheet->setCellValue('D12', 'Mayor');
        $sheet->setCellValue('E12', '{mayor}');
        $sheet->setCellValue('A13', 'Peringatan Awal');
        $sheet->setCellValue('B13', '{peringatan_awal}');

        // item table header
        $sheet->setCellValue('A15', 'No');
        $sheet->setCellValue('B15', 'Kategori');
        $sheet->setCellValue('C15', 'Item');
        $sheet->setCellValue('D15', 'Nilai');
        $sheet->setCellValue('E15', 'Bobot');
        $sheet->setCellValue('F15', 'Catatan');
        $sheet->getStyle('A15:F15')->getFont()->setBold(true);

        // item template row
        $sheet->setCellValue('A16', 1);
        $sheet->setCellValue('B16', '{item_category}');
        $sheet->setCellValue('C16', '{item_name}');
        $sheet->setCellValue('D16', '{item_value}');
        $sheet->setCellValue('E16', '{item_score}');
        $sheet->setCellValue('F16', '{item_notes}');

        // additional sheets
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Data Tambahan');
        $sheet2->setCellValue('A1', 'Franchisee');
        $sheet2->setCellValue('B1', '{franchisee}');
        $sheet2->setCellValue('A2', 'Tipe');
        $sheet2->setCellValue('B2', '{type}');

        // sheet 3: contoh per-item & per-sentence
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Contoh Per-Item');
        $sheet3->setCellValue('A1', 'CONTOH FORMAT PER-ITEM & PER-SENTENCE');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet3->mergeCells('A1:C1');

        $sheet3->setCellValue('A3', 'Minor:');
        $sheet3->setCellValue('B3', '{minor_1}');
        $sheet3->setCellValue('C3', '(minor baris 1)');
        $sheet3->setCellValue('B4', '{minor_2}');
        $sheet3->setCellValue('C4', '(minor baris 2)');

        $sheet3->setCellValue('A6', 'Mayor:');
        $sheet3->setCellValue('B6', '{mayor_1}');
        $sheet3->setCellValue('C6', '(mayor baris 1)');

        $sheet3->setCellValue('A8', 'Peringatan Awal:');
        $sheet3->setCellValue('B8', '{peringatan_awal_1}');
        $sheet3->setCellValue('C8', '(peringatan awal baris 1)');
        $sheet3->setCellValue('B9', '{peringatan_awal_2}');
        $sheet3->setCellValue('C9', '(peringatan awal baris 2)');

        $sheet3->setCellValue('A11', 'Item Checklist (per-item):');
        $sheet3->getStyle('A11')->getFont()->setBold(true);
        $sheet3->setCellValue('A12', 'Score');
        $sheet3->setCellValue('B12', 'Nilai');
        $sheet3->setCellValue('C12', 'Catatan');
        $sheet3->getStyle('A12:C12')->getFont()->setBold(true);

        $sheet3->setCellValue('A13', '{item_score:Kebersihan Lantai}');
        $sheet3->setCellValue('B13', '{item_value:Kebersihan Lantai}');
        $sheet3->setCellValue('C13', '{item_notes:Kebersihan Lantai}');

        $sheet3->setCellValue('A14', '{item_score:Rapikan Meja}');
        $sheet3->setCellValue('B14', '{item_value:Rapikan Meja}');
        $sheet3->setCellValue('C14', '{item_notes:Rapikan Meja}');

        foreach (range('A', 'C') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // style
        foreach (range(1, 6) as $col) {
            $sheet->getColumnDimension(chr(64 + $col))->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="contoh-template-excel.xlsx"',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        session()->forget('assessment_snapshot_' . $report->id);

        $report->results()->delete();
        $report->delete();

        $redirect = $request->input('_from') === 'list'
            ? '/report/monitoring'
            : "/{$this->prefix()}";

        return redirect($redirect)->with('success', 'Laporan berhasil dihapus.');
    }

    protected function pendingReport()
    {
        return $this->modelClass()::where('user_id', Auth::id())
            ->whereNotNull('checkin_at')
            ->whereNull('submit_at')
            ->first();
    }

    protected function authorizeReport($report): void
    {
        if ($report->user_id !== Auth::id() && Auth::user()?->role !== 'admin') {
            abort(403, 'Anda tidak berhak mengakses laporan ini.');
        }
    }

    protected function recalculateRankings(string $periodeLabel): void
    {
        $allPeriodLabels = \App\Models\SemesterPeriod::orderByDesc('year')->orderByDesc('start_month')
            ->get()
            ->map(fn($p) => $p->label)
            ->values()
            ->toArray();

        $idx = array_search($periodeLabel, $allPeriodLabels);
        if ($idx === false) return;

        $periodKeys = array_values(array_filter([
            $allPeriodLabels[$idx] ?? null,
            $allPeriodLabels[$idx + 1] ?? null,
            $allPeriodLabels[$idx + 2] ?? null,
        ]));

        $selectedKey = $periodKeys[0] ?? null;
        if (!$selectedKey) return;

        $geraiIds = MonitoringReport::whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->where('periode_label', $selectedKey)
            ->distinct()
            ->pluck('gerai_id');

        if ($geraiIds->isEmpty()) return;

        $allReports = MonitoringReport::whereIn('gerai_id', $geraiIds)
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->whereIn('periode_label', $periodKeys)
            ->get()
            ->groupBy('gerai_id');

        $openingDates = Gerai::whereIn('id', $geraiIds)->pluck('opening_at', 'id');

        $rows = [];
        foreach ($geraiIds as $gid) {
            $gr = $allReports->get($gid, collect())->keyBy('periode_label');
            $scores = [];
            foreach ($periodKeys as $k) {
                $rp = $k && isset($gr[$k]) ? $gr[$k] : null;
                $scores[] = $rp ? ($rp->nilai !== null ? round((float) $rp->nilai) : 0) : null;
            }
            $rows[] = [
                'gerai_id' => $gid,
                'opening_at' => isset($openingDates[$gid]) ? \Carbon\Carbon::parse($openingDates[$gid])->timestamp : 0,
                'p3' => $scores[0] ?? 0,
                'p2' => $scores[1] ?? 0,
                'p1' => $scores[2] ?? 0,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($b['p3'] !== $a['p3']) return $b['p3'] <=> $a['p3'];
            if ($b['p2'] !== $a['p2']) return $b['p2'] <=> $a['p2'];
            if ($b['p1'] !== $a['p1']) return $b['p1'] <=> $a['p1'];
            return $a['opening_at'] <=> $b['opening_at'];
        });

        $total = count($rows);
        $updates = [];
        foreach ($rows as $pos => $row) {
            $updates[] = [
                'gerai_id' => $row['gerai_id'],
                'periode_label' => $periodeLabel,
                'rank' => $pos + 1,
                'total' => $total,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        \App\Models\Ranking::where('periode_label', $periodeLabel)->delete();
        \App\Models\Ranking::insert($updates);
    }
}

