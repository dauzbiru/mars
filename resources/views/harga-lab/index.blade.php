@extends('layouts.admin')

@section('title', 'Daftar Harga Uji Lab - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Daftar Harga Uji Lab <span class="text-sm font-normal text-gray-400">({{ $hargaLab->count() }})</span></h2>
        </div>

        <div class="max-h-[calc(100vh-200px)] overflow-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">No</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">Kota</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap">Laboratorium</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">Mikrobiologi</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">Fisika dan Kimia</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden sm:table-cell">Lengkap</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden md:table-cell w-48">Catatan</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap hidden md:table-cell w-48">Alamat</th>
                        <th class="px-3 sm:px-6 py-3 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($hargaLab as $index => $lab)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-500 whitespace-nowrap">{{ $index + 1 }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap">{{ $lab->kota ?? '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-800 whitespace-nowrap">{{ $lab->laboratorium }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $lab->mikrobiologi !== null ? number_format($lab->mikrobiologi, 0, ',', '.') : '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $lab->fisika_kimia !== null ? number_format($lab->fisika_kimia, 0, ',', '.') : '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 text-xs sm:text-sm text-gray-600 whitespace-nowrap hidden sm:table-cell">{{ $lab->lengkap !== null ? number_format($lab->lengkap, 0, ',', '.') : '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 hidden md:table-cell">
                                <div class="max-w-48 text-xs sm:text-sm text-gray-600 break-words whitespace-pre-line">{{ $lab->catatan ?? '-' }}</div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 hidden md:table-cell">
                                <div class="max-w-48 text-xs sm:text-sm text-gray-600 break-words whitespace-pre-line">{{ $lab->alamat ?? '-' }}</div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 text-right whitespace-nowrap">
                                <button onclick="openEditModal({{ $lab->id }})"
                                    class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit</button>
                                <form method="POST" action="/harga-lab/{{ $lab->id }}" onsubmit="showConfirm('Hapus data harga uji lab ini?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="inline-block px-2 sm:px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#DC2626">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada data harga uji lab.</td>
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
            <span class="absolute right-full mr-3 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">Tambah Laboratorium</span>
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
        <h2 class="text-xl font-bold text-gray-800 mb-6">Tambah Laboratorium</h2>
        <form id="createForm" method="POST" action="/harga-lab">
            @csrf
            <div class="mb-4">
                <label for="create_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                <input id="create_kota" type="text" name="kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_laboratorium" class="block text-sm font-medium text-gray-700 mb-1">Laboratorium</label>
                <input id="create_laboratorium" type="text" name="laboratorium" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_mikrobiologi" class="block text-sm font-medium text-gray-700 mb-1">Mikrobiologi</label>
                <input id="create_mikrobiologi" type="number" step="0.01" min="0" name="mikrobiologi"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_fisika_kimia" class="block text-sm font-medium text-gray-700 mb-1">Fisika dan Kimia</label>
                <input id="create_fisika_kimia" type="number" step="0.01" min="0" name="fisika_kimia"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Lengkap</label>
                <input id="create_lengkap" type="number" step="0.01" min="0" name="lengkap"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="create_catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea id="create_catatan" name="catatan" rows="2" oninput="autoResize(this)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden"></textarea>
            </div>
            <div class="mb-6">
                <label for="create_alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea id="create_alamat" name="alamat" rows="2" oninput="autoResize(this)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden"></textarea>
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
        <h2 class="text-xl font-bold text-gray-800 mb-6">Edit Laboratorium</h2>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div class="mb-4">
                <label for="edit_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                <input id="edit_kota" type="text" name="kota"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_laboratorium" class="block text-sm font-medium text-gray-700 mb-1">Laboratorium</label>
                <input id="edit_laboratorium" type="text" name="laboratorium" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_mikrobiologi" class="block text-sm font-medium text-gray-700 mb-1">Mikrobiologi</label>
                <input id="edit_mikrobiologi" type="number" step="0.01" min="0" name="mikrobiologi"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_fisika_kimia" class="block text-sm font-medium text-gray-700 mb-1">Fisika dan Kimia</label>
                <input id="edit_fisika_kimia" type="number" step="0.01" min="0" name="fisika_kimia"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Lengkap</label>
                <input id="edit_lengkap" type="number" step="0.01" min="0" name="lengkap"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="edit_catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea id="edit_catatan" name="catatan" rows="2" oninput="autoResize(this)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden"></textarea>
            </div>
            <div class="mb-6">
                <label for="edit_alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea id="edit_alamat" name="alamat" rows="2" oninput="autoResize(this)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
var labData = {!! json_encode($hargaLab->map(fn($l) => [
    'id' => $l->id,
    'kota' => $l->kota ?? '',
    'laboratorium' => $l->laboratorium,
    'mikrobiologi' => $l->mikrobiologi !== null ? (string) $l->mikrobiologi : '',
    'fisika_kimia' => $l->fisika_kimia !== null ? (string) $l->fisika_kimia : '',
    'lengkap' => $l->lengkap !== null ? (string) $l->lengkap : '',
    'catatan' => $l->catatan ?? '',
    'alamat' => $l->alamat ?? '',
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
    document.getElementById('create_laboratorium').value = '';
    document.getElementById('create_mikrobiologi').value = '';
    document.getElementById('create_fisika_kimia').value = '';
    document.getElementById('create_lengkap').value = '';
    document.getElementById('create_catatan').value = '';
    document.getElementById('create_alamat').value = '';
    document.getElementById('createModal').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(id) {
    closeFab();
    var l = labData.find(function(x) { return x.id === id; });
    if (!l) return;
    document.getElementById('editForm').action = '/harga-lab/' + id;
    document.getElementById('edit_kota').value = l.kota;
    document.getElementById('edit_laboratorium').value = l.laboratorium;
    document.getElementById('edit_mikrobiologi').value = l.mikrobiologi;
    document.getElementById('edit_fisika_kimia').value = l.fisika_kimia;
    document.getElementById('edit_lengkap').value = l.lengkap;
    document.getElementById('edit_catatan').value = l.catatan;
    document.getElementById('edit_alamat').value = l.alamat;
    document.getElementById('editModal').classList.remove('hidden');
    autoResize(document.getElementById('edit_catatan'));
    autoResize(document.getElementById('edit_alamat'));
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
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
