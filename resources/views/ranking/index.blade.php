@extends('layouts.admin')

@section('title', 'Daftar Nilai Monitoring - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Daftar Nilai Monitoring</h2>
            <div class="relative flex items-center gap-2">
                <input type="text" id="searchRanking" placeholder="Cari gerai..."
                    class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                    autocomplete="off" oninput="filterRanking(this.value)">
                <button type="button" onclick="toggleSearch('searchRanking', this)" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <ul id="rankingSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
            </div>
        </div>

        @if ($reports->isEmpty())
            <div class="p-6 text-center text-sm text-gray-400">Belum ada data monitoring yang selesai.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Gerai</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Kode</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap hidden sm:table-cell">Franchisee</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Petugas</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap hidden sm:table-cell">Tanggal</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap hidden md:table-cell">Periode</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-right">Skor</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-center">Grade</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="rankingTableBody" class="divide-y divide-gray-200">
                        @foreach ($reports as $i => $r)
                            @php $grade = \App\Models\MonitoringReport::gradeFromScore((float) $r['skor']); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 sm:px-5 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $r['gerai']->nama_gerai }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-500 whitespace-nowrap">{{ $r['gerai']->kode_gerai }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-500 whitespace-nowrap hidden sm:table-cell">{{ $r['gerai']->franchisee }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-700 whitespace-nowrap">{{ $r['petugas'] }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-500 whitespace-nowrap hidden sm:table-cell">{{ $r['tanggal']->format('d-m-Y') }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-500 whitespace-nowrap hidden md:table-cell">{{ $r['periode_label'] ?? '-' }}</td>
                                <td class="px-3 sm:px-5 py-3 text-right font-semibold text-blue-600 whitespace-nowrap">{{ $r['skor'] }}</td>
                                <td class="px-3 sm:px-5 py-3 text-center font-semibold whitespace-nowrap {{ $grade === 'A' ? 'text-green-600' : ($grade === 'B' ? 'text-blue-600' : ($grade === 'C' ? 'text-yellow-600' : ($grade === 'D' ? 'text-orange-500' : 'text-red-600'))) }}">{{ $grade }}</td>
                                <td class="px-3 sm:px-5 py-3 text-center whitespace-nowrap">
                                    <button onclick="openEditModal('{{ $r['id'] }}', '{{ str_replace("'", "\\'", $r['gerai']->nama_gerai) }}', '{{ $r['skor'] }}', '{{ $r['tanggal']->format('Y-m-d') }}', '{{ str_replace("'", "\\'", $r['petugas']) }}')"
                                        class="inline-block px-2 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEF3C7;color:#D97706">Edit</button>
                                    <form method="POST" action="/daftar-nilai/{{ $r['id'] }}" onsubmit="showConfirm('Hapus nilai {{ $r['gerai']->nama_gerai }}?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="inline-block px-2 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#DC2626">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="px-4 sm:px-6 py-3 border-t border-gray-200">
            {{ $reports->appends(request()->query())->links('pagination::tailwind') }}
        </div>
    </div>

    {{-- FAB --}}
    <div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
        <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
            <button onclick="openDownloadModal()"
                style="background:#ECFDF5;color:#059669" class="w-12 h-12 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center text-xs font-medium relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Download Excel</span>
            </button>
            <button onclick="openHapusPeriodeModal()"
                class="w-12 h-12 bg-red-600 text-white rounded-full shadow-lg hover:bg-red-700 flex items-center justify-center text-xs font-medium relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Hapus Nilai</span>
            </button>
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

    var suggestData = {!! json_encode($gerais->map(fn($g) => ['search' => $g->kode_gerai . ' ' . $g->nama_gerai, 'primary' => $g->kode_gerai, 'secondary' => $g->nama_gerai]), JSON_HEX_TAG) !!};

    function filterRanking(q) {
        q = q.toLowerCase();
        document.querySelectorAll('#rankingTableBody tr').forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
        var list = document.getElementById('rankingSuggest');
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
                document.getElementById('searchRanking').value = item.primary;
                list.classList.add('hidden');
                filterRanking(item.primary);
            });
            list.appendChild(li);
        });
        var btn = document.getElementById('searchRanking').parentElement.querySelector('button');
        positionSuggest(btn, 'rankingSuggest');
        list.classList.remove('hidden');
    }

    document.getElementById('searchRanking').addEventListener('blur', function() {
        setTimeout(function() { document.getElementById('rankingSuggest').classList.add('hidden'); }, 200);
    });

    function openEditModal(id, nama, nilai, tanggal, petugas) {
        closeFab();
        document.getElementById('editForm').action = '/daftar-nilai/' + id;
        document.getElementById('editGeraiName').textContent = nama;
        document.getElementById('editNilai').value = nilai;
        document.getElementById('editTanggal').value = tanggal;
        document.getElementById('editPetugas').value = petugas;
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
    function openDownloadModal() {
        closeFab();
        document.getElementById('downloadModal').classList.remove('hidden');
    }
    function closeDownloadModal() {
        document.getElementById('downloadModal').classList.add('hidden');
    }
    function openHapusPeriodeModal() {
        closeFab();
        document.getElementById('hapusPeriodeModal').classList.remove('hidden');
    }
    function closeHapusPeriodeModal() {
        document.getElementById('hapusPeriodeModal').classList.add('hidden');
    }
    </script>

    {{-- Modal Edit --}}
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Nilai</h3>
            <form method="POST" action="" id="editForm">
                @csrf @method('PUT')
                <p id="editGeraiName" class="text-sm text-gray-500 mb-4"></p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="checkin_at" id="editTanggal" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Petugas</label>
                        <input type="text" name="petugas" id="editPetugas" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai</label>
                        <input type="number" name="nilai" id="editNilai" step="0.01" min="0" max="1000" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Download --}}
    <div id="downloadModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeDownloadModal()"></div>
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Download Excel</h3>
            <form method="GET" action="/daftar-nilai/excel">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                    <select name="periode_label" {{ count($periodeLabels) > 10 ? 'size="10"' : '' }}
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 {{ count($periodeLabels) > 10 ? 'overflow-y-auto' : '' }}">
                        <option value="">Semua Periode</option>
                        @foreach ($periodeLabels as $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeDownloadModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium cursor-pointer">Download</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Hapus Periode --}}
    <div id="hapusPeriodeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeHapusPeriodeModal()"></div>
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Hapus Nilai per Periode</h3>
            <form method="POST" action="/daftar-nilai/hapus-periode" onsubmit="return confirm('Yakin hapus semua data nilai periode ini?')">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                    <select name="periode_label" {{ count($periodeLabels) > 10 ? 'size="10"' : '' }}
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 {{ count($periodeLabels) > 10 ? 'overflow-y-auto' : '' }}" required>
                        <option value="">Pilih Periode</option>
                        @foreach ($periodeLabels as $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeHapusPeriodeModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium cursor-pointer">Hapus</button>
                </div>
            </form>
        </div>
    </div>
@endsection
