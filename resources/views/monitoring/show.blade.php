@extends('layouts.admin')

@section('title', 'Laporan - ' . $report->gerai->nama_gerai)

@section('content')
<div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div>
        @php
            $reportListUrl = match($prefix) {
                'pra-monitoring' => '/report/pra-monitoring',
                're-monitoring' => '/report/re-monitoring',
                default => '/report/monitoring',
            };
        @endphp
        <a href="{{ $reportListUrl }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke Daftar Laporan</a>
        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mt-1">{{ $report->gerai->kode_gerai }} - {{ $report->gerai->nama_gerai }}
            @if($report->is_pairing)
                <span style="background:#6B7280;color:#fff;font-size:11px;padding:2px 8px;border-radius:4px;margin-left:6px;vertical-align:middle;">Pairing</span>
            @endif
        </h2>
    </div>
    <div class="flex gap-2">
    </div>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Informasi Laporan</h3>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full">
        <tbody class="divide-y divide-gray-200">
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500 w-1/3">Kode Gerai</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->gerai->kode_gerai }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Nama Gerai</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->gerai->nama_gerai }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Franchisee</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->gerai->franchisee }}</td>
            </tr>

            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Lokasi Checkin</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->location }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Checkin</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->checkin_at->format('d-m-Y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Submit</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->submit_at ? $report->submit_at->format('d-m-Y H:i:s') : '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Petugas</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->user?->name ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Total Nilai</h3>
    </div>
    <div class="p-4 sm:p-5">
        <p class="text-2xl font-bold text-blue-600">{{ $totalScore }}</p>
        @php $grade = \App\Models\MonitoringReport::gradeFromScore((float) $totalScore); @endphp
        <p class="text-lg font-semibold {{ $grade === 'A' ? 'text-green-600' : ($grade === 'B' ? 'text-blue-600' : ($grade === 'C' ? 'text-yellow-600' : ($grade === 'D' ? 'text-orange-500' : 'text-red-600'))) }}">Grade: {{ $grade }}</p>
    </div>
</div>

@if ($report->major || $report->minor)
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700">{{ $prefix === 'evaluasi' ? 'Temuan Evaluasi' : ($prefix === 'pra-monitoring' ? 'Temuan Pra-Monitoring' : ($prefix === 're-monitoring' ? 'Temuan Re-Monitoring' : 'Temuan Monitoring')) }}</h3>
        </div>
        <div class="p-4 sm:p-5 space-y-3">
            @if ($report->major)
                <div>
                    <p class="text-xs font-medium text-gray-500">Major</p>
                    <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ e($report->major) }}</p>
                </div>
            @endif
            @if ($report->minor)
                <div>
                    <p class="text-xs font-medium text-gray-500">Minor</p>
                    <p class="text-sm text-gray-800 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ e($report->minor) }}</p>
                </div>
            @endif
            @if ($report->pengawas || $report->rata_rata_aj || ($report->tds && $prefix !== 'pra-monitoring') || $report->mesin_ozon || $report->peringatan_awal || $report->note || $report->kondisi_cat || $report->kondisi_awning || $report->kondisi_vinyl || $report->kondisi_stiker_kaca)
                <div class="text-sm text-gray-800">
                    <p class="text-xs font-medium text-gray-500 mb-1">Peringatan Awal:</p>
                    @if ($report->pengawas)
                        <div class="my-0 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ e($report->pengawas) }}</div>
                    @endif
                    @if ($report->rata_rata_aj)
                        <p class="my-0">Rerata AJ ± {{ $report->rata_rata_aj }} gln/hr</p>
                    @endif
                    @if ($report->tds && $prefix !== 'pra-monitoring')
                        @php $tdsDisplay = str_replace('/', ' ppm/', $report->tds) . (str_contains($report->tds, '/') ? '°C' : ''); @endphp
                        <p class="my-0">TDS: {{ $tdsDisplay }}</p>
                    @endif
                    @if ($report->mesin_ozon)
                        <p class="my-0">MO: {{ $report->mesin_ozon }}</p>
                    @endif
                    @if ($report->peringatan_awal)
                        <div class="mt-4 mb-0 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ e($report->peringatan_awal) }}</div>
                    @endif
                    @if ($report->note)
                        <p class="mt-4 mb-0">Note:</p>
                        <div class="my-0 whitespace-pre-wrap" style="overflow-wrap: break-word; word-break: break-word;">{{ e($report->note) }}</div>
                    @endif
                    @if ($report->kondisi_cat || $report->kondisi_awning || $report->kondisi_vinyl || $report->kondisi_stiker_kaca)
                        <p class="mt-4 mb-0">Checklist tampilan gerai:</p>
                        <p class="my-0">Kondisi cat: {{ $report->kondisi_cat ?: 'Baik' }}</p>
                        <p class="my-0">Kondisi awning: {{ $report->kondisi_awning ?: 'Baik' }}</p>
                        <p class="my-0">Kondisi vinyl reklame dinding/jalan: {{ $report->kondisi_vinyl ?: 'Baik' }}</p>
                        <p class="my-0">Kondisi stiker kaca: {{ $report->kondisi_stiker_kaca ?: 'Baik' }}</p>
                    @endif
                </div>
            @endif
            @php
                $penjelasanIsi = $report->penjelasan_isi ?? [];
            @endphp
            @if ($prefix !== 'pra-monitoring' && !empty(array_filter($penjelasanIsi)))
                <div class="space-y-2 mb-4">
                    <p class="text-xs font-medium text-gray-500">Penjelasan Formulir 2</p>
                    @foreach ($penjelasanIsi as $i => $teks)
                        @if (trim($teks))
                            <p class="text-sm text-gray-800">{{ $i + 1 }}. {{ $teks }}</p>
                        @endif
                    @endforeach
                </div>
            @endif
            @php
                $penjelasanIsi3 = $report->penjelasan_isi_3 ?? [];
                $showF3 = false;
                if (!empty($penjelasanIsi3) && is_array($penjelasanIsi3)) {
                    foreach ($filteredCategories as $cat) {
                        foreach ($cat->items as $item) {
                            if (!$item->bobot || $item->criteria->count() <= 1) continue;
                            $r = $results->get($item->id);
                            if (!$r || !$r->criterion_id) continue;
                            $idx = $item->criteria->search(fn($c) => $c->id === $r->criterion_id);
                            if ($idx === $item->criteria->count() - 1 && !empty(trim($penjelasanIsi3[$item->id] ?? ''))) {
                                $showF3 = true;
                                break 2;
                            }
                        }
                    }
                }
            @endphp
            @if ($showF3)
                <div class="space-y-2 mb-4">
                    <p class="text-xs font-medium text-gray-500">{{ $prefix === 'pra-monitoring' ? 'Penjelasan' : 'Penjelasan Formulir 3' }}</p>
                    @foreach ($penjelasanIsi3 as $itemId => $teks)
                        @if (trim($teks))
                            <p class="text-sm text-gray-800">{{ $loop->iteration }}. {{ $teks }}</p>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-medium mb-1">TTD Petugas</p>
                    @if ($report->ttd_petugas)
                        <img src="{{ asset('storage/' . $report->ttd_petugas) }}" class="h-16 w-auto rounded border">
                    @else
                        <p class="text-sm italic">Belum ada</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium mb-1">TTD Pimpinan</p>
                    @if ($report->ttd_pimpinan)
                        <img src="{{ asset('storage/' . $report->ttd_pimpinan) }}" class="h-16 w-auto rounded border ml-auto">
                    @else
                        <p class="text-sm italic">Belum ada</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@forelse ($filteredCategories as $cat)
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">{{ $cat->name }}</h3>
        </div>

        @if ($cat->items->isNotEmpty())
            <div class="divide-y divide-gray-100">
                @foreach ($cat->items as $item)
                    @php $r = $results->get($item->id) @endphp
                    @php
                        $count = $item->criteria->count();
                        $interval = $item->bobot && $count > 1 ? $item->bobot / ($count - 1) : null;
                    @endphp
                    <div class="p-4 sm:p-5">
                        <p class="text-sm font-medium text-gray-800">{{ $loop->iteration }}. {{ $item->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Nilai: <strong class="text-blue-600">{{ $r && $r->criterion ? $r->criterion->description : '-' }}</strong>
                            @if ($r && $r->criterion && $interval !== null)
                                @php $selectedIdx = $item->criteria->search(fn($c) => $c->id === $r->criterion_id) @endphp
                                @if ($selectedIdx !== false)
                                    <span class="text-gray-400 ml-1">({{ $item->bobot - ($interval * $selectedIdx) }})</span>
                                @endif
                            @endif
                        </p>
                        @if ($r && $r->notes)
                            <p class="text-xs text-gray-400 mt-0.5">Catatan: {{ $r->notes }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Belum ada item.</div>
        @endif
    </div>
@empty
    <div class="bg-white rounded-xl shadow-md p-8 text-center text-sm text-gray-500">
        Belum ada kategori penilaian.
    </div>
@endforelse

@if ($prefix !== 'evaluasi')
{{-- FAB Speed Dial --}}
<div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
    <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
        <button onclick="showPdfModal(); closeFab()"
            class="w-12 h-12 bg-red-600 text-white rounded-full shadow-lg hover:bg-red-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">PDF</span>
        </button>
        <a href="/{{ $prefix }}/{{ $report->id }}/excel" target="_blank" onclick="closeFab()"
            class="w-12 h-12 bg-orange-600 text-white rounded-full shadow-lg hover:bg-orange-700 flex items-center justify-center text-xs font-medium relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Excel</span>
        </a>
        <button onclick="showWaModal(); closeFab()"
            class="w-12 h-12 bg-green-500 text-white rounded-full shadow-lg hover:bg-green-600 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">WhatsApp</span>
        </button>
        @if ($report->user_id === auth()->id())
        @if ($report->isLocked())
        <span class="w-12 h-12 bg-gray-400 text-white rounded-full shadow-lg flex items-center justify-center text-xs font-medium relative cursor-not-allowed" title="Sedang diedit oleh {{ $report->editingUser?->name ?? 'User lain' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Diedit oleh {{ $report->editingUser?->name ?? 'User lain' }}</span>
        </span>
        @elseif ($report->editing_user_id === auth()->id())
        <a href="/{{ $prefix }}/{{ $report->id }}/assessment" onclick="closeFab()"
            class="w-12 h-12 bg-yellow-500 text-white rounded-full shadow-lg hover:bg-yellow-600 flex items-center justify-center text-xs font-medium relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Lanjutkan Edit</span>
        </a>
        @else
        <a href="/{{ $prefix }}/{{ $report->id }}/assessment" onclick="closeFab()"
            class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 flex items-center justify-center text-xs font-medium relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Edit</span>
        </a>
        @endif
        @endif
    </div>
    <button id="fabToggle"
        style="background:#3B82F6;color:#FFFFFF"
        class="w-14 h-14 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center transition-transform duration-200">
        <svg id="fabIcon" class="w-7 h-7 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
    </button>
</div>
@endif

@if ($prefix !== 'evaluasi')
<script>
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
</script>
@endif

@if ($prefix !== 'evaluasi')
{{-- PDF Modal --}}
<div id="pdfModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="fixed inset-0 bg-black/50" onclick="closePdfModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-1">Pilih Opsi PDF</h3>
        <p class="text-xs text-gray-500 mb-4">Apakah laporan ini perlu ditandai sebagai revisi?</p>
        <div class="mt-4 flex justify-end gap-3">
            <button onclick="downloadPdf(1)" style="background:#800000;color:#fff" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-90">Revisi</button>
            <button onclick="downloadPdf(0)" style="background:#15803d;color:#fff" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-90">Tidak Revisi</button>
        </div>
    </div>
</div>

{{-- WhatsApp Modal --}}
<div id="waModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="fixed inset-0 bg-black/50" onclick="closeWaModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-1">Kirim via WhatsApp</h3>
        <p class="text-xs text-gray-500 mb-4">Ketik nama gerai atau franchisee, lalu pilih nomornya.</p>
        <div class="relative mb-3">
            <input type="text" id="waSearch" placeholder="Cari gerai..." autocomplete="off"
                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            <div id="waDropdown" class="absolute z-10 w-full bg-white border border-gray-200 rounded-xl mt-1 max-h-48 overflow-y-auto hidden shadow-lg"></div>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Nomor Telepon</label>
            <input type="text" id="waNumber" placeholder="628xxxxxxxx"
                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
        </div>
        <p id="waError" class="text-xs text-red-500 mt-1 hidden">Nomor tidak valid. Gunakan format: 628xx...</p>
        <div class="mt-4 flex justify-end gap-3">
            <button onclick="closeWaModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Batal</button>
            <button onclick="sendWa()" class="px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600">Kirim</button>
        </div>
    </div>
</div>
@endif

@endsection

@if ($prefix !== 'evaluasi')
@push('scripts')
<script>
var geraiName = '{{ $report->gerai->kode_gerai }} - {{ $report->gerai->nama_gerai }}';
var tgl = '{{ $report->checkin_at->format("d-m-Y") }}';
var prefix = '{{ ucfirst(str_replace("-", " ", $prefix)) }}';

var allGerais = {!! json_encode($allGerais->map(fn($g) => [
    'kode' => $g->kode_gerai,
    'nama' => $g->nama_gerai,
    'franchisee' => $g->franchisee ?? '',
    'no_telepon' => $g->no_telepon ?? '',
    'tipe' => 'Gerai',
]), JSON_HEX_TAG) !!};

var allPgs = {!! json_encode($allPgs->map(fn($p) => [
    'kode' => '',
    'nama' => $p->nama_pg,
    'franchisee' => $p->kota ?? '',
    'no_telepon' => $p->no_telepon ?? '',
    'tipe' => 'PG',
]), JSON_HEX_TAG) !!};

var allContacts = allGerais.concat(allPgs);

var pdfUrl = '/{{ $prefix }}/{{ $report->id }}/pdf';

function showPdfModal() {
    document.getElementById('pdfModal').classList.remove('hidden');
}

function closePdfModal() {
    document.getElementById('pdfModal').classList.add('hidden');
}

function downloadPdf(revisi) {
    closePdfModal();
    window.open(pdfUrl + (revisi ? '?revisi=1' : ''), '_blank');
}

function showWaModal() {
    document.getElementById('waModal').classList.remove('hidden');
    document.getElementById('waSearch').value = '';
    document.getElementById('waNumber').value = '';
    document.getElementById('waDropdown').classList.add('hidden');
    document.getElementById('waError').classList.add('hidden');
    setTimeout(function(){ document.getElementById('waSearch').focus(); }, 100);
}

function closeWaModal() {
    document.getElementById('waModal').classList.add('hidden');
}

function filterWaGerai(q) {
    var dropdown = document.getElementById('waDropdown');
    q = q.toLowerCase().trim();
    if (!q) { dropdown.classList.add('hidden'); return; }

    var matches = allContacts.filter(function(g) {
        return g.nama.toLowerCase().includes(q) || g.kode.toLowerCase().includes(q) || g.franchisee.toLowerCase().includes(q);
    });

    if (matches.length === 0) { dropdown.classList.add('hidden'); return; }

    dropdown.innerHTML = matches.map(function(g) {
        var display = g.tipe + ': ';
        if (g.kode) display += g.kode + ' - ';
        display += g.nama;
        if (g.franchisee) display += ' (' + g.franchisee + ')';
        var nomor = g.no_telepon;
        return '<div class="px-3 py-2 hover:bg-green-50 cursor-pointer text-sm" onclick="selectWaGerai(this)" data-number="' + nomor + '">' + display + '</div>';
    }).join('');
    dropdown.classList.remove('hidden');
}

function selectWaGerai(el) {
    document.getElementById('waSearch').value = el.textContent;
    document.getElementById('waNumber').value = el.dataset.number;
    document.getElementById('waDropdown').classList.add('hidden');
}

document.getElementById('waSearch').addEventListener('input', function() {
    filterWaGerai(this.value);
});

function sendWa() {
    var number = document.getElementById('waNumber').value.trim().replace(/[^0-9]/g, '');
    if (number.length < 8) {
        document.getElementById('waError').classList.remove('hidden');
        return;
    }
    document.getElementById('waError').classList.add('hidden');
    closeWaModal();
    var text = 'Laporan ' + prefix + ' Gerai ' + geraiName + ' Tanggal ' + tgl;
    window.open('https://wa.me/' + number + '?text=' + encodeURIComponent(text), '_blank');
}
</script>
@endpush
@endif
