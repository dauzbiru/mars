<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\Category;
use App\Models\Result;
use App\Models\MonitoringReport;
use App\Models\PenjelasanFormulir;
use App\Models\SemesterPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    private function getAllItems()
    {
        return \App\Models\Item::with('criteria')->get();
    }

    protected function getAllCategories()
    {
        return Category::whereNull('parent_id')
            ->with('items.criteria')
            ->orderBy('sort')
            ->get();
    }

    private function getScoreLoopResults($report, $categories, $results)
    {
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
                    $val = \App\Services\ScoreCalculator::calculateItemScore($item, $result);
                    $catScore += $val;
                    $totalScore += $val;
                }
            }
            $catScores[$cat->id] = ['score' => $catScore, 'max' => $catMax];
        }
        return [$totalScore, $catScores];
    }

    private function findReportOrFail($id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);
        return $report;
    }

    private function getResultsForReport($report)
    {
        return Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->get()
            ->keyBy('item_id');
    }

    protected function getTypeName(): string
    {
        return match($this->type) {
            'monitoring' => 'Monitoring',
            'pra-monitoring' => 'Pramonitoring',
            're-monitoring' => 'Remonitoring',
            default => ucfirst($this->type),
        };
    }

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

        $gerais = Gerai::active()
            ->when($this->type === 'pra-monitoring', fn($q) => $q
                ->where('opening_at', '<=', now()->subDays(20))
                ->where('opening_at', '>=', now()->subDays(40))
            )
            ->when($this->type === 'monitoring', fn($q) => $q
                ->where('opening_at', '<=', now()->subMonths(5)->subDays(15))
            )
            ->orderBy('kode_gerai')->get();

        $isAdmin = Auth::user()?->role === 'admin';
        if ($this->type === 'evaluasi') {
            $query = $this->modelClass()::whereNull('tanggal');
        } else {
            $query = $this->modelClass()::whereNotNull('checkin_at')->whereNull('submit_at');
        }
        if (!$isAdmin) {
            $query->where('user_id', '!=', Auth::id());
        }
        $pendingByOthers = $query->with('user')->get()->pluck('user.name', 'gerai_id')->toArray();

        return view('monitoring.select-gerai', compact('gerais', 'pendingByOthers') + ['prefix' => $this->prefix()]);
    }

    protected function checkinFormPeriods(Gerai $gerai)
    {
        return SemesterPeriod::where('year', now()->year)->orderBy('start_month')->get();
    }

    protected function checkinFormExistingPeriods(Gerai $gerai): array
    {
        return MonitoringReport::where('gerai_id', $gerai->id)
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
    }

    protected function checkinFormHasPairingCheck(): bool
    {
        return true;
    }

    public function checkinForm(Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending && $this->checkinFormHasPairingCheck() && request('pairing') !== '1') {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan. Selesaikan dulu.');
        }

        $periods = $this->checkinFormPeriods($gerai);
        $existingPeriods = $this->checkinFormExistingPeriods($gerai);

        return view('monitoring.checkin', compact('gerai', 'periods', 'existingPeriods') + ['prefix' => $this->prefix()]);
    }

    public function doCheckin(Request $request, Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending && $request->input('pairing') !== '1') {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan.');
        }

        $rules = array_merge([
            'location' => 'required|string|max:255',
            'checkin_at' => 'required|date',
        ], $this->doCheckinExtraValidation());

        $data = $request->validate($rules);

        $report = DB::transaction(function () use ($gerai, $data) {
            if ($this->shouldCheckDuplicate()) {
                $duplicate = $this->modelClass()::where('gerai_id', $gerai->id)
                    ->where('periode_label', $data['periode_label'])
                    ->whereNotNull('submit_at')
                    ->exists();

                if ($duplicate) {
                    return null;
                }
            }

            $createData = array_merge([
                'gerai_id' => $gerai->id,
                'user_id' => Auth::id(),
                'location' => $data['location'],
                'checkin_at' => \Carbon\Carbon::parse($data['checkin_at'] . ' ' . now()->format('H:i:s')),
            ], $this->doCheckinExtraData($data));

            $report = $this->modelClass()::create($createData);

            $this->createInitialResults($report);

            return $report;
        });

        if ($report === null) {
            return redirect("/{$this->prefix()}/checkin/{$gerai->id}")->with('warning', 'Nilai untuk gerai dan periode ini sudah ada. Hapus data yang ada terlebih dahulu jika ingin mengganti.');
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment");
    }

    public function assessment($id)
    {
        $report = $this->findReportOrFail($id);

        $categories = $this->getAllCategories();
        $results = $this->getResultsForReport($report);
        [$totalScore, $catScores] = $this->getScoreLoopResults($report, $categories, $results);

        $allItemsForValidation = $this->getAllItems();
        $incomplete = \App\Services\TemuanValidationService::validateCompleteness(
            $report, $results, $allItemsForValidation, $this->hasPenjelasanFormulir2()
        );

        $snapshot = null;
        if ($report->submit_at) {
            if ($report->editing_snapshot) {
                $snapshot = $report->editing_snapshot;
            } else {
                $snapshot = [
                    'results' => $results->map(fn($r) => ['item_id' => $r->item_id, 'criterion_id' => $r->criterion_id])->values()->toArray(),
                    'finding' => $report->only([
                        'major', 'minor', 'peringatan_awal', 'pengawas', 'rata_rata_aj',
                        'tds', 'mesin_ozon', 'note', 'kondisi_cat', 'kondisi_awning',
                        'kondisi_vinyl', 'kondisi_stiker_kaca', 'ttd_petugas', 'ttd_pimpinan',
                        'penjelasan_isi', 'penjelasan_isi_3',
                    ]),
                ];
                $report->lockForEditing($snapshot);
            }
        }

        $prefix = $this->prefix();
        return response()
            ->view('monitoring.assessment', compact('report', 'categories', 'results', 'totalScore', 'catScores', 'incomplete', 'snapshot') + ['prefix' => $prefix])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function cancelAssessment(Request $request, $id)
    {
        $report = $this->findReportOrFail($id);

        $snapshot = $report->editing_snapshot;

        if (!$snapshot) {
            return redirect("/{$this->prefix()}")->with('warning', 'Snapshot tidak ditemukan. Mungkin sudah kedaluwarsa.');
        }

        if ($snapshot['results'] !== null) {
            Result::where('reportable_type', get_class($report))
                ->where('reportable_id', $report->id)->delete();
            $insertData = [];
            foreach ($snapshot['results'] as $resultData) {
                $insertData[] = [
                    'reportable_type' => get_class($report),
                    'reportable_id' => $report->id,
                    'user_id' => Auth::id(),
                    'item_id' => $resultData['item_id'],
                    'criterion_id' => $resultData['criterion_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($insertData) {
                Result::insert($insertData);
            }
        }

        if (array_key_exists('finding', $snapshot)) {
            $findingData = $snapshot['finding'];
            if ($findingData) {
                $report->update($findingData);
            } else {
                $nullData = [
                    'major' => null, 'minor' => null, 'peringatan_awal' => null,
                    'ttd_petugas' => null, 'ttd_pimpinan' => null,
                    'penjelasan_isi' => null, 'penjelasan_isi_3' => null,
                    'pengawas' => null, 'rata_rata_aj' => null, 'tds' => null,
                    'mesin_ozon' => null, 'note' => null,
                    'kondisi_cat' => null, 'kondisi_awning' => null,
                    'kondisi_vinyl' => null, 'kondisi_stiker_kaca' => null,
                ];
                $report->update($nullData);
            }
        }

        $report->unlockEditing();

        $redirect = "/{$this->prefix()}/{$report->id}";

        return redirect($redirect)->with('success', 'Perubahan berhasil dibatalkan.');
    }

    public function assessmentForm($id, Category $category)
    {
        $report = $this->findReportOrFail($id);
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
        $report = $this->findReportOrFail($id);
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
        $report = $this->findReportOrFail($id);
        $results = $this->getResultsForReport($report);

        $groups = $this->penjelasanGroups();
        $groupLabels = [];

        $allGroupItems = \App\Models\Item::with('criteria')
            ->whereIn('id', collect($groups)->pluck('item_ids')->flatten())
            ->get()
            ->keyBy('id');

        foreach ($groups as $group) {
            $achieved = 0;
            $maxBobot = 0;
            foreach ($group['item_ids'] as $itemId) {
                $result = $results->get($itemId);
                if (!$result) continue;
                $item = $allGroupItems->get($itemId);
                if (!$item || !$item->bobot) continue;
                if ($item->criteria->count() <= 1) continue;
                $achieved += \App\Services\ScoreCalculator::calculateItemScore($item, $result);
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
        foreach ($this->getAllItems() as $item) {
            if (!$item->bobot || $item->criteria->count() <= 1) continue;
            $result = $results->get($item->id);
            if (!$result || !$result->criterion_id) continue;
            $criteria = $item->criteria;
            $idToIndex = array_flip($criteria->pluck('id')->toArray());
            $idx = $idToIndex[$result->criterion_id] ?? false;
            if ($idx === $criteria->count() - 1) {
                $zeroScoreItems[] = ['id' => $item->id, 'name' => $item->name];
            }
        }

        return view('monitoring.temuan', compact('report', 'groupLabels', 'penjelasanItems', 'penjelasanItems3', 'zeroScoreItems') + ['prefix' => $this->prefix()]);
    }

    protected function penjelasanGroups(): array
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
        $report = $this->findReportOrFail($id);

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

        $report->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment")->with('success', 'Temuan monitoring berhasil disimpan.');
    }

    public function submit(Request $request, $id)
    {
        $report = $this->findReportOrFail($id);

        $savedResults = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->where('user_id', $report->user_id)
            ->whereNotNull('criterion_id')
            ->pluck('item_id')
            ->toArray();

        $categories = $this->getAllCategories();
        $allItemIds = $categories->pluck('items.*.id')->flatten()->toArray();

        $unfilled = array_diff($allItemIds, $savedResults);

        if (!empty($unfilled)) {
            $unfilledNames = [];
            foreach ($categories as $cat) {
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
        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->whereNotNull('criterion_id')
            ->get()
            ->keyBy('item_id');
        $allItemsForValidation = $this->getAllItems();
        $incomplete = \App\Services\TemuanValidationService::validateCompleteness(
            $report, $results, $allItemsForValidation, $this->hasPenjelasanFormulir2()
        );

        if (!empty($incomplete)) {
            return back()->with('warning', 'Lengkapi bagian berikut sebelum submit:')
                ->with('incomplete', $incomplete);
        }

        $report->load('results.item.criteria');
        $total = \App\Services\ScoreCalculator::calculateFromResults($report->results);

        $grade = $this->modelClass()::gradeFromScore($total);

        $updateData = ['nilai' => $total, 'grade' => $grade];
        if (!$report->submit_at) {
            $updateData['submit_at'] = now();
        }
        $report->update($updateData);

        if ($report->periode_label) {
            $this->recalculateRankings($report->periode_label);
        }

        $report->unlockEditing();

        return redirect("/{$this->prefix()}/{$report->id}")->with('success', 'Laporan berhasil disubmit.');
    }

    public function show($id)
    {
        $report = $this->findReportOrFail($id);
        $categories = $this->getAllCategories();
        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->with('criterion')
            ->get()
            ->keyBy('item_id');

        [$totalScore] = $this->getScoreLoopResults($report, $categories, $results);

        $filteredCategories = $categories->map(function ($cat) use ($results) {
            $cat->items = $cat->items->filter(function ($item) use ($results) {
                $r = $results->get($item->id);
                if (!$r || !$r->criterion_id) return false;
                $firstCriterionId = $item->criteria->first()?->id;
                return $r->criterion_id !== $firstCriterionId;
            })->values();
            return $cat;
        })->filter(fn($cat) => $cat->items->isNotEmpty())->values();

        $allGerais = \App\Models\Gerai::active()->get(['id', 'kode_gerai', 'nama_gerai', 'franchisee', 'no_telepon']);
        $allPgs = \App\Models\Pg::orderBy('nama_pg')->get(['id', 'nama_pg', 'kota', 'no_telepon']);

        return view('monitoring.show', compact('report', 'filteredCategories', 'results', 'totalScore') + [
            'prefix' => $this->prefix(),
            'allGerais' => $allGerais,
            'allPgs' => $allPgs,
        ]);
    }

    public function pdf($id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->with('gerai', 'user')->findOrFail($id);
        $this->authorizeReport($report);

        $revisi = request()->boolean('revisi');
        if (request()->boolean('excel')) {
            $typeName = $this->getTypeName();
            $periode = $report->periode_label ?? $report->checkin_at?->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('MMMM YYYY') ?? '';
            $filename = "{$typeName} - {$report->gerai->kode_gerai} - {$periode}";
        } else {
            $typeDisplay = $this->getTypeName();
            $dateFmt = $report->checkin_at->setTimezone('Asia/Jakarta')->format('d-m-y_H.i');
            $filename = ($revisi ? 'Revisi_' : '') . "Laporan_Sementara_{$typeDisplay}_{$report->gerai->kode_gerai}_{$dateFmt}";
        }

        if (request()->boolean('excel')) {
            $tempDir = storage_path('app/temp-pdf');
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

            $excelPath = $this->excel($report->id, $tempDir);
            if ($excelPath && file_exists($excelPath)) {
                $pdfPath = $tempDir . '/' . $filename . '.pdf';
                $pyScript = base_path('scripts/xlwings-to-pdf.py');
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

    protected function pdfDompdf($report, $revisi, $filename, $savePath = null)
    {
        $categories = $this->getAllCategories();

        $results = Result::where('reportable_type', get_class($report))
            ->where('reportable_id', $report->id)
            ->with('criterion')
            ->get()
            ->keyBy('item_id');

        [$totalScore] = $this->getScoreLoopResults($report, $categories, $results);

        // resize TTD images to base64 to reduce PDF size
        $ttdImages = [];
        foreach (['ttd_petugas', 'ttd_pimpinan'] as $field) {
            $ttdImages[$field] = null;
            if (!empty($report->$field)) {
                $path = storage_path('app/public/' . $report->$field);
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

        $fontLoaded = $this->registerArimoFont();

        $pdf = Pdf::loadView('monitoring.pdf', compact('report', 'categories', 'results', 'totalScore', 'fontLoaded', 'ttdImages', 'revisi') + ['prefix' => $this->prefix()]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['dpi' => 72, 'defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
        if ($savePath) {
            $pdf->save($savePath);
            return $savePath;
        }
        return $pdf->download($filename . '.pdf');
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

    protected function reportListRoute(): string
    {
        return '/report/' . $this->prefix();
    }

    public function destroy(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        session()->forget('assessment_snapshot_' . $report->id);

        $report->tampilanGeraiBlocks()->get()->each(function ($block) {
            $block->delete();
        });

        $report->results()->delete();
        $report->delete();

        $redirect = $request->input('_from') === 'list'
            ? $this->reportListRoute()
            : "/{$this->prefix()}";

        return redirect($redirect)->with('success', 'Laporan berhasil dihapus.');
    }

    protected function createInitialResults($report): void
    {
        $data = [];
        $reportType = get_class($report);
        $userId = $report->user_id;
        foreach ($this->getAllCategories() as $cat) {
            foreach ($cat->items as $item) {
                if ($item->criteria->isNotEmpty()) {
                    $data[] = [
                        'item_id' => $item->id,
                        'user_id' => $userId,
                        'reportable_type' => $reportType,
                        'reportable_id' => $report->id,
                        'criterion_id' => $item->criteria->first()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        if ($data) {
            Result::insert($data);
        }
    }

    protected function doCheckinExtraValidation(): array
    {
        return ['periode_label' => 'required|string|max:100'];
    }

    protected function doCheckinExtraData(array $validated): array
    {
        return [
            'periode_label' => $validated['periode_label'],
            'is_pairing' => request()->input('pairing') === '1',
        ];
    }

    protected function shouldCheckDuplicate(): bool
    {
        return true;
    }

    protected function pendingReport()
    {
        if (Auth::user()?->role === 'admin') {
            return null;
        }
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

