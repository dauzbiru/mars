@extends('layouts.admin')

@section('title', 'Evaluasi - ' . $report->gerai->nama_gerai)

@section('content')
<div class="max-w-lg mx-auto">
    {{-- Score Card --}}
    <div style="background:linear-gradient(135deg,#7C3AED,#6D28D9);border-radius:1rem;padding:1.25rem;margin-bottom:1.25rem;color:#fff;box-shadow:0 4px 6px -1px rgba(0,0,0,.1);position:relative;display:flex;align-items:center">
        <p style="font-size:1.25rem;text-transform:uppercase;letter-spacing:0.05em;color:#FFFFFF;font-weight:700;margin:0;text-align:left">Evaluasi</p>
        <div style="text-align:right;margin-left:auto">
            <p style="font-weight:700;font-size:0.875rem">{{ $report->gerai->kode_gerai }}</p>
            <p style="font-size:0.75rem;color:#C4B5FD;margin-top:0.125rem">{{ $report->gerai->nama_gerai }}</p>
            @if ($report->gerai->nama_kota)
            <p style="font-size:0.75rem;color:#C4B5FD;margin-top:0.125rem">{{ $report->gerai->nama_kota }}</p>
            @endif
            <p style="font-size:0.75rem;color:#C4B5FD;margin-top:0.25rem">{{ $report->created_at->format('d-m-Y') }}</p>
        </div>
    </div>

    {{-- Laporan Sebelumnya --}}
    @if ($lastReport && $lastReport->finding)
    @php $f = $lastReport->finding; @endphp
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 3px rgba(0,0,0,.05);border:1px solid #f3f4f6;padding:1rem;margin-bottom:1.25rem">
        <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;color:#9CA3AF;font-weight:600;margin:0 0 0.75rem">{{ str_replace('-', ' ', ucfirst(match(class_basename($lastReport)) { 'ReMonitoringReport' => 're-monitoring', default => $lastReport->type ?? 'monitoring', })) }} — {{ $lastReport->checkin_at->format('d-m-Y') }} — {{ $lastReport->user?->name ?? '-' }} — Nilai: {{ $lastReport->nilai ?? '-' }} ({{ $lastReport->grade ?? '-' }})</p>

        @if ($f->major)
        <div style="margin-bottom:0.75rem">
            <p style="font-size:0.7rem;font-weight:700;color:#111827;margin:0 0 0.25rem">Mayor</p>
            <p style="font-size:0.8rem;color:#374151;margin:0;white-space:pre-wrap;word-break:break-word">{{ $f->major }}</p>
        </div>
        @endif

        @if ($f->minor)
        <div style="margin-bottom:0.75rem">
            <p style="font-size:0.7rem;font-weight:700;color:#111827;margin:0 0 0.25rem">Minor</p>
            <p style="font-size:0.8rem;color:#374151;margin:0;white-space:pre-wrap;word-break:break-word">{{ $f->minor }}</p>
        </div>
        @endif

        @if ($f->peringatan_awal)
        <div style="margin-bottom:0.75rem">
            <p style="font-size:0.7rem;font-weight:700;color:#111827;margin:0 0 0.25rem">Peringatan Awal</p>
            <p style="font-size:0.8rem;color:#374151;margin:0;white-space:pre-wrap;word-break:break-word">{{ $f->peringatan_awal }}</p>
        </div>
        @endif

        @if ($f->note)
        <div style="margin-bottom:0.75rem">
            <p style="font-size:0.7rem;font-weight:700;color:#111827;margin:0 0 0.25rem">Note</p>
            <p style="font-size:0.8rem;color:#374151;margin:0;white-space:pre-wrap;word-break:break-word">{{ $f->note }}</p>
        </div>
        @endif

        @if ($f->kondisi_cat || $f->kondisi_awning || $f->kondisi_vinyl || $f->kondisi_stiker_kaca)
        <div>
            <p style="font-size:0.7rem;font-weight:700;color:#111827;margin:0 0 0.25rem">Checklist Kondisi Gerai</p>
            <p style="font-size:0.8rem;color:#374151;margin:0">Kondisi cat: {{ $f->kondisi_cat ?: 'Baik' }}</p>
            <p style="font-size:0.8rem;color:#374151;margin:0">Kondisi awning: {{ $f->kondisi_awning ?: 'Baik' }}</p>
            <p style="font-size:0.8rem;color:#374151;margin:0">Kondisi vinyl reklame dinding/jalan: {{ $f->kondisi_vinyl ?: 'Baik' }}</p>
            <p style="font-size:0.8rem;color:#374151;margin:0">Kondisi stiker kaca: {{ $f->kondisi_stiker_kaca ?: 'Baik' }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Form --}}
    <form id="assessment-form" method="POST" action="/{{ $prefix }}/{{ $report->id }}/assessment">
        @csrf

        {{-- Catatan --}}
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 3px rgba(0,0,0,.05);border:1px solid #f3f4f6;padding:1rem;margin-bottom:0.75rem">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:0.5rem">Catatan</label>
            <textarea name="catatan" id="catatan" rows="5" style="width:100%;padding:0.625rem;border:1px solid #e5e7eb;border-radius:0.75rem;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" placeholder="Tulis catatan evaluasi...">{{ old('catatan', $report->catatan ?? '') }}</textarea>
        </div>

        {{-- Keterangan --}}
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 3px rgba(0,0,0,.05);border:1px solid #f3f4f6;padding:1rem;margin-bottom:1.5rem">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:0.5rem">Keterangan</label>
            <textarea name="keterangan" id="keterangan" rows="5" style="width:100%;padding:0.625rem;border:1px solid #e5e7eb;border-radius:0.75rem;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" placeholder="Tulis keterangan evaluasi...">{{ old('keterangan', $report->keterangan ?? '') }}</textarea>
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:0.75rem">
            @if ($report->tanggal)
            <button type="button" onclick="showConfirm('Yakin ingin batalkan perubahan?', function(){ document.getElementById('cancel-form').submit(); })" style="flex:1;padding:0.75rem;background:#fff;border:1px solid #e5e7eb;color:#374151;font-size:0.875rem;font-weight:600;border-radius:0.75rem;cursor:pointer;transition:all 0.15s ease" onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'" onmousedown="this.style.transform='scale(0.97)';this.style.background='#E5E7EB'" onmouseup="this.style.transform='';this.style.background='#F3F4F6'">Batalkan</button>
            @else
            <button type="button" onclick="showConfirm('Batalkan laporan ini? Laporan akan dihapus.', function(){ document.getElementById('delete-form').submit(); })" style="flex:1;padding:0.75rem;background:#fff;border:1px solid #e5e7eb;color:#374151;font-size:0.875rem;font-weight:600;border-radius:0.75rem;cursor:pointer;transition:all 0.15s ease" onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'" onmousedown="this.style.transform='scale(0.97)';this.style.background='#E5E7EB'" onmouseup="this.style.transform='';this.style.background='#F3F4F6'">Batalkan</button>
            @endif
            <button type="button" onclick="submitEvaluasi()" style="flex:1;padding:0.75rem;background:#3B82F6;color:#fff;font-size:0.875rem;font-weight:600;border-radius:0.75rem;border:none;cursor:pointer;transition:all 0.15s ease" onmouseover="this.style.background='#2563EB'" onmouseout="this.style.background='#3B82F6'" onmousedown="this.style.transform='scale(0.97)';this.style.background='#1D4ED8'" onmouseup="this.style.transform='';this.style.background='#2563EB'">Simpan</button>
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
var poin1 = '1. Poin kinerja di gerai ' + kodeGerai + ' berada di atas Rerata pada ' + periodeLabel1;
if (belowAvgCount > 0) {
    poin1 += ' dan pernah ' + belowAvgCount + 'x berada di bawah Rerata pada monitoring periode sebelumnya.';
} else {
    poin1 += '.';
}
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
        window.location.replace('/{{ $prefix }}/{{ $report->id }}');
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
