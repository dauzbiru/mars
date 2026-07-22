<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\MonitoringReport;
use App\Models\ReMonitoringReport;
use App\Models\Result;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReMonitoringController extends MonitoringController
{
    protected $type = 're-monitoring';

    protected function modelClass(): string
    {
        return ReMonitoringReport::class;
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
            '{type}'             => 'Re-Monitoring',
            '{nama_kota}'        => $report->gerai->nama_kota ?? '-',
            '{area}'             => $report->gerai->area ?? '-',
            '{opening_at}'       => $report->gerai->opening_at ? strtoupper($report->gerai->opening_at->locale('id')->isoFormat('D MMMM YYYY')) : '-',
        ];

        return $this->buildExcel($report, $headerReplacements, $outputDir);
    }

    public function checkinForm(Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending) {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan. Selesaikan dulu.');
        }

        return view('monitoring.checkin', compact('gerai') + ['prefix' => $this->prefix(), 'periods' => collect()]);
    }

    public function doCheckin(Request $request, Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending) {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan.');
        }

        $data = $request->validate([
            'location' => 'required|string|max:255',
            'checkin_at' => 'required|date',
        ]);

        $report = ReMonitoringReport::create([
            'gerai_id' => $gerai->id,
            'user_id' => Auth::id(),
            'location' => $data['location'],
            'checkin_at' => \Carbon\Carbon::parse($data['checkin_at'] . ' ' . now()->format('H:i:s')),
        ]);

        $categories = Category::whereNull('parent_id')->with('items.criteria')->get();
        foreach ($categories as $cat) {
            foreach ($cat->items as $item) {
                if ($item->criteria->isNotEmpty()) {
                    Result::create([
                        'item_id' => $item->id,
                        'user_id' => Auth::id(),
                        'reportable_type' => ReMonitoringReport::class,
                        'reportable_id' => $report->id,
                        'criterion_id' => $item->criteria->first()->id,
                    ]);
                }
            }
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment");
    }

    public function destroy(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        session()->forget('assessment_snapshot_' . $report->id);

        $report->results()->delete();
        $report->finding?->delete();
        $report->delete();

        $redirect = $request->input('_from') === 'list'
            ? '/report/re-monitoring'
            : "/{$this->prefix()}";

        return redirect($redirect)->with('success', 'Laporan berhasil dihapus.');
    }

    protected function getTemplateName(): string
    {
        return 'monitoring';
    }

    protected function getPreviousScore($report, float $totalScore): ?float
    {
        $prevReport = MonitoringReport::where('gerai_id', $report->gerai_id)
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->latest('checkin_at')
            ->first();
        if ($prevReport && is_numeric($prevReport->nilai)) {
            return (float) $prevReport->nilai;
        }
        return null;
    }

    protected function useExcelPdf(): bool
    {
        return true;
    }

    protected function appendChartColumn($dom4, $xpath4, array $filledCols, $report, float $totalScore, ?float $prevTotalScore, string $tz, array $columns, string $ns4): array
    {
        if (empty($filledCols)) return $filledCols;

        $reColIdx = array_search(end($filledCols), $columns, true);
        if ($reColIdx === false || $reColIdx >= 8) return $filledCols;

        $reCol = $columns[$reColIdx + 1];
        $reDate = $report->checkin_at->setTimezone($tz)->format('d-M-y');
        $reScore = is_numeric($report->nilai) ? round((float) $report->nilai) : round($totalScore);
        $rePrevScore = $prevTotalScore !== null ? round($prevTotalScore) : null;

        $reData = [1 => $reDate, 2 => (string) $reScore, 3 => '975'];
        if ($rePrevScore !== null) {
            $reData[4] = (string) $rePrevScore;
        }

        foreach ($reData as $rowNum => $cellValue) {
            $ref = $reCol . $rowNum;
            $cells = $xpath4->query("//s:c[@r='$ref']");
            if ($cells->length === 0) continue;
            $cell = $cells->item(0);
            foreach (['v', 'is', 'f'] as $tag) {
                $existing = $cell->getElementsByTagNameNS($ns4, $tag)->item(0);
                if ($existing) $cell->removeChild($existing);
            }
            if ($rowNum === 1) {
                $cell->setAttribute('t', 'inlineStr');
                $is = $dom4->createElementNS($ns4, 'is');
                $t = $dom4->createElementNS($ns4, 't');
                $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                $t->textContent = $cellValue;
                $is->appendChild($t);
                $cell->appendChild($is);
            } else {
                $cell->removeAttribute('t');
                $v = $dom4->createElementNS($ns4, 'v');
                $v->textContent = $cellValue;
                $cell->appendChild($v);
            }
        }
        $filledCols[] = $reCol;
        return $filledCols;
    }

    protected function getTargetPeriode($report): ?string
    {
        $allPeriods = MonitoringReport::where('gerai_id', $report->gerai_id)
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->selectRaw('periode_label, MAX(checkin_at) as last_checkin')
            ->groupBy('periode_label')
            ->orderByRaw('MAX(checkin_at) desc')
            ->pluck('periode_label');

        if ($allPeriods->count() >= 2) {
            return $allPeriods[1];
        } elseif ($allPeriods->count() === 1) {
            return $allPeriods[0];
        }
        return $report->periode_label;
    }
}

