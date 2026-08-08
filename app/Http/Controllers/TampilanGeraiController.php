<?php

namespace App\Http\Controllers;

use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\ReMonitoringReport;
use App\Models\TampilanGeraiBlock;
use App\Models\TampilanGeraiPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TampilanGeraiController extends Controller
{
    protected function modelMap(): array
    {
        return [
            'monitoring'    => MonitoringReport::class,
            'pra-monitoring' => PraMonitoringReport::class,
            're-monitoring'  => ReMonitoringReport::class,
        ];
    }

    protected function resolveReport(string $type, int $id)
    {
        $class = $this->modelMap()[$type] ?? abort(404);
        $report = $class::withoutGlobalScope('no_pairing')->findOrFail($id);
        if ($report->user_id !== Auth::id() && !Auth::user()?->isAdmin()) {
            abort(403, 'Anda tidak berhak mengakses laporan ini.');
        }
        return $report;
    }

    public function index()
    {
        $blocks = TampilanGeraiBlock::with(['reportable.gerai', 'reportable.user'])
            ->where(function ($query) {
                $query->whereNotNull('keterangan')->where('keterangan', '!=', '')
                    ->orWhereHas('photos');
            })
            ->whereHasMorph('reportable', [
                MonitoringReport::class,
                PraMonitoringReport::class,
                ReMonitoringReport::class,
            ], function ($query) {
                $query->whereNotNull('submit_at');
            })
            ->when(!Auth::user()?->isAdmin(), function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->get();

        $groups = $blocks->groupBy(fn($b) => get_class($b->reportable) . '|' . $b->reportable_id);

        $groups = $groups->sortByDesc(function ($group) {
            return $group->first()->reportable->submit_at?->timestamp ?? 0;
        });

        return view('tampilan-gerai.index', compact('groups'));
    }

    public function list(string $type, int $reportId)
    {
        $report = $this->resolveReport($type, $reportId);

        $report->tampilanGeraiBlocks()
            ->where(function ($query) {
                $query->whereNull('keterangan')->orWhere('keterangan', '');
            })
            ->doesntHave('photos')
            ->get()
            ->each->delete();

        $blocks = $report->tampilanGeraiBlocks()
            ->with('photos')
            ->get()
            ->map(function ($block) {
                return [
                    'id' => $block->id,
                    'keterangan' => $block->keterangan,
                    'photos' => $block->photos->map(fn($p) => [
                        'id' => $p->id,
                        'url' => $p->url(),
                    ]),
                ];
            });

        return response()->json(['blocks' => $blocks]);
    }

    public function detail(string $type, int $reportId)
    {
        $report = $this->resolveReport($type, $reportId);

        $blocks = $report->tampilanGeraiBlocks()
            ->with('photos')
            ->where(function ($query) {
                $query->whereNotNull('keterangan')->where('keterangan', '!=', '')
                    ->orWhereHas('photos');
            })
            ->get();

        $prefix = match (class_basename($report)) {
            'PraMonitoringReport' => 'pra-monitoring',
            'ReMonitoringReport' => 're-monitoring',
            default => 'monitoring',
        };

        return view('tampilan-gerai.detail', compact('report', 'blocks', 'prefix', 'type'));
    }

    public function storeBlock(string $type, int $reportId)
    {
        $report = $this->resolveReport($type, $reportId);

        $block = TampilanGeraiBlock::create([
            'reportable_type' => get_class($report),
            'reportable_id' => $report->id,
            'user_id' => Auth::id(),
            'keterangan' => null,
            'sort_order' => $report->tampilanGeraiBlocks()->count(),
        ]);

        return response()->json(['id' => $block->id]);
    }

    public function storePhoto(Request $request, string $type, int $reportId)
    {
        $report = $this->resolveReport($type, $reportId);

        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'block_id' => 'nullable|integer',
        ]);

        $block = null;
        if ($request->filled('block_id')) {
            $block = TampilanGeraiBlock::where('reportable_type', get_class($report))
                ->where('reportable_id', $report->id)
                ->find($request->input('block_id'));
        }
        if (!$block) {
            $block = TampilanGeraiBlock::create([
                'reportable_type' => get_class($report),
                'reportable_id' => $report->id,
                'user_id' => Auth::id(),
                'keterangan' => null,
                'sort_order' => $report->tampilanGeraiBlocks()->count(),
            ]);
        }

        $path = $request->file('foto')->store('tampilan-gerai');

        $photo = TampilanGeraiPhoto::create([
            'block_id' => $block->id,
            'foto' => $path,
            'sort_order' => $block->photos()->count(),
        ]);

        return response()->json([
            'id' => $photo->id,
            'url' => $photo->url(),
            'block_id' => $block->id,
        ]);
    }

    public function updateBlock(Request $request, TampilanGeraiBlock $block)
    {
        $this->authorizeBlock($block);

        $request->validate(['keterangan' => 'nullable|string|max:5000']);
        $value = $request->input('keterangan');

        if (trim((string) $value) === '' && $block->photos()->count() === 0) {
            $block->delete();
            return response()->json(['deleted' => true]);
        }

        $block->update(['keterangan' => $value]);

        return response()->json(['success' => true]);
    }

    public function destroyPhoto(TampilanGeraiPhoto $photo)
    {
        $this->authorizeBlock($photo->block);
        $photo->deleteFile();
        $photo->delete();

        return response()->json(['success' => true]);
    }

    public function destroyBlock(TampilanGeraiBlock $block)
    {
        $this->authorizeBlock($block);
        $block->delete();

        return response()->json(['success' => true]);
    }

    public function foto(TampilanGeraiPhoto $photo)
    {
        return Storage::disk('local')->response($photo->foto);
    }

    protected function authorizeBlock(TampilanGeraiBlock $block): void
    {
        if ($block->user_id !== Auth::id() && !Auth::user()?->isAdmin()) {
            abort(403, 'Anda tidak berhak mengubah data ini.');
        }
    }
}
