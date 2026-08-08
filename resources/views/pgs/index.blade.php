@extends('layouts.admin')

@section('title', 'Data PG - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Data PG <span class="text-sm font-normal text-gray-400">({{ $pgs->count() }})</span></h2>
            <div class="relative flex items-center gap-1 sm:gap-2 shrink-0">
                <input type="text" id="searchPg" placeholder="Cari PG..."
                    class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                    autocomplete="off" oninput="filterPg(this.value)">
                <button type="button" onclick="toggleSearch('searchPg', this)" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <ul id="pgSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
            </div>
        </div>

        <div class="max-h-[calc(100vh-200px)] overflow-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">No</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">Nama PG</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">Kota</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">No Telepon</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody id="pgTableBody" class="divide-y divide-gray-200">
                    @forelse ($pgs as $index => $pg)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-500 whitespace-nowrap">{{ $index + 1 }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-800 whitespace-nowrap">{{ $pg->nama_pg }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $pg->kota ?? '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $pg->no_telepon ? (str_starts_with($pg->no_telepon, '62') ? '0' . substr($pg->no_telepon, 2) : $pg->no_telepon) : '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-right whitespace-nowrap">
                                <button onclick="openEditModal({{ $pg->id }})"
                                    class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit</button>
                                <form method="POST" action="/pgs/{{ $pg->id }}" onsubmit="showConfirm('Hapus data PG ini?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#DC2626">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada data PG.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
    <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
        <a href="/pgs/export" target="_blank"
            class="w-12 h-12 bg-purple-600 text-white rounded-full shadow-lg hover:bg-purple-700 flex items-center justify-center text-xs font-medium relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Download Excel</span>
        </a>
        <button onclick="openImportModal()"
            class="w-12 h-12 bg-green-600 text-white rounded-full shadow-lg hover:bg-green-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Upload Excel</span>
        </button>
        <button onclick="openCreateModal()"
            class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Tambah PG</span>
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

{{-- Modal Create --}}
<div id="createModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeCreateModal()"></div>
    <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg mx-4 p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Tambah PG</h2>
        <form method="POST" action="/pgs">
            @csrf
            <div class="mb-4">
                <label for="create_nama_pg" class="block text-sm font-medium text-gray-700 mb-1">Nama PG</label>
                <input id="create_nama_pg" type="text" name="nama_pg" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                <input id="create_kota" type="text" name="kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label for="create_no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No Telepon</label>
                <input id="create_no_telepon" type="text" name="no_telepon" placeholder="08xxxxxxxxxx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeEditModal()"></div>
    <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg mx-4 p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Edit PG</h2>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div class="mb-4">
                <label for="edit_nama_pg" class="block text-sm font-medium text-gray-700 mb-1">Nama PG</label>
                <input id="edit_nama_pg" type="text" name="nama_pg" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                <input id="edit_kota" type="text" name="kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label for="edit_no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No Telepon</label>
                <input id="edit_no_telepon" type="text" name="no_telepon" placeholder="08xxxxxxxxxx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Import --}}
<div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeImportModal()"></div>
    <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg mx-4 p-6 sm:p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Import Data PG</h2>
        <p class="text-sm text-gray-500 mb-4">Upload file Excel untuk import data PG.</p>
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <p class="font-medium mb-1">Format file:</p>
            <p>Kolom A: Nama PG<br>Kolom B: Kota<br>Kolom C: No Telepon</p>
            <a href="/pgs/template" target="_blank" class="mt-2 inline-block text-blue-600 hover:underline font-medium">Download template &rarr;</a>
        </div>
        <form method="POST" action="/pgs/import" enctype="multipart/form-data">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih file Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeImportModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium cursor-pointer">Import</button>
            </div>
        </form>
    </div>
</div>

<script>
var suggestData = {!! json_encode($pgs->map(fn($p) => ['search' => $p->nama_pg, 'primary' => $p->nama_pg, 'secondary' => $p->kota ?? '']), JSON_HEX_TAG) !!};

var pgData = {!! json_encode($pgs->map(fn($p) => [
    'id' => $p->id,
    'nama_pg' => $p->nama_pg,
    'kota' => $p->kota ?? '',
    'no_telepon' => $p->no_telepon ?? '',
]), JSON_HEX_TAG) !!};

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

function openCreateModal() {
    closeFab();
    document.getElementById('create_nama_pg').value = '';
    document.getElementById('create_kota').value = '';
    document.getElementById('create_no_telepon').value = '';
    document.getElementById('createModal').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(id) {
    closeFab();
    var p = pgData.find(function(x) { return x.id === id; });
    if (!p) return;
    document.getElementById('editForm').action = '/pgs/' + id;
    document.getElementById('edit_nama_pg').value = p.nama_pg;
    document.getElementById('edit_kota').value = p.kota;
    document.getElementById('edit_no_telepon').value = p.no_telepon;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function openImportModal() {
    closeFab();
    document.getElementById('importModal').classList.remove('hidden');
}
function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
}

function filterPg(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#pgTableBody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
    var list = document.getElementById('pgSuggest');
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
            document.getElementById('searchPg').value = item.primary;
            list.classList.add('hidden');
            filterPg(item.primary);
        });
        list.appendChild(li);
    });
    var btn = document.getElementById('searchPg').parentElement.querySelector('button');
    positionSuggest(btn, 'pgSuggest');
    list.classList.remove('hidden');
}

document.getElementById('searchPg').addEventListener('blur', function() {
    setTimeout(function() { document.getElementById('pgSuggest').classList.add('hidden'); }, 200);
});
</script>
@endsection
