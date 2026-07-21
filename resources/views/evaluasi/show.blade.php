@extends('layouts.admin')

@section('title', 'Detail Evaluasi - ' . $report->gerai->nama_gerai)

@section('content')
<div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div>
        <a href="/report/evaluasi" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke Daftar Laporan</a>
        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mt-1">{{ $report->gerai->kode_gerai }} - {{ $report->gerai->nama_gerai }}</h2>
    </div>
</div>

@if ($historyData->isNotEmpty())
@php
    $groups = [];
    $i = 0;
    $n = count($historyData);
    while ($i < $n) {
        $current = $historyData[$i];
        $year = $current['year'];
        $isRemon = $current['type'] === 're-monitoring';
        $colspan = 1;
        $j = $i + 1;
        while ($j < $n) {
            $next = $historyData[$j];
            $nextRemon = $next['type'] === 're-monitoring';
            if ($next['year'] === $year && $nextRemon === $isRemon) {
                $colspan++;
                $j++;
            } else {
                break;
            }
        }
        $groups[] = ['year' => $year, 'colspan' => $colspan, 'isRemon' => $isRemon];
        $i = $j;
    }
@endphp
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Riwayat Nilai</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs sm:text-sm border border-gray-300" style="min-width: {{ count($historyData) * 70 }}px; border-collapse: collapse;">
            <thead>
                <tr class="bg-gray-50">
                    @foreach ($groups as $g)
                    <th class="px-2 py-2 text-center border border-gray-300 whitespace-nowrap" colspan="{{ $g['colspan'] }}">
                        <div class="font-bold text-gray-800">{{ $g['year'] }}</div>
                    </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($historyData as $h)
                    <th class="px-2 py-1 text-center border border-gray-300 whitespace-nowrap font-normal text-gray-500 text-[0.65rem]">
                        {{ $h['periode_short'] ?? '-' }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach ($historyData as $h)
                    <td class="px-2 py-2 text-center border border-gray-300 whitespace-nowrap">
                        <div class="font-bold">975</div>
                    </td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($historyData as $h)
                    <td class="px-2 py-2 text-center border border-gray-300 whitespace-nowrap">
                        <span class="font-bold text-base {{ $h['nilai'] >= 975 ? 'text-green-600' : 'text-red-600' }}">{{ round($h['nilai']) }}</span>
                    </td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($historyData as $h)
                    <td class="px-2 py-2 text-center border border-gray-300 whitespace-nowrap">
                        @if ($h['type'] === 're-monitoring')
                            <span class="font-semibold text-purple-600">REMON</span>
                        @elseif ($h['rank'] && $h['total'])
                            <span class="font-semibold">{{ $h['rank'] }}</span><span class="text-gray-400">-{{ $h['total'] }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@php $lastFinding = $historyData->isNotEmpty() ? $historyData->last()['finding'] : null; @endphp

@if ($lastFinding)
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Laporan Terakhir</h3>
    </div>
    <div class="p-4 sm:p-5 space-y-3">
        @if ($lastFinding->major)
            <div>
                <p class="text-xs font-medium text-gray-500">Major</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ $lastFinding->major }}</p>
            </div>
        @endif
        @if ($lastFinding->minor)
            <div>
                <p class="text-xs font-medium text-gray-500">Minor</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ $lastFinding->minor }}</p>
            </div>
        @endif
        @if ($lastFinding->peringatan_awal)
            <div>
                <p class="text-xs font-medium text-gray-500">Peringatan Awal</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ $lastFinding->peringatan_awal }}</p>
            </div>
        @endif
        @if ($lastFinding->note)
            <div>
                <p class="text-xs font-medium text-gray-500">Note</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ $lastFinding->note }}</p>
            </div>
        @endif
        @if ($lastFinding->kondisi_cat || $lastFinding->kondisi_awning || $lastFinding->kondisi_vinyl || $lastFinding->kondisi_stiker_kaca)
            <div>
                <p class="text-xs font-medium text-gray-500">Checklist Kondisi Gerai</p>
                <p class="text-sm text-gray-800">Kondisi cat: {{ $lastFinding->kondisi_cat ?: 'Baik' }}</p>
                <p class="text-sm text-gray-800">Kondisi awning: {{ $lastFinding->kondisi_awning ?: 'Baik' }}</p>
                <p class="text-sm text-gray-800">Kondisi vinyl reklame dinding/jalan: {{ $lastFinding->kondisi_vinyl ?: 'Baik' }}</p>
                <p class="text-sm text-gray-800">Kondisi stiker kaca: {{ $lastFinding->kondisi_stiker_kaca ?: 'Baik' }}</p>
            </div>
        @endif
    </div>
</div>
@endif

@if ($report->catatan || $report->keterangan)
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Evaluasi</h3>
        <button type="button" onclick="toggleEditEvaluasi()" id="editEvaluasiBtn" class="text-xs text-blue-600 hover:underline font-medium">Edit</button>
    </div>
    <div class="p-4 sm:p-5 space-y-3" id="evaluasiView">
        @if ($report->catatan)
            <div>
                <p class="text-xs font-medium text-gray-500">Catatan</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ $report->catatan }}</p>
            </div>
        @endif
        @if ($report->keterangan)
            <div>
                <p class="text-xs font-medium text-gray-500">Keterangan</p>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ $report->keterangan }}</p>
            </div>
        @endif
    </div>
    <div class="p-4 sm:p-5 hidden" id="evaluasiEdit">
        <form id="editEvaluasiForm">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Catatan</label>
                <textarea name="catatan" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none resize-none" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ $report->catatan }}</textarea>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Keterangan</label>
                <textarea name="keterangan" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none resize-none" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ $report->keterangan }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="toggleEditEvaluasi()" class="flex-1 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition">Batal</button>
                <button type="button" onclick="saveEvaluasi()" class="flex-1 py-2.5 bg-blue-500 text-white text-sm font-semibold rounded-lg hover:bg-blue-600 transition">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

@if (!$report->tanggal)
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Catatan & Keterangan</h3>
    </div>
    <div class="p-4 sm:p-5">
        <form id="assessment-form" method="POST" action="/{{ $prefix }}/{{ $report->id }}/assessment">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Catatan</label>
                <textarea name="catatan" id="catatan" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none resize-none" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" placeholder="Tulis catatan evaluasi...">{{ old('catatan', $report->catatan ?? '') }}</textarea>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Keterangan</label>
                <textarea name="keterangan" id="keterangan" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none resize-none" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" placeholder="Tulis keterangan evaluasi...">{{ old('keterangan', $report->keterangan ?? '') }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="showConfirm('Batalkan laporan ini? Laporan akan dihapus.', function(){ document.getElementById('delete-form').submit(); })" class="flex-1 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition">Batalkan</button>
                <button type="button" onclick="submitEvaluasi()" class="flex-1 py-2.5 bg-blue-500 text-white text-sm font-semibold rounded-lg hover:bg-blue-600 transition">Simpan</button>
            </div>
        </form>
        <form id="delete-form" method="POST" action="/{{ $prefix }}/{{ $report->id }}">@csrf @method('DELETE')<input type="hidden" name="_from" value="assessment"></form>
    </div>
</div>
@endif

{{-- FAB --}}
<div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
    <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
        <a href="/{{ $prefix }}/{{ $report->id }}/pdf" onclick="closeFab()"
            class="w-12 h-12 bg-red-600 text-white rounded-full shadow-lg hover:bg-red-700 flex items-center justify-center text-xs font-medium relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">PDF</span>
        </a>
        <a href="/{{ $prefix }}/{{ $report->id }}/excel" onclick="closeFab()"
            class="w-12 h-12 bg-orange-600 text-white rounded-full shadow-lg hover:bg-orange-700 flex items-center justify-center text-xs font-medium relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Excel</span>
        </a>
    </div>
    <button id="fabToggle"
        style="background:#3B82F6;color:#FFFFFF"
        class="w-14 h-14 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center transition-transform duration-200">
        <svg id="fabIcon" class="w-7 h-7 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
    </button>
</div>

@endsection

@push('scripts')
<script>
window.history.replaceState(null, '', window.location.href);
window.addEventListener('popstate', function() {
    window.location.href = '/evaluasi';
});

@if (!$report->tanggal)
var kodeGerai = @json($report->gerai->kode_gerai);
var lastGrade = @json($lastReportGrade);
var gradeLabel = {A:'Sangat Baik',B:'Baik',C:'Cukup',D:'Kurang Baik',E:'Tidak Baik'}[lastGrade] || 'Baik';
var pimpinanText = ['C','D','E'].includes(lastGrade)
    ? 'Pimpinan Gerai belum mampu menerapkan & mengarahkan dengan baik standar pelayanan pelanggan kepada karyawan sesuai standar PSSO.'
    : 'Pimpinan Gerai mampu menerapkan & mengarahkan dengan baik standar pelayanan pelanggan kepada karyawan sesuai standar PSSO.';
var catatanTemplate = '1. Kinerja operasional serta pemahaman untuk Pengawas & Karyawan ' + gradeLabel + '.\n2. Kebersihan di halaman gerai serta kelengkapan teknis di gerai ' + gradeLabel + '.\n3. ' + pimpinanText;

var lastReportType = @json($lastReportType);
var belowAvgCount = @json($belowAverageCount);
var lastReportMonth = @json($lastReportMonth);
var periodeLabel1 = lastReportType === 're-monitoring'
    ? 'Re-Monitoring ' + (lastReportMonth || 'terbaru')
    : 'monitoring periode terbaru';
var poin1 = '1. Poin kinerja di gerai ' + kodeGerai + ' berada di atas Standar Kinerja pada ' + periodeLabel1;
if (belowAvgCount > 0) {
    poin1 += ' dan pernah ' + belowAvgCount + 'x berada di bawah Standar Kinerja pada monitoring periode sebelumnya.';
} else {
    poin1 += '.';
}
var posisiText = ['C','D','E'].includes(lastGrade)
    ? '2. Gerai ' + kodeGerai + ' belum mampu menempatkan posisi kinerja gerainya untuk berada di atas Standar Kinerja monitoring semua gerai BIRU.'
    : '2. Gerai ' + kodeGerai + ' mampu menempatkan posisi kinerja gerainya untuk berada di atas Standar Kinerja monitoring semua gerai BIRU.';
var keteranganTemplate = poin1 + '\n' + posisiText + '\n3. Gerai ' + kodeGerai + ' masuk dalam Grade ' + (lastGrade || 'B') + ' dengan kategori ' + gradeLabel + '.';

var catatanEl = document.getElementById('catatan');
var keteranganEl = document.getElementById('keterangan');

if (!catatanEl.value.trim()) {
    catatanEl.value = catatanTemplate;
}
catatanEl.style.height = '';
catatanEl.style.height = catatanEl.scrollHeight + 'px';

if (!keteranganEl.value.trim()) {
    keteranganEl.value = keteranganTemplate;
}
keteranganEl.style.height = '';
keteranganEl.style.height = keteranganEl.scrollHeight + 'px';

function submitEvaluasi() {
    var f = document.getElementById('assessment-form');
    var formData = new FormData(f);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/{{ $prefix }}/{{ $report->id }}/submit', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) {
        window.location.reload();
    });
}

var autoSaveTimer = null;
document.querySelectorAll('#assessment-form textarea').forEach(function(el) {
    el.addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            var fd = new FormData(document.getElementById('assessment-form'));
            fd.append('_token', '{{ csrf_token() }}');
            fetch('/{{ $prefix }}/{{ $report->id }}/assessment', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        }, 1000);
    });
});
@endif

var fabToggle = document.getElementById('fabToggle');
var fabActions = document.getElementById('fabActions');
var fabIcon = document.getElementById('fabIcon');

function closeFab() {
    fabActions.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
    fabActions.classList.add('opacity-0', 'scale-0', 'pointer-events-none');
    fabIcon.classList.remove('rotate-45');
}

function openFab() {
    fabActions.classList.remove('opacity-0', 'scale-0', 'pointer-events-none');
    fabActions.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
    fabIcon.classList.add('rotate-45');
}

fabToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    var isOpen = fabActions.classList.contains('opacity-100');
    if (isOpen) { closeFab(); } else { openFab(); }
});

document.addEventListener('click', function(e) {
    if (fabActions.classList.contains('opacity-100') && !e.target.closest('#fabMenu')) {
        closeFab();
    }
});

function toggleEditEvaluasi() {
    var view = document.getElementById('evaluasiView');
    var edit = document.getElementById('evaluasiEdit');
    var btn = document.getElementById('editEvaluasiBtn');
    if (edit.classList.contains('hidden')) {
        view.classList.add('hidden');
        edit.classList.remove('hidden');
        btn.textContent = '';
        edit.querySelectorAll('textarea').forEach(function(t) { t.style.height = ''; t.style.height = t.scrollHeight + 'px'; });
    } else {
        view.classList.remove('hidden');
        edit.classList.add('hidden');
        btn.textContent = 'Edit';
    }
}

function saveEvaluasi() {
    var f = document.getElementById('editEvaluasiForm');
    var fd = new FormData(f);
    fd.append('_token', '{{ csrf_token() }}');
    fetch('/{{ $prefix }}/{{ $report->id }}/assessment', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function() { window.location.reload(); });
}
</script>
@endpush
