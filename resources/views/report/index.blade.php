@extends('layouts.admin')

@section('title', 'Laporan - MARS')

@section('content')
<div class="bg-white rounded-xl shadow-md">
    <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">{{ $title }}</h2>
        <div class="relative flex items-center shrink-0">
            <input type="text" id="searchLaporan" placeholder="Cari gerai atau petugas..."
                class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                autocomplete="off" oninput="filterLaporan(this.value)">
            <button type="button" onclick="toggleSearch('searchLaporan', this)" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
            <ul id="laporanSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
        </div>
    </div>

    @if ($reports->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full min-w-[600px]">
            <thead>
                <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                    <th class="px-4 sm:px-6 py-3">Gerai</th>
                    <th class="px-4 sm:px-6 py-3">Petugas</th>
                    @if ($type === 'pra-monitoring' || $type === 're-monitoring')
                    <th class="px-4 sm:px-6 py-3">Bulan/Tahun</th>
                    @elseif ($type !== 'evaluasi')
                    <th class="px-4 sm:px-6 py-3">Periode</th>
                    @endif
                    @if ($type === 'evaluasi')
                    <th class="px-4 sm:px-6 py-3">Tanggal</th>
                    @else
                    <th class="px-4 sm:px-6 py-3">Checkin</th>
                    <th class="px-4 sm:px-6 py-3">Submit</th>
                    @endif
                    @if ($type !== 'evaluasi')
                    <th class="px-4 sm:px-6 py-3">Total</th>
                    <th class="px-4 sm:px-6 py-3">Grade</th>
                    @endif
                    <th class="px-4 sm:px-6 py-3"></th>
                </tr>
            </thead>
            <tbody id="laporanTableBody" class="divide-y divide-gray-200">
                @foreach ($reports as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">
                            <span class="font-medium">{{ $r->gerai->kode_gerai }}</span> - {{ $r->gerai->nama_gerai }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $r->user?->name ?? '-' }}</td>
                        @if ($type === 'pra-monitoring' || $type === 're-monitoring')
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $r->checkin_at->locale('id')->isoFormat('MMMM YYYY') }}</td>
                        @elseif ($type !== 'evaluasi')
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $r->periode_label ?? '-' }}</td>
                        @endif
                        @if ($type === 'evaluasi')
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $r->tanggal ? $r->tanggal->format('d-m-Y') : '-' }}</td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">-</td>
                        @else
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $r->checkin_at->format('d-m-Y H:i') }}</td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $r->submit_at ? $r->submit_at->format('d-m-Y H:i') : '-' }}</td>
                        @endif
                        @if ($type !== 'evaluasi')
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-blue-600 font-semibold">{{ $r->total_score ?? '-' }}</td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-semibold {{ $r->grade === 'A' ? 'text-green-600' : ($r->grade === 'B' ? 'text-blue-600' : ($r->grade === 'C' ? 'text-yellow-600' : ($r->grade === 'D' ? 'text-orange-500' : ($r->grade === 'E' ? 'text-red-600' : '')))) }}">{{ $r->grade ?? '-' }}</td>
                        @endif
                        <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">
                            @php
                                $rPrefix = match(class_basename($r)) {
                                    'PraMonitoringReport' => 'pra-monitoring',
                                    'ReMonitoringReport' => 're-monitoring',
                                    'EvaluasiReport' => 'evaluasi',
                                    default => $type,
                                };
                            @endphp
                            @if (in_array($type, ['monitoring', 'pra-monitoring', 're-monitoring']))
                            <a href="/{{ $rPrefix }}/{{ $r->id }}/pdf?excel=1"
                                style="background:#F3E8FF;color:#7C3AED" class="inline-block px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80">PDF</a>
                            @endif
                            <a href="/{{ $rPrefix }}/{{ $r->id }}"
                                style="background:#DBEAFE;color:#2563EB" class="inline-block px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80">Detail</a>
                            <form method="POST" action="/{{ $rPrefix }}/{{ $r->id }}" onsubmit="showConfirm('Hapus laporan ini?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                @csrf @method('DELETE')<input type="hidden" name="_from" value="list">
                                <button style="background:#FEE2E2;color:#DC2626" class="inline-block px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center text-sm text-gray-500">Belum ada {{ $title }}.</div>
    @endif

    <div class="px-4 sm:px-6 py-3 border-t border-gray-200">
        {{ $reports->appends(request()->query())->links('pagination::tailwind') }}
    </div>
</div>

@if (auth()->user()->role !== 'guest' && $type !== 'evaluasi')
{{-- FAB Speed Dial --}}
<div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
    <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
        <button onclick="document.getElementById('modalAmbilData').classList.remove('hidden'); closeFab()"
            class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Checkin</span>
        </button>
        @if ($type !== 'pra-monitoring' && $type !== 're-monitoring')
        <button onclick="document.getElementById('modalChecklistTidakSempurna').classList.remove('hidden'); closeFab()"
            class="w-12 h-12 bg-orange-600 text-white rounded-full shadow-lg hover:bg-orange-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Checklist</span>
        </button>
        @endif
        <button onclick="document.getElementById('modalExportAllExcel').classList.remove('hidden'); closeFab()"
            style="background:#ECFDF5;color:#059669" class="w-12 h-12 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Export All Excel</span>
        </button>
        @if (in_array($type, ['monitoring', 'pra-monitoring', 're-monitoring']))
        <button onclick="document.getElementById('modalExportAllPdf').classList.remove('hidden'); closeFab()"
            style="background:#FEE2E2;color:#DC2626" class="w-12 h-12 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Export All PDF</span>
        </button>
        @endif
        @if ($type !== 're-monitoring')
        <button onclick="document.getElementById('modalExcelDetail').classList.remove('hidden'); closeFab()"
            class="w-12 h-12 bg-teal-600 text-white rounded-full shadow-lg hover:bg-teal-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Detail Excel</span>
        </button>
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

<div id="modalAmbilData" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Data Checkin & Submit</h3>
        <form method="GET" action="/report/ambil-data">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Periode</label>
                <select name="periode_label" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach ($periods as $p)
                        <option value="{{ $p->label }}">{{ $p->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalAmbilData').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" onclick="this.closest('[id^=modal]').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Ambil</button>
            </div>
        </form>
    </div>
</div>

<div id="modalChecklistTidakSempurna" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Checklist Tidak Sempurna</h3>
        <p class="text-xs text-gray-500 mb-4">Pilih {{ in_array($type, ['pra-monitoring', 're-monitoring']) ? 'bulan' : 'periode' }} untuk mengekspor data checklist yang nilainya tidak sempurna (tidak mencapai kriteria terbaik).</p>
        <form method="GET" action="/report/checklist-tidak-sempurna">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="mb-4">
                @if (in_array($type, ['pra-monitoring', 're-monitoring']))
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <input type="month" name="month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                @else
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode Semester</label>
                <select name="periode_label" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    @foreach ($periods as $p)
                        <option value="{{ $p->label }}">{{ $p->label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalChecklistTidakSempurna').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" onclick="this.closest('[id^=modal]').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">Export Excel</button>
            </div>
        </form>
    </div>
</div>

<div id="modalExportAllExcel" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Export Semua Laporan Excel</h3>
        <p class="text-xs text-gray-500 mb-4">Pilih {{ $type === 'pra-monitoring' || $type === 're-monitoring' ? 'bulan' : 'periode' }} untuk mendownload semua laporan Excel dalam satu file ZIP.</p>
        <form method="GET" action="/report/export-all-excel">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="mb-4">
                @if ($type === 'pra-monitoring' || $type === 're-monitoring')
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <input type="month" name="month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                @else
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode Semester</label>
                <select name="periode_label" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @foreach ($periods as $p)
                        <option value="{{ $p->label }}">{{ $p->label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalExportAllExcel').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" onclick="this.closest('[id^=modal]').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">Download ZIP</button>
            </div>
        </form>
    </div>
</div>

<div id="modalExportAllPdf" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Export Semua Laporan PDF</h3>
        <p class="text-xs text-gray-500 mb-4">Pilih {{ in_array($type, ['pra-monitoring', 're-monitoring']) ? 'bulan' : 'periode' }} untuk mendownload semua laporan PDF (konversi dari Excel) dalam satu file ZIP.</p>
        <form method="GET" action="/report/export-all-pdf">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="mb-4">
                @if (in_array($type, ['pra-monitoring', 're-monitoring']))
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <input type="month" name="month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                @else
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode Semester</label>
                <select name="periode_label" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                    @foreach ($periods as $p)
                        <option value="{{ $p->label }}">{{ $p->label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalExportAllPdf').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" onclick="this.closest('[id^=modal]').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Download ZIP</button>
            </div>
        </form>
    </div>
</div>

<div id="modalExcelDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Download Detail Excel</h3>
        <p class="text-xs text-gray-500 mb-4">Pilih {{ $type === 'pra-monitoring' ? 'bulan' : 'periode' }} untuk mendownload detail laporan per sheet Excel.</p>
        <form method="GET" action="/report/excel-detail">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="mb-4">
                @if ($type === 'pra-monitoring')
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <input type="month" name="month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                @else
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode Semester</label>
                <select name="periode_label" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    @foreach ($periods as $p)
                        <option value="{{ $p->label }}">{{ $p->label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalExcelDetail').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" onclick="this.closest('[id^=modal]').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700">Download</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
var suggestData = {!! json_encode($gerais->map(fn($g) => ['search' => $g->kode_gerai . ' ' . $g->nama_gerai, 'primary' => $g->kode_gerai, 'secondary' => $g->nama_gerai]), JSON_HEX_TAG) !!};

function filterLaporan(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#laporanTableBody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
    var list = document.getElementById('laporanSuggest');
    list.innerHTML = '';
    if (!q) { list.classList.add('hidden'); return; }
    var matches = suggestData.filter(function(item) {
        return item.search.toLowerCase().includes(q);
    }).slice(0, 8);
    if (matches.length === 0) { list.classList.add('hidden'); return; }
    matches.forEach(function(item) {
        var li = document.createElement('li');
        li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
        li.innerHTML = '<span class="font-medium text-gray-800">' + item.primary + '</span>' + (item.secondary ? '<span class="text-gray-500"> - ' + item.secondary + '</span>' : '');
        li.addEventListener('mousedown', function(e) {
            e.preventDefault();
            document.getElementById('searchLaporan').value = item.primary;
            list.classList.add('hidden');
            filterLaporan(item.primary);
        });
        list.appendChild(li);
    });
    var btn = document.getElementById('searchLaporan').parentElement.querySelector('button');
    positionSuggest(btn, 'laporanSuggest');
    list.classList.remove('hidden');
}

document.getElementById('searchLaporan').addEventListener('blur', function() {
    setTimeout(function() { document.getElementById('laporanSuggest').classList.add('hidden'); }, 200);
});
</script>

@endsection
