@extends('layouts.admin')

@section('title', 'Daftar Harga Air Baku - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Daftar Harga Air Baku <span class="text-sm font-normal text-gray-400">({{ $hargaAirBaku->count() }})</span></h2>
        </div>

        <div class="max-h-[calc(100vh-200px)] overflow-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">No</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">Kota</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">Nama Supplier</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">Harga Air Baku</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">Pemilik</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">Nomor Telepon</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($hargaAirBaku as $index => $s)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-500 whitespace-nowrap">{{ $index + 1 }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap">{{ $s->kota ?? '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-800 whitespace-nowrap">{{ $s->nama_supplier }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap">{{ $s->harga_air_baku !== null ? number_format($s->harga_air_baku, 0, ',', '.') : '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $s->pemilik ?? '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $s->no_telepon ? (str_starts_with($s->no_telepon, '62') ? '0' . substr($s->no_telepon, 2) : $s->no_telepon) : '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-right whitespace-nowrap">
                                <button onclick="openEditModal({{ $s->id }})"
                                    class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit</button>
                                <form method="POST" action="/harga-air-baku/{{ $s->id }}" onsubmit="showConfirm('Hapus data harga air baku ini?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#DC2626">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada data harga air baku.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div id="fabMenu" class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3">
    <div id="fabActions" class="flex flex-col items-center gap-3 transition-all duration-200 ease-in-out opacity-0 scale-0 pointer-events-none">
        <button onclick="openCreateModal()"
            class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 flex items-center justify-center text-xs font-medium relative cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Tambah Supplier</span>
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
        <h2 class="text-xl font-bold text-gray-800 mb-6">Tambah Supplier Air Baku</h2>
        <form id="createForm" method="POST" action="/harga-air-baku">
            @csrf
            <div class="mb-4">
                <label for="create_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                <input id="create_kota" type="text" name="kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_nama_supplier" class="block text-sm font-medium text-gray-700 mb-1">Nama Supplier</label>
                <input id="create_nama_supplier" type="text" name="nama_supplier" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_harga_air_baku" class="block text-sm font-medium text-gray-700 mb-1">Harga Air Baku</label>
                <input id="create_harga_air_baku" type="number" step="0.01" min="0" name="harga_air_baku"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_pemilik" class="block text-sm font-medium text-gray-700 mb-1">Pemilik</label>
                <input id="create_pemilik" type="text" name="pemilik"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label for="create_no_telepon" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
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
        <h2 class="text-xl font-bold text-gray-800 mb-6">Edit Supplier Air Baku</h2>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div class="mb-4">
                <label for="edit_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                <input id="edit_kota" type="text" name="kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_nama_supplier" class="block text-sm font-medium text-gray-700 mb-1">Nama Supplier</label>
                <input id="edit_nama_supplier" type="text" name="nama_supplier" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_harga_air_baku" class="block text-sm font-medium text-gray-700 mb-1">Harga Air Baku</label>
                <input id="edit_harga_air_baku" type="number" step="0.01" min="0" name="harga_air_baku"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_pemilik" class="block text-sm font-medium text-gray-700 mb-1">Pemilik</label>
                <input id="edit_pemilik" type="text" name="pemilik"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label for="edit_no_telepon" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
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

<script>
var supplierData = {!! json_encode($hargaAirBaku->map(fn($s) => [
    'id' => $s->id,
    'kota' => $s->kota ?? '',
    'nama_supplier' => $s->nama_supplier,
    'harga_air_baku' => $s->harga_air_baku !== null ? (string) $s->harga_air_baku : '',
    'pemilik' => $s->pemilik ?? '',
    'no_telepon' => $s->no_telepon ?? '',
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
    document.getElementById('create_kota').value = '';
    document.getElementById('create_nama_supplier').value = '';
    document.getElementById('create_harga_air_baku').value = '';
    document.getElementById('create_pemilik').value = '';
    document.getElementById('create_no_telepon').value = '';
    document.getElementById('createModal').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(id) {
    closeFab();
    var s = supplierData.find(function(x) { return x.id === id; });
    if (!s) return;
    document.getElementById('editForm').action = '/harga-air-baku/' + id;
    document.getElementById('edit_kota').value = s.kota;
    document.getElementById('edit_nama_supplier').value = s.nama_supplier;
    document.getElementById('edit_harga_air_baku').value = s.harga_air_baku;
    document.getElementById('edit_pemilik').value = s.pemilik;
    document.getElementById('edit_no_telepon').value = s.no_telepon;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

['createForm', 'editForm'].forEach(function(formId) {
    var form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
});
</script>
@endsection
