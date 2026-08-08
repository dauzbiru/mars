@extends('layouts.admin')

@section('title', 'Gerai - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Data Gerai</h2>
                <div id="filterButtons" class="flex items-center gap-2 text-xs font-medium">
                    <button onclick="filterGeraiByStatus('')" id="filterAll" style="background:#374151;color:#FFFFFF" class="px-2 py-0.5 rounded-full font-medium cursor-pointer transition-colors">All: {{ $gerais->count() }}</button>
                    <button onclick="filterGeraiByStatus('aktif')" id="filterAktif" style="background:#DCFCE7;color:#16A34A" class="px-2 py-0.5 rounded-full font-medium cursor-pointer hover:opacity-80 transition-colors">Buka: {{ $gerais->where('is_active', true)->count() }}</button>
                    <button onclick="filterGeraiByStatus('tutup')" id="filterTutup" style="background:#F3F4F6;color:#4B5563" class="px-2 py-0.5 rounded-full font-medium cursor-pointer hover:opacity-80 transition-colors">Tutup: {{ $gerais->where('is_active', false)->count() }}</button>
                    <button onclick="openKotaModal()" style="background:#EEF2FF;color:#4F46E5" class="px-2 py-0.5 rounded-full font-medium cursor-pointer hover:opacity-80 transition-colors">Kota</button>
                </div>
            </div>
            <div class="relative flex items-center gap-1 sm:gap-2 shrink-0">
                    <input type="text" id="searchGerai" placeholder="Cari gerai..."
                        class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                        autocomplete="off" oninput="filterGerai(this.value)">
                    <button type="button" onclick="toggleSearch('searchGerai', this); toggleFilterOnMobile()" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <ul id="geraiSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
            </div>
        </div>

        <div id="geraiScroll" class="max-h-[calc(100vh-200px)] overflow-auto" style="max-height:calc(100dvh - 200px);overscroll-behavior:contain; -webkit-overflow-scrolling:touch">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('kode_gerai')">Kode <span class="sort-icon" data-col="kode_gerai"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('nama_gerai')">Nama <span class="sort-icon" data-col="nama_gerai"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('franchisee')">Franchisee <span class="sort-icon" data-col="franchisee"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('alamat')">Alamat <span class="sort-icon" data-col="alamat"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('email')">Email <span class="sort-icon" data-col="email"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('no_telepon')">No Telepon <span class="sort-icon" data-col="no_telepon"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('opening_at')">Opening <span class="sort-icon" data-col="opening_at"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('lama_beroperasi')">Lama Beroperasi <span class="sort-icon" data-col="lama_beroperasi"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('nama_kota')">Nama Kota <span class="sort-icon" data-col="nama_kota"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('area')">Area <span class="sort-icon" data-col="area"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap cursor-pointer hover:text-gray-700 select-none" onclick="sortTable('status')">Status <span class="sort-icon" data-col="status"></span></th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody id="geraiTableBody" class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
<div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
    <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
        <div class="relative">
            <button onclick="openDownloadModal()"
                style="background:#ECFDF5;color:#059669"
                class="w-12 h-12 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center text-xs font-medium relative cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Download Excel</span>
            </button>
        </div>
        <button onclick="openImportModal()"
            class="w-12 h-12 bg-green-600 text-white rounded-full shadow-lg hover:bg-green-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Upload Excel</span>
        </button>
        <button onclick="openCreateModal()"
            class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Tambah Gerai</span>
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
        <h2 class="text-xl font-bold text-gray-800 mb-6">Tambah Gerai</h2>
        <form method="POST" action="/gerais">
            @csrf
            <div class="mb-4">
                <label for="create_kode_gerai" class="block text-sm font-medium text-gray-700 mb-1">Kode Gerai</label>
                <input id="create_kode_gerai" type="text" name="kode_gerai" required oninput="checkKotaFromKode()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_nama_gerai" class="block text-sm font-medium text-gray-700 mb-1">Nama Gerai</label>
                <input id="create_nama_gerai" type="text" name="nama_gerai" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_franchisee" class="block text-sm font-medium text-gray-700 mb-1">Franchisee</label>
                <input id="create_franchisee" type="text" name="franchisee" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea id="create_alamat" name="alamat" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="mb-4">
                <label for="create_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="create_email" type="email" name="email"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No Telepon</label>
                <input id="create_no_telepon" type="text" name="no_telepon" placeholder="08xxxxxxxxxx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label for="create_opening_at" class="block text-sm font-medium text-gray-700 mb-1">Opening</label>
                <input id="create_opening_at" type="date" name="opening_at"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="createKotaFields" class="hidden">
                <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 mb-4">
                    Kode gerai ini belum terdaftar di daftar kota. Isi nama kota dan area di bawah.
                </div>
                <div class="mb-4">
                    <label for="create_nama_kota" class="block text-sm font-medium text-gray-700 mb-1">Nama Kota</label>
                    <input id="create_nama_kota" type="text" name="nama_kota"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="create_area" class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                    <input id="create_area" type="text" name="area"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
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
        <h2 class="text-xl font-bold text-gray-800 mb-6">Edit Gerai</h2>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div class="mb-4">
                <label for="edit_kode_gerai" class="block text-sm font-medium text-gray-700 mb-1">Kode Gerai</label>
                <input id="edit_kode_gerai" type="text" name="kode_gerai" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_nama_gerai" class="block text-sm font-medium text-gray-700 mb-1">Nama Gerai</label>
                <input id="edit_nama_gerai" type="text" name="nama_gerai" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_franchisee" class="block text-sm font-medium text-gray-700 mb-1">Franchisee</label>
                <input id="edit_franchisee" type="text" name="franchisee" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea id="edit_alamat" name="alamat" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="mb-4">
                <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="edit_email" type="email" name="email"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No Telepon</label>
                <input id="edit_no_telepon" type="text" name="no_telepon" placeholder="08xxxxxxxxxx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label for="edit_opening_at" class="block text-sm font-medium text-gray-700 mb-1">Opening</label>
                <input id="edit_opening_at" type="date" name="opening_at"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_nama_kota" class="block text-sm font-medium text-gray-700 mb-1">Nama Kota</label>
                <input id="edit_nama_kota" type="text" name="nama_kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_area" class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                <input id="edit_area" type="text" name="area"
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
        <h2 class="text-xl font-bold text-gray-800 mb-2">Import Gerai</h2>
        <p class="text-sm text-gray-500 mb-4">Upload file Excel untuk import data gerai.</p>
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <p class="font-medium mb-1">Format file:</p>
            <p>Kolom A: Kode Gerai<br>Kolom B: Nama Gerai<br>Kolom C: Franchisee<br>Kolom D: Alamat<br>Kolom E: Email<br>Kolom F: No Telepon<br>Kolom G: Opening (format dd-mm-yyyy, opsional)<br>Kolom H: Nama Kota (opsional, auto dari kode)<br>Kolom I: Area (opsional, auto dari kode)</p>
            <a href="/gerais/template" target="_blank" class="mt-2 inline-block text-blue-600 hover:underline font-medium">Download template &rarr;</a>
        </div>
        <form method="POST" action="/gerais/import" enctype="multipart/form-data">
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

{{-- Modal Daftar Nama Kota --}}
<div id="kotaModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeKotaModal()"></div>
    <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg mx-4 p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Daftar Nama Kota & Area</h2>
        <p class="text-sm text-gray-500 mb-4">Berdasarkan 3 huruf pertama kode gerai.</p>

        <div id="kotaAddForm" class="mb-4 p-3 bg-gray-50 rounded-lg hidden">
            <div class="flex gap-2 mb-2">
                <input id="add_kode" type="text" placeholder="Kode" maxlength="10" class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase">
                <input id="add_nama_kota" type="text" placeholder="Nama Kota" class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input id="add_area" type="text" placeholder="Area" class="w-28 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button onclick="submitAddKota()" class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 cursor-pointer">Simpan</button>
                <button onclick="toggleAddKotaForm()" class="px-3 py-1.5 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-100 cursor-pointer">Batal</button>
            </div>
        </div>

        <div class="mb-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="py-2">Kode</th>
                        <th class="py-2">Nama Kota</th>
                        <th class="py-2">Area</th>
                        <th class="py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody id="kotaTableBody">
                    @foreach ($kotaMaps as $km)
                        <tr class="border-b" id="kota-row-{{ $km->id }}">
                            <td class="py-1.5 font-medium text-gray-800">{{ $km->kode }}</td>
                            <td class="py-1.5 text-gray-600">
                                <span class="kota-view">{{ $km->nama_kota }}</span>
                                <input type="text" value="{{ $km->nama_kota }}" class="kota-edit hidden w-full px-2 py-1 border border-gray-300 rounded text-sm">
                            </td>
                            <td class="py-1.5 text-gray-600">
                                <span class="kota-view">{{ $km->area }}</span>
                                <input type="text" value="{{ $km->area }}" class="kota-edit hidden w-full px-2 py-1 border border-gray-300 rounded text-sm">
                            </td>
                            <td class="py-1.5 text-right whitespace-nowrap">
                                <button onclick="editKotaRow(this)" class="kota-view p-1 text-amber-600 hover:bg-amber-50 rounded cursor-pointer" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button onclick="saveKotaRow(this, {{ $km->id }})" class="kota-edit hidden p-1 text-green-600 hover:bg-green-50 rounded cursor-pointer" title="Simpan">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button onclick="cancelEditKotaRow(this)" class="kota-edit hidden p-1 text-gray-500 hover:bg-gray-100 rounded cursor-pointer" title="Batal">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <form method="POST" action="/gerais/kota-maps/{{ $km->id }}" onsubmit="showConfirm('Hapus kota ini?', function(){ this.submit(); }.bind(this)); return false;" class="inline kota-view">
                                    @csrf @method('DELETE')
                                    <button class="p-1 text-red-500 hover:bg-red-50 rounded" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex gap-2 mb-4">
            <button onclick="toggleAddKotaForm()" id="btnAddKota" style="background:#EEF2FF;color:#4F46E5" class="px-3 py-1.5 text-xs font-medium rounded-lg hover:opacity-80 cursor-pointer">+ Tambah Kota</button>
            <form method="POST" action="/gerais/sync-kota" class="flex-1">
                @csrf
                <button type="submit" class="w-full px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 cursor-pointer">Sync ke Semua Gerai</button>
            </form>
        </div>
        <button onclick="closeKotaModal()" class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Tutup</button>
    </div>
</div>

<div id="downloadModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeDownloadModal()"></div>
    <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Download Data Gerai</h2>
        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih data yang ingin didownload</label>
        <select id="dlStatus" onchange="updateDlLink()" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white mb-4">
            <option value="all">Semua Gerai ({{ $gerais->count() }})</option>
            <option value="active">Gerai Buka ({{ $gerais->where('is_active', true)->count() }})</option>
            <option value="closed">Gerai Tutup ({{ $gerais->where('is_active', false)->count() }})</option>
        </select>
        <div class="flex gap-3">
            <button onclick="closeDownloadModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
            <a id="dlDownloadBtn" href="/gerais/export?status=all" target="_blank" onclick="closeDownloadModal()"
                class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-center cursor-pointer"
                style="background:#2563EB;color:#FFFFFF;">Download</a>
        </div>
    </div>
</div>

<script>
function openDownloadModal() {
    closeFab();
    var sel = document.getElementById('dlStatus');
    sel.value = 'all';
    updateDlLink();
    document.getElementById('downloadModal').classList.remove('hidden');
}
function closeDownloadModal() {
    document.getElementById('downloadModal').classList.add('hidden');
}
function updateDlLink() {
    var status = document.getElementById('dlStatus').value;
    document.getElementById('dlDownloadBtn').href = '/gerais/export?status=' + status;
}
</script>

<script>
(function () {
    var scroller = document.getElementById('geraiScroll');
    if (!scroller) return;
    function lock(e) {
        var atTop = scroller.scrollTop <= 0;
        var atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 1;
        if ((atTop && e.deltaY < 0) || (atBottom && e.deltaY > 0)) e.preventDefault();
    }
    scroller.addEventListener('wheel', lock, { passive: false });
})();

var kotaMap = {
    @foreach ($kotaMaps as $km)
        '{{ $km->kode }}': ['{{ $km->nama_kota }}', '{{ $km->area }}']@if(!$loop->last),@endif
    @endforeach
};

function openKotaModal() {
    document.getElementById('kotaModal').classList.remove('hidden');
}
function closeKotaModal() {
    document.getElementById('kotaModal').classList.add('hidden');
}

function toggleAddKotaForm() {
    var form = document.getElementById('kotaAddForm');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('add_kode').value = '';
        document.getElementById('add_nama_kota').value = '';
        document.getElementById('add_area').value = '';
        document.getElementById('add_kode').focus();
    }
}

function submitAddKota() {
    var kode = document.getElementById('add_kode').value.trim();
    var namaKota = document.getElementById('add_nama_kota').value.trim();
    var area = document.getElementById('add_area').value.trim();
    if (!kode || !namaKota || !area) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/gerais/kota-maps';
    form.innerHTML = '@csrf<input type="hidden" name="kode" value="' + kode + '"><input type="hidden" name="nama_kota" value="' + namaKota + '"><input type="hidden" name="area" value="' + area + '">';
    document.body.appendChild(form);
    form.submit();
}

function editKotaRow(btn) {
    var row = btn.closest('tr');
    row.querySelectorAll('.kota-view').forEach(function(el) { el.classList.add('hidden'); });
    row.querySelectorAll('.kota-edit').forEach(function(el) { el.classList.remove('hidden'); });
}

function cancelEditKotaRow(btn) {
    var row = btn.closest('tr');
    row.querySelectorAll('.kota-edit').forEach(function(el) { el.classList.add('hidden'); });
    row.querySelectorAll('.kota-view').forEach(function(el) { el.classList.remove('hidden'); });
}

function saveKotaRow(btn, id) {
    var row = btn.closest('tr');
    var inputs = row.querySelectorAll('.kota-edit');
    var namaKota = inputs[0].value.trim();
    var area = inputs[1].value.trim();
    if (!namaKota || !area) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/gerais/kota-maps/' + id;
    form.innerHTML = '@csrf<input type="hidden" name="_method" value="PUT"><input type="hidden" name="kode" value="' + row.querySelector('td:first-child').textContent.trim() + '"><input type="hidden" name="nama_kota" value="' + namaKota + '"><input type="hidden" name="area" value="' + area + '">';
    document.body.appendChild(form);
    form.submit();
}

function checkKotaFromKode() {
    var kode = document.getElementById('create_kode_gerai').value.trim().toUpperCase();
    var fieldsDiv = document.getElementById('createKotaFields');
    if (kode.length >= 3) {
        var prefix = kode.substring(0, 3);
        if (kotaMap[prefix]) {
            fieldsDiv.classList.add('hidden');
            document.getElementById('create_nama_kota').value = '';
            document.getElementById('create_area').value = '';
        } else {
            fieldsDiv.classList.remove('hidden');
        }
    } else {
        fieldsDiv.classList.add('hidden');
    }
}

var geraiData = {!! json_encode($gerais->map(fn($g) => [
    'id' => $g->id,
    'kode_gerai' => $g->kode_gerai,
    'nama_gerai' => $g->nama_gerai,
    'franchisee' => $g->franchisee,
    'alamat' => $g->alamat ?? '',
    'email' => $g->email ?? '',
    'no_telepon' => $g->no_telepon ?? '',
    'opening_at' => $g->opening_at?->format('Y-m-d') ?? '',
    'nama_kota' => $g->nama_kota ?? '',
    'area' => $g->area ?? '',
    'is_active' => (bool)$g->is_active,
    'lama_beroperasi' => $g->is_active && $g->opening_at ? (int)$g->opening_at->diffInDays() : -1,
]), JSON_HEX_TAG) !!};

var csrfToken = '{{ csrf_token() }}';

renderGeraiTable();

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
    document.getElementById('create_kode_gerai').value = '';
    document.getElementById('create_nama_gerai').value = '';
    document.getElementById('create_franchisee').value = '';
    document.getElementById('create_alamat').value = '';
    document.getElementById('create_email').value = '';
    document.getElementById('create_no_telepon').value = '';
    document.getElementById('create_opening_at').value = '';
    document.getElementById('create_nama_kota').value = '';
    document.getElementById('create_area').value = '';
    document.getElementById('createKotaFields').classList.add('hidden');
    document.getElementById('createModal').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(id) {
    closeFab();
    var g = geraiData.find(function(x) { return x.id === id; });
    if (!g) return;
    document.getElementById('editForm').action = '/gerais/' + id;
    document.getElementById('edit_kode_gerai').value = g.kode_gerai;
    document.getElementById('edit_nama_gerai').value = g.nama_gerai;
    document.getElementById('edit_franchisee').value = g.franchisee;
    document.getElementById('edit_alamat').value = g.alamat;
    document.getElementById('edit_email').value = g.email;
    document.getElementById('edit_no_telepon').value = g.no_telepon;
    document.getElementById('edit_opening_at').value = g.opening_at;
    document.getElementById('edit_nama_kota').value = g.nama_kota;
    document.getElementById('edit_area').value = g.area;
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

function filterGerai(q) {
    q = q.toLowerCase();
    document.getElementById('searchGerai').value = q;
    renderGeraiTable();
    var list = document.getElementById('geraiSuggest');
    list.innerHTML = '';
    if (!q) { list.classList.add('hidden'); return; }
    var matches = geraiData.filter(function(g) {
        return g.kode_gerai.toLowerCase().includes(q) || g.nama_gerai.toLowerCase().includes(q);
    }).slice(0, 8);
    if (matches.length === 0) { list.classList.add('hidden'); return; }
    matches.forEach(function(g) {
        var li = document.createElement('li');
        li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
        li.innerHTML = '<span class="font-medium text-gray-800">' + g.kode_gerai + '</span><span class="text-gray-500"> - ' + g.nama_gerai + '</span>';
        li.addEventListener('mousedown', function(e) {
            e.preventDefault();
            document.getElementById('searchGerai').value = g.nama_gerai;
            list.classList.add('hidden');
            filterGerai(g.nama_gerai);
        });
        list.appendChild(li);
    });
    var btn = document.getElementById('searchGerai').parentElement.querySelector('button');
    positionSuggest(btn, 'geraiSuggest');
    list.classList.remove('hidden');
}

var currentGeraiStatus = '';
function filterGeraiByStatus(status) {
    currentGeraiStatus = status;
    renderGeraiTable();
    var allBtn = document.getElementById('filterAll');
    var aktifBtn = document.getElementById('filterAktif');
    var tutupBtn = document.getElementById('filterTutup');
    allBtn.style.background = !status ? '#374151' : '#F3F4F6';
    allBtn.style.color = !status ? '#FFFFFF' : '#4B5563';
    aktifBtn.style.background = status === 'aktif' ? '#16A34A' : '#DCFCE7';
    aktifBtn.style.color = status === 'aktif' ? '#FFFFFF' : '#16A34A';
    tutupBtn.style.background = status === 'tutup' ? '#6B7280' : '#F3F4F6';
    tutupBtn.style.color = status === 'tutup' ? '#FFFFFF' : '#4B5563';
}

function toggleFilterOnMobile() {
    if (window.innerWidth >= 640) return;
    var filters = document.getElementById('filterButtons');
    var input = document.getElementById('searchGerai');
    if (input.classList.contains('w-0')) {
        filters.classList.remove('hidden');
    } else {
        filters.classList.add('hidden');
    }
}

document.addEventListener('click', function(e) {
    if (window.innerWidth >= 640) return;
    setTimeout(function() {
        var input = document.getElementById('searchGerai');
        var filters = document.getElementById('filterButtons');
        if (input && filters) {
            if (input.classList.contains('w-0')) {
                filters.classList.remove('hidden');
            } else {
                filters.classList.add('hidden');
            }
        }
    }, 10);
});

document.getElementById('searchGerai').addEventListener('blur', function() {
    setTimeout(function() { document.getElementById('geraiSuggest').classList.add('hidden'); }, 200);
});

var sortState = {};
function sortTable(col) {
    var dir = sortState[col] === 'asc' ? 'desc' : 'asc';
    sortState = {};
    sortState[col] = dir;

    document.querySelectorAll('.sort-icon').forEach(function(el) {
        el.textContent = el.getAttribute('data-col') === col ? (dir === 'asc' ? ' \u25B2' : ' \u25BC') : '';
    });

    renderGeraiTable();
}

function renderGeraiTable() {
    var tbody = document.getElementById('geraiTableBody');
    var q = (document.getElementById('searchGerai').value || '').toLowerCase();
    var status = currentGeraiStatus;
    var sortCol = null, sortDir = 'asc';
    for (var k in sortState) { sortCol = k; sortDir = sortState[k]; }

    var matched = geraiData.filter(function(g) {
        var match = q ? (g.kode_gerai.toLowerCase().includes(q) || g.nama_gerai.toLowerCase().includes(q)) : true;
        var st = status === 'aktif' ? g.is_active : (status === 'tutup' ? !g.is_active : true);
        return match && st;
    });

    var groupSort = function(list) {
        if (!sortCol || sortCol === 'status') return list;
        var col = sortCol, dir = sortDir;
        return list.slice().sort(function(a, b) {
            var va = a[col], vb = b[col];
            var r;
            if (col === 'lama_beroperasi') r = (va||-1) - (vb||-1);
            else if (col === 'opening_at') r = (va||' ').localeCompare(vb||' ');
            else r = String(va||'').localeCompare(String(vb||''));
            return dir === 'asc' ? r : -r;
        });
    };

    var active = groupSort(matched.filter(function(g) { return g.is_active; }));
    var closed = groupSort(matched.filter(function(g) { return !g.is_active; }));
    var closedFirst = sortCol === 'status' && sortDir === 'desc';
    var groups = closedFirst ? [closed, active] : [active, closed];
    var groupLabels = closedFirst ? ['Gerai Tutup', 'Gerai Buka'] : ['Gerai Buka', 'Gerai Tutup'];

    var rows = [];
    groups.forEach(function(group, gi) {
        if (group.length === 0) return;
        if (gi > 0 && rows.length > 0) {
            rows.push('<tr class="border-t-2 border-gray-400"><td colspan="12" class="px-3 sm:px-6 py-2 bg-gray-200/60 text-xs font-semibold text-gray-600 uppercase tracking-wide">' + groupLabels[gi] + '</td></tr>');
        }
        group.forEach(function(g) {
            var diff = '-';
            if (g.is_active && g.lama_beroperasi > 0) {
                var parts = [];
                var d = g.lama_beroperasi;
                if (d >= 365) parts.push(Math.floor(d/365) + ' thn');
                if (d%365 >= 30) parts.push(Math.floor((d%365)/30) + ' bln');
                if (parts.length === 0 || d%30 > 0) parts.push(d%30 + ' hr');
                diff = parts.join(' ');
            }
            var phone = g.no_telepon && String(g.no_telepon).startsWith('62') ? '0' + String(g.no_telepon).slice(2) : (g.no_telepon || '-');
            rows.push('<tr class="hover:bg-gray-50 ' + (!g.is_active ? 'bg-gray-100' : '') + '" data-active="' + (g.is_active ? '1' : '0') + '">'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-800 whitespace-nowrap">' + g.kode_gerai + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 truncate max-w-[120px] sm:max-w-none">' + g.nama_gerai + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + g.franchisee + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 truncate max-w-[120px] sm:max-w-none hidden sm:table-cell">' + (g.alamat || '-') + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + (g.email || '-') + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + phone + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + (g.opening_at ? formatDate(g.opening_at) : '-') + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + diff + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + (g.nama_kota || '-') + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">' + (g.area || '-') + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-xs sm:text-sm whitespace-nowrap">' + (g.is_active ? '<span class="text-green-600 font-medium">Aktif</span>' : '<span class="text-gray-800 font-medium">Tutup</span>') + '</td>'
                + '<td class="px-3 sm:px-6 py-3 text-right whitespace-nowrap">'
                + '<button onclick="openEditModal(' + g.id + ')" class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit</button>'
                + (g.is_active
                    ? '<form method="POST" action="/gerais/' + g.id + '/tutup" onsubmit="showConfirm(\'Tutup gerai ini?\', function(){ this.submit(); }.bind(this)); return false;" class="inline"><input type="hidden" name="_token" value="' + csrfToken + '"><button class="inline-block px-2 sm:px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Tutup</button></form>'
                    + '<form method="POST" action="/gerais/' + g.id + '" onsubmit="showConfirm(\'Hapus gerai ini?\', function(){ this.submit(); }.bind(this)); return false;" class="inline"><input type="hidden" name="_token" value="' + csrfToken + '"><input type="hidden" name="_method" value="DELETE"><button style="background:#FEE2E2;color:#DC2626" class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80">Hapus</button></form>'
                    : '<form method="POST" action="/gerais/' + g.id + '/buka" onsubmit="showConfirm(\'Buka kembali gerai ini?\', function(){ this.submit(); }.bind(this)); return false;" class="inline"><input type="hidden" name="_token" value="' + csrfToken + '"><button class="inline-block px-2 sm:px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100">Buka</button></form>'
                    + '<form method="POST" action="/gerais/' + g.id + '" onsubmit="showConfirm(\'Hapus gerai ini?\', function(){ this.submit(); }.bind(this)); return false;" class="inline"><input type="hidden" name="_token" value="' + csrfToken + '"><input type="hidden" name="_method" value="DELETE"><button style="background:#FEE2E2;color:#DC2626" class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80">Hapus</button></form>')
                + '</td></tr>');
        });
    });

    if (rows.length === 0) {
        rows.push('<tr><td colspan="12" class="px-3 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada data gerai.</td></tr>');
    }
    tbody.innerHTML = rows.join('');
}

function formatDate(ymd) {
    if (!ymd) return '-';
    var p = ymd.split('-');
    return p[2] + '-' + p[1] + '-' + p[0];
}
</script>
@endsection
