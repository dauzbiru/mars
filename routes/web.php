<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\GeraiController;
use App\Http\Controllers\KomplainController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PenjelasanFormulirController;

use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    if (Auth::check()) {
        if (in_array(Auth::user()->role, ['admin', 'superadmin'], true)) {
            return redirect('/dashboard');
        }
        return redirect('/guest');
    }
    return view('welcome');
});

Route::get('/guest', function () {
    if (!Auth::check()) {
        return redirect('/login');
    }
    if (in_array(Auth::user()->role, ['admin', 'superadmin'], true)) {
        return redirect('/dashboard');
    }
    return view('guest.landing');
})->middleware('auth');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



Route::middleware('auth')->group(function () {

    // === Admin-only routes ===
    Route::middleware('admin')->group(function () {
        // User management
        Route::get('/user', [UserController::class, 'index'])->name('user.index');
        Route::get('/user/create', [UserController::class, 'create']);
        Route::post('/user', [UserController::class, 'store']);
        Route::get('/user/{user}/edit', [UserController::class, 'edit']);
        Route::put('/user/{user}', [UserController::class, 'updateUser']);
        Route::delete('/user/{user}', [UserController::class, 'destroy']);

        // Categories
        Route::middleware('superadmin')->group(function () {
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::get('/categories/create', [CategoryController::class, 'create']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/reorder', [CategoryController::class, 'reorder']);
            Route::get('/categories/{category}', [CategoryController::class, 'show']);
            Route::get('/categories/{category}/edit', [CategoryController::class, 'edit']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

            // Penjelasan Formulir
            Route::get('/tugas/penjelasan-formulir-2', [PenjelasanFormulirController::class, 'index'])->defaults('formulir', 2);
            Route::get('/tugas/penjelasan-formulir-3', [PenjelasanFormulirController::class, 'index'])->defaults('formulir', 3);
            Route::post('/tugas/penjelasan-formulir/{formulir}', [PenjelasanFormulirController::class, 'store']);
            Route::put('/tugas/penjelasan-formulir/{penjelasan_formulir}', [PenjelasanFormulirController::class, 'update']);
            Route::delete('/tugas/penjelasan-formulir/{penjelasan_formulir}', [PenjelasanFormulirController::class, 'destroy']);
            Route::get('/tugas/penjelasan-formulir/{formulir}/import', [PenjelasanFormulirController::class, 'importForm']);
            Route::post('/tugas/penjelasan-formulir/{formulir}/import', [PenjelasanFormulirController::class, 'import']);
            Route::get('/tugas/penjelasan-formulir/{formulir}/template', [PenjelasanFormulirController::class, 'template']);

            // Items + Criteria
            Route::get('/categories/{category}/items/create', [ItemController::class, 'create']);
            Route::post('/categories/{category}/items', [ItemController::class, 'store']);
            Route::get('/items/{item}/edit', [ItemController::class, 'edit']);
            Route::put('/items/{item}', [ItemController::class, 'update']);
            Route::put('/items/{item}/up', [ItemController::class, 'moveUp']);
            Route::put('/items/{item}/down', [ItemController::class, 'moveDown']);
            Route::delete('/items/{item}', [ItemController::class, 'destroy']);
            Route::put('/categories/{category}/items/reorder', [ItemController::class, 'reorder']);
            Route::put('/categories/{category}/items/bobot', [ItemController::class, 'batchBobot']);

            Route::get('/items/{item}/criteria', [CriterionController::class, 'index']);
            Route::get('/items/{item}/criteria/create', [CriterionController::class, 'create']);
            Route::post('/items/{item}/criteria', [CriterionController::class, 'store']);
            Route::post('/items/{item}/criteria/batch', [CriterionController::class, 'batchStore']);
            Route::put('/items/{item}/criteria/reorder', [CriterionController::class, 'reorder']);
            Route::get('/items/{item}/criteria/{criterion}/edit', [CriterionController::class, 'edit']);
            Route::put('/items/{item}/criteria/{criterion}', [CriterionController::class, 'update']);
            Route::delete('/items/{item}/criteria/{criterion}', [CriterionController::class, 'destroy']);
        });

        // Gerai
        Route::get('/gerais', [GeraiController::class, 'index'])->name('gerai.index');
        Route::get('/gerais/create', [GeraiController::class, 'create']);
        Route::get('/gerais/import', [GeraiController::class, 'importForm']);
        Route::post('/gerais/import', [GeraiController::class, 'importExcel']);
        Route::get('/gerais/export', [GeraiController::class, 'exportExcel']);
        Route::get('/gerais/template', [GeraiController::class, 'template']);
        Route::post('/gerais', [GeraiController::class, 'store']);
        Route::get('/gerais/{gerai}', [GeraiController::class, 'show']);
        Route::get('/gerais/{gerai}/edit', [GeraiController::class, 'edit']);
        Route::put('/gerais/{gerai}', [GeraiController::class, 'update']);
        Route::delete('/gerais/{gerai}', [GeraiController::class, 'destroy']);
        Route::post('/gerais/{gerai}/tutup', [GeraiController::class, 'tutup']);
        Route::post('/gerais/{gerai}/buka', [GeraiController::class, 'buka']);
        Route::post('/gerais/sync-kota', [GeraiController::class, 'syncKota']);
        Route::post('/gerais/kota-maps', [GeraiController::class, 'storeKotaMap']);
        Route::put('/gerais/kota-maps/{kotaMap}', [GeraiController::class, 'updateKotaMap']);
        Route::delete('/gerais/kota-maps/{kotaMap}', [GeraiController::class, 'destroyKotaMap']);

        // Komplain
        Route::get('/komplain', [KomplainController::class, 'index'])->name('komplain.index');
        Route::get('/komplain/pdf/all', [KomplainController::class, 'pdfAll']);
        Route::get('/komplain/excel/all', [KomplainController::class, 'excelAll']);
        Route::get('/komplain/{komplain}/pdf', [KomplainController::class, 'pdf']);
        Route::get('/komplain/{komplain}', [KomplainController::class, 'show']);
        Route::post('/komplain', [KomplainController::class, 'store']);
        Route::put('/komplain/{komplain}', [KomplainController::class, 'update']);
        Route::put('/komplain/{komplain}/penanganan', [KomplainController::class, 'updatePenanganan']);
        Route::post('/komplain/{komplain}/template', [KomplainController::class, 'saveTemplate']);
        Route::delete('/komplain/{komplain}', [KomplainController::class, 'destroy']);

        // Tampilan Gerai
        Route::get('/tampilan-gerai', [\App\Http\Controllers\TampilanGeraiController::class, 'index'])->name('tampilan-gerai.index');
        Route::get('/tampilan-gerai/foto/{photo}', [\App\Http\Controllers\TampilanGeraiController::class, 'foto'])->name('tampilan-gerai.foto');
        Route::get('/tampilan-gerai/{type}/{report}/detail', [\App\Http\Controllers\TampilanGeraiController::class, 'detail']);
        Route::get('/tampilan-gerai/{type}/{report}', [\App\Http\Controllers\TampilanGeraiController::class, 'list']);
        Route::post('/tampilan-gerai/{type}/{report}/block', [\App\Http\Controllers\TampilanGeraiController::class, 'storeBlock']);
        Route::post('/tampilan-gerai/{type}/{report}/photo', [\App\Http\Controllers\TampilanGeraiController::class, 'storePhoto']);
        Route::patch('/tampilan-gerai/block/{block}', [\App\Http\Controllers\TampilanGeraiController::class, 'updateBlock']);
        Route::delete('/tampilan-gerai/photo/{photo}', [\App\Http\Controllers\TampilanGeraiController::class, 'destroyPhoto']);
        Route::delete('/tampilan-gerai/block/{block}', [\App\Http\Controllers\TampilanGeraiController::class, 'destroyBlock']);

        // AI
        Route::post('/ai/check-typo', [\App\Http\Controllers\AiController::class, 'checkTypo']);

        Route::get('/pgs', [\App\Http\Controllers\PgController::class, 'index'])->name('pgs.index');
        Route::post('/pgs', [\App\Http\Controllers\PgController::class, 'store']);
        Route::put('/pgs/{pg}', [\App\Http\Controllers\PgController::class, 'update']);
        Route::delete('/pgs/{pg}', [\App\Http\Controllers\PgController::class, 'destroy']);
        Route::post('/pgs/import', [\App\Http\Controllers\PgController::class, 'importExcel']);
        Route::get('/pgs/export', [\App\Http\Controllers\PgController::class, 'exportExcel']);
        Route::get('/pgs/template', [\App\Http\Controllers\PgController::class, 'template']);

        // Semester Periods
        Route::get('/semester-periods', [\App\Http\Controllers\SemesterPeriodController::class, 'index'])->name('semester-periods.index');
        Route::get('/semester-periods/create', [\App\Http\Controllers\SemesterPeriodController::class, 'create']);
        Route::post('/semester-periods', [\App\Http\Controllers\SemesterPeriodController::class, 'store']);
        Route::get('/semester-periods/{semesterPeriod}/edit', [\App\Http\Controllers\SemesterPeriodController::class, 'edit']);
        Route::put('/semester-periods/{semesterPeriod}', [\App\Http\Controllers\SemesterPeriodController::class, 'update']);
        Route::delete('/semester-periods/{semesterPeriod}', [\App\Http\Controllers\SemesterPeriodController::class, 'destroy']);

        // Import + Export
        Route::get('/import', [ImportController::class, 'create']);
        Route::post('/import', [ImportController::class, 'import']);
        Route::get('/import/template', [ImportController::class, 'template']);

        // Daftar Nilai write operations
        Route::post('/gerai-pendampingan/{report}/mark-sent', [\App\Http\Controllers\RankingController::class, 'markWaSent']);
        Route::post('/daftar-nilai/hapus-periode', [\App\Http\Controllers\RankingController::class, 'hapusPeriode']);
        Route::delete('/daftar-nilai/{report}', [\App\Http\Controllers\RankingController::class, 'destroy']);
        Route::put('/daftar-nilai/{report}', [\App\Http\Controllers\RankingController::class, 'update']);
        Route::get('/daftar-nilai/import', [\App\Http\Controllers\RankingController::class, 'importForm'])->name('daftar-nilai.import');
        Route::post('/daftar-nilai/import', [\App\Http\Controllers\RankingController::class, 'import']);
        Route::get('/daftar-nilai/import/template', [\App\Http\Controllers\RankingController::class, 'template']);

        // Excel Templates
        Route::middleware('superadmin')->group(function () {
            Route::post('/excel-template/upload', [\App\Http\Controllers\MonitoringController::class, 'uploadTemplate']);
            Route::delete('/excel-template/delete', [\App\Http\Controllers\MonitoringController::class, 'deleteTemplate']);

            // Template Evaluasi upload/delete
            Route::post('/excel-template/evaluasi/upload', [\App\Http\Controllers\MonitoringController::class, 'uploadTemplateEvaluasi']);
            Route::delete('/excel-template/evaluasi/delete', [\App\Http\Controllers\MonitoringController::class, 'deleteTemplateEvaluasi']);

            // Template Surat Word (Pra-Monitoring) upload/delete
            Route::post('/word-template/upload', [\App\Http\Controllers\PraMonitoringController::class, 'uploadWordTemplate']);
            Route::delete('/word-template/delete', [\App\Http\Controllers\PraMonitoringController::class, 'deleteWordTemplate']);

            // Settings
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('/excel-template', function () {
                return view('excel-template');
            })->name('excel-template');
            Route::get('/excel-template/example', [\App\Http\Controllers\MonitoringController::class, 'downloadExampleTemplate'])->name('excel-template.example');
        });

        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/chart-data', [\App\Http\Controllers\DashboardController::class, 'chartData']);

        // Daftar Nilai
        Route::get('/daftar-nilai', [\App\Http\Controllers\RankingController::class, 'index'])->name('daftar-nilai.index');
        Route::get('/daftar-nilai/pra-monitoring', [\App\Http\Controllers\RankingController::class, 'praMonitoring'])->name('daftar-nilai.pra-monitoring');
        Route::get('/daftar-nilai/peringkat', [\App\Http\Controllers\RankingController::class, 'peringkat'])->name('daftar-nilai.peringkat');
        Route::get('/daftar-nilai/peringkat/excel', [\App\Http\Controllers\RankingController::class, 'peringkatExcel']);
        Route::get('/daftar-nilai/peringkat/rankings', [\App\Http\Controllers\RankingController::class, 'peringkatRankings']);
        Route::get('/daftar-nilai/excel', [\App\Http\Controllers\RankingController::class, 'excel']);
        Route::get('/daftar-nilai/performa', [\App\Http\Controllers\RankingController::class, 'performa'])->name('daftar-nilai.performa');
        Route::get('/gerai-pendampingan', [\App\Http\Controllers\RankingController::class, 'pendampingan'])->name('gerai-pendampingan');
        Route::get('/nilai-pairing', [\App\Http\Controllers\RankingController::class, 'nilaiPairing'])->name('nilai-pairing');
        Route::get('/nilai-pairing/excel', [\App\Http\Controllers\RankingController::class, 'nilaiPairingExcel']);

        // Report admin-only
        Route::get('/report/pdf', [ReportController::class, 'pdf']);
        Route::get('/report/excel', [ReportController::class, 'excel']);
        Route::get('/report/analytics', [ReportController::class, 'analytics']);
        Route::get('/report/analytics/excel', [ReportController::class, 'analyticsExcel']);
        Route::get('/report/ambil-data', [ReportController::class, 'ambilData']);
        Route::get('/report/checklist-tidak-sempurna', [ReportController::class, 'checklistTidakSempurna']);
        Route::get('/report/export-all-excel', [ReportController::class, 'exportAllExcel']);
        Route::get('/report/export-all-pdf', [ReportController::class, 'exportAllPdf']);
        Route::get('/report/excel-detail', [ReportController::class, 'excelDetail']);

        // Evaluasi
        Route::get('/report/evaluasi', [\App\Http\Controllers\ReportController::class, 'evaluasi'])->name('report.evaluasi');
        Route::get('/evaluasi', [\App\Http\Controllers\EvaluasiController::class, 'selectGerai'])->name('evaluasi.select-gerai');
        Route::get('/evaluasi/checkin/{gerai}', [\App\Http\Controllers\EvaluasiController::class, 'checkinForm']);
        Route::get('/evaluasi/{report}/assessment', [\App\Http\Controllers\EvaluasiController::class, 'assessment']);

        Route::post('/evaluasi/{report}/submit', [\App\Http\Controllers\EvaluasiController::class, 'submit']);
        Route::post('/evaluasi/{report}/cancel', [\App\Http\Controllers\EvaluasiController::class, 'cancelAssessment']);
        Route::get('/evaluasi/{report}/temuan', [\App\Http\Controllers\EvaluasiController::class, 'temuanForm']);
        Route::post('/evaluasi/{report}/temuan', [\App\Http\Controllers\EvaluasiController::class, 'saveTemuan']);
        Route::get('/evaluasi/{report}/pdf', [\App\Http\Controllers\EvaluasiController::class, 'pdf']);
        Route::get('/evaluasi/{report}/excel', [\App\Http\Controllers\EvaluasiController::class, 'excel']);
        Route::get('/evaluasi/{report}', [\App\Http\Controllers\EvaluasiController::class, 'show']);
        Route::put('/evaluasi/{report}', [\App\Http\Controllers\EvaluasiController::class, 'update']);
        Route::delete('/evaluasi/{report}', [\App\Http\Controllers\EvaluasiController::class, 'destroy']);

    });

    // === All authenticated users (guest + admin) ===
    // User self-profile update
    Route::put('/user', [UserController::class, 'update']);

    // Report (with ownership filtering in controller)
        Route::get('/report/monitoring', [ReportController::class, 'index'])->name('report.monitoring');
        Route::get('/report/pra-monitoring', [ReportController::class, 'preMonitoring'])->name('report.pra-monitoring');
        Route::get('/report/re-monitoring', [ReportController::class, 'reMonitoring'])->name('report.re-monitoring');

    // Monitoring (with authorizeReport ownership check)
    Route::get('/monitoring', [\App\Http\Controllers\MonitoringController::class, 'selectGerai'])->name('monitoring.select-gerai');
    Route::get('/monitoring/checkin/{gerai}', [\App\Http\Controllers\MonitoringController::class, 'checkinForm']);
    Route::post('/monitoring/checkin/{gerai}', [\App\Http\Controllers\MonitoringController::class, 'doCheckin']);
    Route::get('/monitoring/{report}/assessment', [\App\Http\Controllers\MonitoringController::class, 'assessment']);
    Route::get('/monitoring/{report}/assessment/{category}/form', [\App\Http\Controllers\MonitoringController::class, 'assessmentForm']);
    Route::post('/monitoring/{report}/assessment/{category}/form', [\App\Http\Controllers\MonitoringController::class, 'saveAssessmentForm']);
    Route::post('/monitoring/{report}/submit', [\App\Http\Controllers\MonitoringController::class, 'submit']);
    Route::post('/monitoring/{report}/cancel', [\App\Http\Controllers\MonitoringController::class, 'cancelAssessment']);
    Route::get('/monitoring/{report}/temuan', [\App\Http\Controllers\MonitoringController::class, 'temuanForm']);
    Route::post('/monitoring/{report}/temuan', [\App\Http\Controllers\MonitoringController::class, 'saveTemuan']);
    Route::get('/monitoring/{report}/pdf', [\App\Http\Controllers\MonitoringController::class, 'pdf']);
    Route::get('/monitoring/{report}/excel', [\App\Http\Controllers\MonitoringController::class, 'excel']);
    Route::get('/monitoring/{report}', [\App\Http\Controllers\MonitoringController::class, 'show']);
    Route::put('/monitoring/{report}/info', [\App\Http\Controllers\MonitoringController::class, 'updateInfo'])->middleware('superadmin');
    Route::delete('/monitoring/{report}', [\App\Http\Controllers\MonitoringController::class, 'destroy']);

    // Pra-Monitoring
    Route::get('/pra-monitoring', [\App\Http\Controllers\PraMonitoringController::class, 'selectGerai'])->name('pra-monitoring.select-gerai');
    Route::get('/pra-monitoring/checkin/{gerai}', [\App\Http\Controllers\PraMonitoringController::class, 'checkinForm']);
    Route::post('/pra-monitoring/checkin/{gerai}', [\App\Http\Controllers\PraMonitoringController::class, 'doCheckin']);
    Route::get('/pra-monitoring/{report}/assessment', [\App\Http\Controllers\PraMonitoringController::class, 'assessment']);
    Route::get('/pra-monitoring/{report}/assessment/{category}/form', [\App\Http\Controllers\PraMonitoringController::class, 'assessmentForm']);
    Route::post('/pra-monitoring/{report}/assessment/{category}/form', [\App\Http\Controllers\PraMonitoringController::class, 'saveAssessmentForm']);
    Route::post('/pra-monitoring/{report}/submit', [\App\Http\Controllers\PraMonitoringController::class, 'submit']);
    Route::post('/pra-monitoring/{report}/cancel', [\App\Http\Controllers\PraMonitoringController::class, 'cancelAssessment']);
    Route::get('/pra-monitoring/{report}/temuan', [\App\Http\Controllers\PraMonitoringController::class, 'temuanForm']);
    Route::post('/pra-monitoring/{report}/temuan', [\App\Http\Controllers\PraMonitoringController::class, 'saveTemuan']);
    Route::get('/pra-monitoring/{report}/pdf', [\App\Http\Controllers\PraMonitoringController::class, 'pdf']);
    Route::get('/pra-monitoring/{report}/excel', [\App\Http\Controllers\PraMonitoringController::class, 'excel']);
    Route::get('/pra-monitoring/{report}', [\App\Http\Controllers\PraMonitoringController::class, 'show']);
    Route::put('/pra-monitoring/{report}/info', [\App\Http\Controllers\PraMonitoringController::class, 'updateInfo'])->middleware('superadmin');
    Route::delete('/pra-monitoring/{report}', [\App\Http\Controllers\PraMonitoringController::class, 'destroy']);

    // Re-Monitoring
    Route::get('/re-monitoring', [\App\Http\Controllers\ReMonitoringController::class, 'selectGerai'])->name('re-monitoring.select-gerai');
    Route::get('/re-monitoring/checkin/{gerai}', [\App\Http\Controllers\ReMonitoringController::class, 'checkinForm']);
    Route::post('/re-monitoring/checkin/{gerai}', [\App\Http\Controllers\ReMonitoringController::class, 'doCheckin']);
    Route::get('/re-monitoring/{report}/assessment', [\App\Http\Controllers\ReMonitoringController::class, 'assessment']);
    Route::get('/re-monitoring/{report}/assessment/{category}/form', [\App\Http\Controllers\ReMonitoringController::class, 'assessmentForm']);
    Route::post('/re-monitoring/{report}/assessment/{category}/form', [\App\Http\Controllers\ReMonitoringController::class, 'saveAssessmentForm']);
    Route::post('/re-monitoring/{report}/submit', [\App\Http\Controllers\ReMonitoringController::class, 'submit']);
    Route::post('/re-monitoring/{report}/cancel', [\App\Http\Controllers\ReMonitoringController::class, 'cancelAssessment']);
    Route::get('/re-monitoring/{report}/temuan', [\App\Http\Controllers\ReMonitoringController::class, 'temuanForm']);
    Route::post('/re-monitoring/{report}/temuan', [\App\Http\Controllers\ReMonitoringController::class, 'saveTemuan']);
    Route::get('/re-monitoring/{report}/pdf', [\App\Http\Controllers\ReMonitoringController::class, 'pdf']);
    Route::get('/re-monitoring/{report}/excel', [\App\Http\Controllers\ReMonitoringController::class, 'excel']);
    Route::get('/re-monitoring/{report}', [\App\Http\Controllers\ReMonitoringController::class, 'show']);
        Route::put('/re-monitoring/{report}/info', [\App\Http\Controllers\ReMonitoringController::class, 'updateInfo'])->middleware('superadmin');
        Route::delete('/re-monitoring/{report}', [\App\Http\Controllers\ReMonitoringController::class, 'destroy']);

    });
