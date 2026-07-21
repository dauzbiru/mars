@extends('layouts.admin')

@section('title', 'Buat Laporan - Pilih Gerai')

@section('content')
<div class="bg-white rounded-xl shadow-md">
    <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800">Buat {{ $prefix === 'pra-monitoring' ? 'Pra-Monitoring' : ($prefix === 're-monitoring' ? 'Re-Monitoring' : ($prefix === 'evaluasi' ? 'Evaluasi' : 'Monitoring')) }}</h2>
        <div class="relative flex items-center">
            <input type="text" id="searchGerai" placeholder="Cari gerai..."
                class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                autocomplete="off" oninput="filterGerai(this.value)">
            <button type="button" onclick="toggleSearch('searchGerai', this)" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
            <ul id="selectGeraiSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
        </div>
    </div>

    <div class="max-h-[calc(100vh-200px)] overflow-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                    <th class="px-4 sm:px-6 py-3">Kode</th>
                    <th class="px-4 sm:px-6 py-3">Nama Gerai</th>
                    <th class="px-4 sm:px-6 py-3"></th>
                </tr>
            </thead>
            <tbody id="geraiTableBody" class="divide-y divide-gray-200">
                @forelse ($gerais as $g)
                    @if (in_array($g->id, $todayReportGeraiIds))
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-400">{{ $g->kode_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-400">{{ $g->nama_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-right"><span class="text-xs text-gray-400">Sudah</span></td>
                        </tr>
                    @elseif (isset($pendingByOthers[$g->id]))
                        <tr class="hover:bg-gray-50 bg-orange-50">
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-orange-400">{{ $g->kode_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-orange-400">{{ $g->nama_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-right"><span class="text-xs text-orange-500 font-medium">Sedang dikerjakan: {{ $pendingByOthers[$g->id] }}</span></td>
                        </tr>
                    @else
                        @if ($prefix === 'evaluasi')
                        <tr class="hover:bg-blue-50 active:bg-blue-200 cursor-pointer transition-colors" onclick="showConfirm('Buat laporan evaluasi untuk {{ $g->kode_gerai }} - {{ $g->nama_gerai }}?', function(){ window.location='/{{ $prefix }}/checkin/{{ $g->id }}'; })">
                        @else
                        <tr class="hover:bg-blue-50 active:bg-blue-200 cursor-pointer transition-colors" onclick="window.location='/{{ $prefix }}/checkin/{{ $g->id }}'">
                        @endif
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-800">{{ $g->kode_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $g->nama_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-right"><span class="text-blue-600 text-xs font-medium">&rarr;</span></td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="3" class="px-4 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada data gerai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
var suggestData = {!! json_encode($gerais->map(fn($g) => ['search' => $g->kode_gerai . ' ' . $g->nama_gerai, 'primary' => $g->kode_gerai, 'secondary' => $g->nama_gerai]), JSON_HEX_TAG) !!};

function filterGerai(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#geraiTableBody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
    var list = document.getElementById('selectGeraiSuggest');
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
            document.getElementById('searchGerai').value = item.primary;
            list.classList.add('hidden');
            filterGerai(item.primary);
        });
        list.appendChild(li);
    });
    var btn = document.getElementById('searchGerai').parentElement.querySelector('button');
    positionSuggest(btn, 'selectGeraiSuggest');
    list.classList.remove('hidden');
}

document.getElementById('searchGerai').addEventListener('blur', function() {
    setTimeout(function() { document.getElementById('selectGeraiSuggest').classList.add('hidden'); }, 200);
});
</script>
@endsection
