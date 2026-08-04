@extends('layouts.admin')

@section('title', 'Evaluasi - ' . $report->gerai->nama_gerai)

@section('content')
<div class="max-w-lg mx-auto">
    {{-- Score Card --}}
    <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-5 mb-5 text-white shadow-md flex items-center">
        <p class="text-xl uppercase tracking-wider font-bold text-left">Evaluasi</p>
        <div class="text-right ml-auto">
            <p class="font-bold text-sm">{{ $report->gerai->kode_gerai }}</p>
            <p class="text-xs text-purple-200 mt-0.5">{{ $report->gerai->nama_gerai }}</p>
            @if ($report->gerai->nama_kota)
            <p class="text-xs text-purple-200 mt-0.5">{{ $report->gerai->nama_kota }}</p>
            @endif
            <p class="text-xs text-purple-200 mt-1">{{ $report->created_at->format('d-m-Y') }}</p>
        </div>
    </div>

    {{-- Laporan Sebelumnya --}}
    @if ($lastReport && ($lastReport->major || $lastReport->minor))
    @php $f = $lastReport; @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
        <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">{{ str_replace('-', ' ', ucfirst(match(class_basename($lastReport)) { 'ReMonitoringReport' => 're-monitoring', default => $lastReport->type ?? 'monitoring', })) }} — {{ $lastReport->checkin_at->format('d-m-Y') }} — {{ $lastReport->user?->name ?? '-' }} — Nilai: {{ $lastReport->nilai ?? '-' }} ({{ $lastReport->grade ?? '-' }})</p>

        @if ($f->major)
        <div class="mb-3">
            <p class="text-xs font-bold text-gray-900 mb-1">Mayor</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ $f->major }}</p>
        </div>
        @endif

        @if ($f->minor)
        <div class="mb-3">
            <p class="text-xs font-bold text-gray-900 mb-1">Minor</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ $f->minor }}</p>
        </div>
        @endif

        @if ($f->peringatan_awal)
        <div class="mb-3">
            <p class="text-xs font-bold text-gray-900 mb-1">Peringatan Awal</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ $f->peringatan_awal }}</p>
        </div>
        @endif

        @if ($f->note)
        <div class="mb-3">
            <p class="text-xs font-bold text-gray-900 mb-1">Note</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ $f->note }}</p>
        </div>
        @endif

        @if ($f->kondisi_cat || $f->kondisi_awning || $f->kondisi_vinyl || $f->kondisi_stiker_kaca)
        <div>
            <p class="text-xs font-bold text-gray-900 mb-1">Checklist Kondisi Gerai</p>
            <p class="text-sm text-gray-700">Kondisi cat: {{ $f->kondisi_cat ?: 'Baik' }}</p>
            <p class="text-sm text-gray-700">Kondisi awning: {{ $f->kondisi_awning ?: 'Baik' }}</p>
            <p class="text-sm text-gray-700">Kondisi vinyl reklame dinding/jalan: {{ $f->kondisi_vinyl ?: 'Baik' }}</p>
            <p class="text-sm text-gray-700">Kondisi stiker kaca: {{ $f->kondisi_stiker_kaca ?: 'Baik' }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Form --}}
    <form id="assessment-form" method="POST" action="/{{ $prefix }}/{{ $report->id }}">
        @csrf
        @method('PUT')

        {{-- Catatan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-3">
            <label for="catatan" class="block text-xs font-semibold text-gray-600 mb-2">Catatan</label>
            <textarea name="catatan" id="catatan" rows="5" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm outline-none resize-none box-border" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" placeholder="Tulis catatan evaluasi...">{{ old('catatan', $report->catatan ?? '') }}</textarea>
        </div>

        {{-- Keterangan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <label for="keterangan" class="block text-xs font-semibold text-gray-600 mb-2">Keterangan</label>
            <textarea name="keterangan" id="keterangan" rows="5" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm outline-none resize-none box-border" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" placeholder="Tulis keterangan evaluasi...">{{ old('keterangan', $report->keterangan ?? '') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            @if ($report->tanggal)
            <button type="button" onclick="showConfirm('Yakin ingin batalkan perubahan?', function(){ document.getElementById('cancel-form').submit(); })" class="flex-1 px-3 py-3 bg-white border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl cursor-pointer transition-all duration-150 hover:bg-gray-100 active:scale-97">Batalkan</button>
            @else
            <button type="button" onclick="showConfirm('Batalkan laporan ini? Laporan akan dihapus.', function(){ document.getElementById('delete-form').submit(); })" class="flex-1 px-3 py-3 bg-white border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl cursor-pointer transition-all duration-150 hover:bg-gray-100 active:scale-97">Batalkan</button>
            @endif
            <button type="button" onclick="submitEvaluasi()" class="flex-1 px-3 py-3 bg-blue-500 text-white text-sm font-semibold rounded-xl border-none cursor-pointer transition-all duration-150 hover:bg-blue-600 active:scale-97">Simpan</button>
        </div>
    </form>

    @if ($report->tanggal)
    <form id="cancel-form" method="POST" action="/{{ $prefix }}/{{ $report->id }}/cancel">@csrf</form>
    @else
    <form id="delete-form" method="POST" action="/{{ $prefix }}/{{ $report->id }}">@csrf @method('DELETE')<input type="hidden" name="_from" value="assessment"></form>
    @endif
</div>
@endsection

@push('scripts')
<script>
var kodeGerai = @json($report->gerai->kode_gerai);
var namaGerai = @json($report->gerai->nama_gerai);

var lastGrade = @json($lastReport->grade ?? null);
var gradeLabel = {A:'Sangat Baik',B:'Baik',C:'Cukup',D:'Kurang Baik',E:'Tidak Baik'}[lastGrade] || 'Baik';
var pimpinanText = ['C','D','E'].includes(lastGrade)
    ? 'Pimpinan Gerai belum mampu menerapkan & mengarahkan dengan baik standar pelayanan pelanggan kepada karyawan sesuai standar PSSO.'
    : 'Pimpinan Gerai mampu menerapkan & mengarahkan dengan baik standar pelayanan pelanggan kepada karyawan sesuai standar PSSO.';
var catatanTemplate = '1. Kinerja operasional serta pemahaman untuk Pengawas & Karyawan ' + gradeLabel + '.\n2. Kebersihan di halaman gerai serta kelengkapan teknis di gerai ' + gradeLabel + '.\n3. ' + pimpinanText;

var lastReportType = @json($lastReportType);
var belowAvgCount = @json($belowAverageCount);
var lastReport = @json($lastReport ? ['month' => $lastReport->checkin_at->locale('id')->isoFormat('MMMM YYYY')] : null);
var periodeLabel1 = lastReportType === 're-monitoring'
    ? 'Re-Monitoring ' + (lastReport ? lastReport.month : 'terbaru')
    : 'monitoring periode terbaru';
var poin1 = '1. Poin kinerja di gerai ' + kodeGerai + ' berada di atas Rerata pada ' + periodeLabel1 + ' dan ' + (belowAvgCount > 0
    ? 'pernah ' + belowAvgCount + 'x berada di bawah Rerata pada monitoring periode sebelumnya.'
    : 'belum pernah berada di bawah Rerata pada monitoring periode sebelumnya.');
var posisiText = ['C','D','E'].includes(lastGrade)
    ? '2. Gerai ' + kodeGerai + ' belum mampu menempatkan posisi kinerja gerainya untuk berada di atas rerata monitoring semua gerai BIRU.'
    : '2. Gerai ' + kodeGerai + ' mampu menempatkan posisi kinerja gerainya untuk berada di atas rerata monitoring semua gerai BIRU.';
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
        if (!r.ok) throw new Error('Gagal menyimpan');
        window.location.replace('/{{ $prefix }}/{{ $report->id }}');
    }).catch(function(err) {
        showAlert('Terjadi kesalahan: ' + err.message);
    });
}

var autoSaveTimer = null;
document.querySelectorAll('#assessment-form textarea').forEach(function(el) {
    el.addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            var fd = new FormData(document.getElementById('assessment-form'));
            fd.append('_token', '{{ csrf_token() }}');
            fetch('/{{ $prefix }}/{{ $report->id }}', {
                method: 'PUT',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(function(err) {
                console.error('Auto-save gagal:', err);
            });
        }, 1000);
    });
});

window.history.replaceState(null, '', window.location.href);
window.addEventListener('popstate', function() {
    @if ($report->tanggal)
    window.location.href = '/{{ $prefix }}/{{ $report->id }}';
    @else
    window.location.href = '/evaluasi';
    @endif
});
</script>
@endpush
