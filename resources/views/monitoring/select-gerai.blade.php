@extends('layouts.admin')

@section('title', 'Buat Laporan - Pilih Gerai')

@section('content')
<div class="bg-white rounded-xl shadow-md">
    <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">Buat {{ $prefix === 'pra-monitoring' ? 'Pra-Monitoring' : ($prefix === 're-monitoring' ? 'Re-Monitoring' : ($prefix === 'evaluasi' ? 'Evaluasi' : 'Monitoring')) }}</h2>
            @if ($prefix === 'monitoring' || $prefix === 'pra-monitoring')
            <span class="text-xs font-medium text-gray-500">Pairing</span>
            <button type="button" id="toggleAllGerai" onclick="toggleGeraiFilter()" style="position:relative;display:inline-flex;height:24px;width:48px;align-items:center;border-radius:9999px;background:#D1D5DB;transition:background 0.2s;cursor:pointer;border:none;padding:0;outline:none;">
                <span id="toggleLabelOff" style="position:absolute;right:7px;font-size:10px;font-weight:700;color:#fff;line-height:24px;pointer-events:none;transition:opacity 0.2s;">OFF</span>
                <span id="toggleLabelOn" style="position:absolute;left:7px;font-size:10px;font-weight:700;color:#fff;line-height:24px;pointer-events:none;opacity:0;transition:opacity 0.2s;">ON</span>
                <span id="toggleDot" style="position:absolute;left:2px;display:inline-block;height:20px;width:20px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);transition:transform 0.2s;transform:translateX(0);"></span>
            </button>
            @endif
        </div>
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
        <table class="w-full" style="table-layout:fixed;">
            <colgroup>
                <col style="width:120px;">
                <col>
                <col>
            </colgroup>
            <thead>
                <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                    <th class="px-4 sm:px-6 py-3">Kode</th>
                    <th class="px-4 sm:px-6 py-3">Nama Gerai</th>
                    <th class="px-4 sm:px-6 py-3"></th>
                </tr>
            </thead>
            <tbody id="geraiTableBody" class="divide-y divide-gray-200">
                @forelse ($gerais as $g)
                    @if (isset($pendingByOthers[$g->id]))
                        <tr class="hover:bg-gray-50 bg-orange-50" data-active="{{ $g->is_active ? '1' : '0' }}" data-pending="1" data-pending-user="{{ $pendingByOthers[$g->id] }}" data-gerai-id="{{ $g->id }}">
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-orange-400">{{ $g->kode_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-orange-400">{{ $g->nama_gerai }}</td>
                            <td class="px-4 sm:px-6 py-3 text-right"><span class="text-xs text-orange-500 font-medium pending-label">Sedang dikerjakan: {{ $pendingByOthers[$g->id] }}</span><span class="text-blue-600 text-xs font-medium normal-label hidden">&rarr;</span></td>
                        </tr>
                    @else
                        @if ($prefix === 'evaluasi')
                        <tr class="hover:bg-blue-50 active:bg-blue-200 cursor-pointer transition-colors {{ $g->is_active ? '' : 'opacity-50' }}" data-active="{{ $g->is_active ? '1' : '0' }}" onclick="showConfirm(@js('Buat laporan evaluasi untuk ' . $g->kode_gerai . ' - ' . $g->nama_gerai . '?'), function(){ window.location=@js('/' . $prefix . '/checkin/' . $g->id); })">
                        @else
                        <tr class="hover:bg-blue-50 active:bg-blue-200 cursor-pointer transition-colors {{ $g->is_active ? '' : 'opacity-50' }}" data-active="{{ $g->is_active ? '1' : '0' }}" onclick="window.location='/{{ $prefix }}/checkin/{{ $g->id }}'">
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
var showAll = false;
var pairingOn = false;
var prefix = '{{ $prefix }}';

function toggleGeraiFilter() {
    showAll = !showAll;
    pairingOn = showAll;
    localStorage.setItem('pairingOn', pairingOn ? '1' : '0');
    var btn = document.getElementById('toggleAllGerai');
    var dot = document.getElementById('toggleDot');
    var labelOff = document.getElementById('toggleLabelOff');
    var labelOn = document.getElementById('toggleLabelOn');
    if (showAll) {
        btn.style.background = '#3B82F6';
        dot.style.transform = 'translateX(24px)';
        labelOff.style.opacity = '0';
        labelOn.style.opacity = '1';
    } else {
        btn.style.background = '#D1D5DB';
        dot.style.transform = 'translateX(0)';
        labelOff.style.opacity = '1';
        labelOn.style.opacity = '0';
    }
    applyFilters();
}

function applyFilters() {
    var q = document.getElementById('searchGerai').value.toLowerCase();
    document.querySelectorAll('#geraiTableBody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var matchSearch = text.includes(q);
        var matchActive = showAll || row.dataset.active === '1';
        var isPending = row.dataset.pending === '1';

        if (isPending && pairingOn) {
            row.className = 'hover:bg-blue-50 active:bg-blue-200 cursor-pointer transition-colors';
            row.querySelectorAll('td').forEach(function(td) {
                td.classList.remove('text-orange-400');
                td.classList.add('text-gray-800');
            });
            row.querySelectorAll('td:last-child .pending-label').forEach(function(el) { el.classList.add('hidden'); });
            row.querySelectorAll('td:last-child .normal-label').forEach(function(el) { el.classList.remove('hidden'); });
            if (!row.getAttribute('onclick')) {
                var pairingParam = pairingOn ? '?pairing=1' : '';
                row.setAttribute('onclick', "window.location='/" + prefix + "/checkin/" + row.dataset.geraiId + pairingParam + "'");
            }
        } else if (isPending && !pairingOn) {
            row.className = 'hover:bg-gray-50 bg-orange-50';
            row.querySelectorAll('td').forEach(function(td) {
                td.classList.add('text-orange-400');
                td.classList.remove('text-gray-800');
            });
            row.querySelectorAll('td:last-child .pending-label').forEach(function(el) { el.classList.remove('hidden'); });
            row.querySelectorAll('td:last-child .normal-label').forEach(function(el) { el.classList.add('hidden'); });
            row.removeAttribute('onclick');
        }

        row.style.display = (!matchSearch || !matchActive) ? 'none' : '';
    });
}

function filterGerai(q) {
    q = q.toLowerCase();
    applyFilters();
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
