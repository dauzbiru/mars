<?php

namespace App\View\Composers;

use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\ReMonitoringReport;
use App\Models\EvaluasiReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view): void
    {
        $pendingReports = collect();
        $editingReports = collect();
        $userId = Auth::id();

        $isAssessmentPage = request()->is('*/assessment') || request()->is('*/assessment/*') || request()->is('*/temuan');

        $assessmentType = null;
        $assessmentId = null;
        $assessmentSubmitted = false;
        if ($isAssessmentPage) {
            if (preg_match('#^/?(monitoring|pra-monitoring|re-monitoring)/(\d+)/assessment#', request()->path(), $m)) {
                $assessmentType = $m[1];
                $assessmentId = (int) $m[2];

                $modelClass = match($assessmentType) {
                    'pra-monitoring' => PraMonitoringReport::class,
                    're-monitoring' => ReMonitoringReport::class,
                    default => MonitoringReport::class,
                };
                $report = $modelClass::find($assessmentId);
                $assessmentSubmitted = $report && $report->submit_at !== null;
            }
        }

        $isAdmin = (bool) Auth::user()?->isAdmin();

        $geraiCounts = [
            'pra-monitoring' => \App\Models\Gerai::active()
                ->where('opening_at', '<=', now()->subDays(20))
                ->where('opening_at', '>=', now()->subDays(40))
                ->count(),
            'monitoring' => \App\Models\Gerai::active()
                ->where('opening_at', '<=', now()->subMonths(5)->subDays(15))
                ->count(),
        ];

        if (!$isAssessmentPage && $userId) {
            $pendingWhere = $isAdmin
                ? "submit_at IS NULL AND checkin_at IS NOT NULL"
                : "user_id = ? AND submit_at IS NULL AND checkin_at IS NOT NULL";
            $evaluasiWhere = $isAdmin
                ? "tanggal IS NULL"
                : "user_id = ? AND tanggal IS NULL";
            $params = $isAdmin ? [] : [$userId, $userId, $userId, $userId];
            $rows = DB::select("
                SELECT 'monitoring' AS report_type, id FROM monitoring_reports
                WHERE {$pendingWhere}
                UNION ALL
                SELECT 'pra_monitoring' AS report_type, id FROM pra_monitoring_reports
                WHERE {$pendingWhere}
                UNION ALL
                SELECT 're_monitoring' AS report_type, id FROM re_monitoring_reports
                WHERE {$pendingWhere}
                UNION ALL
                SELECT 'evaluasi' AS report_type, id FROM evaluasi_reports
                WHERE {$evaluasiWhere}
            ", $params);

            $grouped = collect($rows)->groupBy('report_type');
            $typeModelMap = [
                'monitoring'    => MonitoringReport::class,
                'pra_monitoring' => PraMonitoringReport::class,
                're_monitoring'  => ReMonitoringReport::class,
                'evaluasi'       => EvaluasiReport::class,
            ];

            foreach ($typeModelMap as $type => $modelClass) {
                if ($grouped->has($type)) {
                    $pendingReports = $pendingReports->concat(
                        $modelClass::whereIn('id', $grouped[$type]->pluck('id'))->with('gerai')->get()
                    );
                }
            }

            $pendingReports = $pendingReports->sortByDesc(function ($r) {
                return $r->checkin_at ?? $r->created_at;
            })->values();

            $editWhere = $isAdmin
                ? "submit_at IS NOT NULL AND editing_user_id IS NOT NULL"
                : "editing_user_id = ? AND submit_at IS NOT NULL";
            $editParams = $isAdmin ? [] : [$userId, $userId, $userId];
            $editRows = DB::select("
                SELECT 'monitoring' AS report_type, id FROM monitoring_reports
                WHERE {$editWhere}
                UNION ALL
                SELECT 'pra_monitoring' AS report_type, id FROM pra_monitoring_reports
                WHERE {$editWhere}
                UNION ALL
                SELECT 're_monitoring' AS report_type, id FROM re_monitoring_reports
                WHERE {$editWhere}
            ", $editParams);

            $editGrouped = collect($editRows)->groupBy('report_type');
            $editTypeModelMap = [
                'monitoring'    => MonitoringReport::class,
                'pra_monitoring' => PraMonitoringReport::class,
                're_monitoring'  => ReMonitoringReport::class,
            ];

            foreach ($editTypeModelMap as $type => $modelClass) {
                if ($editGrouped->has($type)) {
                    $editingReports = $editingReports->concat(
                        $modelClass::whereIn('id', $editGrouped[$type]->pluck('id'))->with('gerai')->get()
                    );
                }
            }

            $editingReports = $editingReports->sortByDesc(function ($r) {
                return $r->editing_at ?? $r->updated_at;
            })->values();
        }

        $view->with('pendingReports', $pendingReports)
             ->with('editingReports', $editingReports)
             ->with('isAssessmentPage', $isAssessmentPage)
             ->with('assessmentType', $assessmentType)
             ->with('assessmentId', $assessmentId)
             ->with('assessmentSubmitted', $assessmentSubmitted)
             ->with('geraiCounts', $geraiCounts);
    }
}
