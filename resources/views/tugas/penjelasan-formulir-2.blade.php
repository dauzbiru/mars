@extends('layouts.admin')

@section('title', $title . ' - MARS')

@section('content')
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800">{{ $title }}</h2>
        <div class="flex gap-2">
            <button type="button" onclick="document.getElementById('modalImport').classList.remove('hidden')"
                class="px-3 py-2 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100">
                Import Excel
            </button>
            <button type="button" onclick="document.getElementById('modalTambah').classList.remove('hidden')"
                class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                + Tambah
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[600px]">
            <thead>
                <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                    <th class="px-4 sm:px-6 py-3 w-10">No</th>
                    <th class="px-4 sm:px-6 py-3">Kondisi</th>
                    <th class="px-4 sm:px-6 py-3">Penjelasan</th>
                    <th class="px-4 sm:px-6 py-3 w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 text-xs text-gray-400 font-mono">{{ $loop->iteration }}</td>
                        <td class="px-4 sm:px-6 py-3 text-sm text-gray-800">{{ $item->kondisi }}</td>
                        <td class="px-4 sm:px-6 py-3 text-sm text-gray-600">{{ $item->penjelasan ?? '-' }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">
                            <button type="button" onclick="editItem(@js($item->id), @js($item->kondisi), @js($item->penjelasan ?? ''))"
                                class="inline-block px-2 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEF3C7;color:#D97706">Edit</button>
                            <form method="POST" action="/tugas/penjelasan-formulir/{{ $item->id }}" onsubmit="showConfirm('Hapus item ini?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                @csrf @method('DELETE')
                                <button class="inline-block px-2 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#DC2626">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Import Excel --}}
<div id="modalImport" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-1">Import Excel</h3>
        <p class="text-xs text-gray-500 mb-4">Upload file Excel dengan kolom <strong>Kondisi</strong> dan <strong>Penjelasan</strong>.</p>
        <div class="space-y-4">
            <a href="/tugas/penjelasan-formulir/{{ $formulir }}/template" target="_blank"
                class="inline-block px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                Download Template Excel
            </a>
            <form method="POST" action="/tugas/penjelasan-formulir/{{ $formulir }}/import" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div id="modalTambah" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-2xl mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Tambah Item</h3>
        <form method="POST" action="/tugas/penjelasan-formulir/{{ $formulir }}">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi</label>
                <textarea name="kondisi" required rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tulis kondisi..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Penjelasan</label>
                <textarea name="penjelasan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tulis penjelasan..."></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-80" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-2xl mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Edit Item</h3>
        <form method="POST" id="formEdit">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi</label>
                <textarea name="kondisi" id="editKondisi" required rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Penjelasan</label>
                <textarea name="penjelasan" id="editPenjelasan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-80" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editItem(id, kondisi, penjelasan) {
    document.getElementById('formEdit').action = '/tugas/penjelasan-formulir/' + id;
    document.getElementById('editKondisi').value = kondisi;
    document.getElementById('editPenjelasan').value = penjelasan;
    document.getElementById('modalEdit').classList.remove('hidden');
}
</script>
@endsection
