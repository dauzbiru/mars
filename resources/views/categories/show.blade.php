@extends('layouts.admin')

@section('title', $category->name . ' - Tugas')

@push('head')
<style>
.sortable-ghost { opacity: 0.5; background: #eff6ff; }
.sortable-drag { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); transform: scale(1.02); }
</style>
@endpush

@section('content')
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h2 class="text-lg sm:text-xl font-bold text-gray-800 mt-1">{{ $category->name }}</h2>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="openBobotModal()"
                class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700">Atur Bobot</button>
            <button type="button" onclick="openModal()"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">+ Item</button>
        </div>
    </div>

    @if ($items->isNotEmpty())
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700">Daftar Item</h3>
            </div>

            <div class="divide-y divide-gray-100" id="sortable-items">
                @foreach ($items as $item)
                    <div class="p-4 sm:p-5" data-item-id="{{ $item->id }}">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                <span class="drag-handle cursor-grab text-gray-300 hover:text-gray-500">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6h2v2H8V6zm6 0h2v2h-2V6zM8 11h2v2H8v-2zm6 0h2v2h-2v-2zm-6 5h2v2H8v-2zm6 0h2v2h-2v-2z"/></svg>
                                </span>
                                <span class="item-number">{{ $loop->iteration }}.</span>
                                {{ $item->name }}
                                @if($item->bobot) <span class="text-xs text-gray-400 font-normal">(bobot {{ $item->bobot }})</span> @endif
                            </h4>
                            <div class="flex gap-1 shrink-0">
                                <button onclick="openEditItemModal({{ $item->id }})" class="p-1 cursor-pointer" style="background:#FEF3C7;color:#D97706">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button type="button" onclick="deleteItem(@js($item->id), @js($item->name))" class="p-1 text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        @if ($item->criteria->isNotEmpty())
                            <ul class="list-disc list-inside space-y-1 mb-3">
                                @foreach ($item->criteria as $c)
                                    <li class="text-sm text-gray-600">{{ $c->description }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-gray-400 italic mb-2">Belum ada opsi.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-md p-8 text-center text-sm text-gray-500">
            Belum ada item. <a href="#" onclick="openModal(); return false;" class="text-blue-600 hover:underline">Tambah item</a>
        </div>
    @endif

    <form id="deleteForm" method="POST" style="display:none">
        @csrf @method('DELETE')
    </form>

    {{-- Modal Tambah Item + Opsi --}}
    <div id="itemModal" class="fixed inset-0 bg-black/40 z-40 flex items-center justify-center hidden" onclick="if (event.target===this) closeModal()">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Item</h3>
            <form method="POST" action="/categories/{{ $category->id }}/items">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Item</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bobot</label>
                    <input type="number" step="0.01" name="bobot" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Opsi Penilaian</label>
                    <div class="space-y-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <input type="text" name="criteria[]" placeholder="Opsi {{ $i }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                        @endfor
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-80" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Atur Bobot --}}
    <div id="bobotModal" class="fixed inset-0 bg-black/40 z-40 flex items-center justify-center hidden" onclick="if (event.target===this) closeBobotModal()">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Atur Bobot</h3>
            @if ($items->isNotEmpty())
                <form method="POST" action="/categories/{{ $category->id }}/items/bobot">
                    @csrf @method('PUT')
                    <div class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            <div class="py-3 flex items-center gap-3">
                                <span class="text-sm text-gray-700 flex-1">{{ $item->name }}</span>
                                <input type="number" step="0.01" name="bobot[{{ $item->id }}]" value="{{ $item->bobot }}" placeholder="Bobot"
                                    class="w-24 px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                            </div>
                        @endforeach
                    </div>
                    <div class="flex gap-2 justify-end mt-4">
                        <button type="button" onclick="closeBobotModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-80" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                    </div>
                </form>
            @else
                <p class="text-sm text-gray-400 italic">Belum ada item.</p>
            @endif
        </div>
    </div>
    {{-- Modal Edit Item --}}
    <div id="editItemModal" class="fixed inset-0 bg-black/40 z-40 flex items-center justify-center hidden" onclick="if (event.target===this) closeEditItemModal()">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Item</h3>
            <form id="editItemForm" method="POST" action="">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Item</label>
                    <input id="edit_item_name" type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bobot</label>
                    <input id="edit_item_bobot" type="number" step="0.01" name="bobot" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                </div>
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Opsi</label>
                        <button type="button" onclick="editAddOpsi()" class="text-xs text-blue-600 hover:text-blue-800 font-medium cursor-pointer">+ Opsi</button>
                    </div>
                    <div id="editOpsiList" class="space-y-1.5"></div>
                    <div id="editInlineAdd" class="hidden mt-2 flex items-center gap-2">
                        <input id="edit_opsi_input" type="text" placeholder="Tulis opsi..." autocomplete="off"
                            class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();editSaveOpsi();}">
                        <button type="button" onclick="editSaveOpsi()" class="px-3 py-1.5 text-xs font-medium rounded-lg hover:opacity-80 cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                        <button type="button" onclick="editCancelOpsi()" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-300 cursor-pointer">Batal</button>
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="closeEditItemModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg hover:opacity-80 cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
var itemData = {!! json_encode($items->map(fn($i) => [
    'id' => $i->id,
    'name' => $i->name,
    'bobot' => $i->bobot,
    'criteria' => $i->criteria->map(fn($c) => ['id' => $c->id, 'description' => $c->description]),
]), JSON_HEX_TAG) !!};
var editOpsiItemId = null;
var sortable = new Sortable(document.getElementById('sortable-items'), {
    handle: '.drag-handle',
    animation: 200,
    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    onEnd: function() {
        var items = [];
        document.querySelectorAll('#sortable-items [data-item-id]').forEach(function(el) {
            items.push(el.dataset.itemId);
        });

        var formData = new FormData();
        formData.append('_method', 'PUT');
        items.forEach(function(id) {
            formData.append('items[]', id);
        });

        fetch('/categories/{{ $category->id }}/items/reorder', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        }).then(function() {
            document.querySelectorAll('#sortable-items [data-item-id]').forEach(function(el, i) {
                el.querySelector('.item-number').textContent = (i + 1) + '.';
            });
        });
    }
});

function deleteItem(id, name) {
    showConfirm('Hapus item ' + name + '?', function() {
        var form = document.getElementById('deleteForm');
        form.action = '/items/' + id;
        form.submit();
    });
}

function openModal() {
    document.getElementById('itemModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

function openBobotModal() {
    document.getElementById('bobotModal').classList.remove('hidden');
}

function closeBobotModal() {
    document.getElementById('bobotModal').classList.add('hidden');
}

function openEditItemModal(id) {
    var i = itemData.find(function(x) { return x.id === id; });
    if (!i) return;
    editOpsiItemId = id;
    document.getElementById('editItemForm').action = '/items/' + id;
    document.getElementById('edit_item_name').value = i.name;
    document.getElementById('edit_item_bobot').value = i.bobot;
    renderEditOpsi(i.criteria);
    document.getElementById('editItemModal').classList.remove('hidden');
}
function closeEditItemModal() {
    document.getElementById('editItemModal').classList.add('hidden');
    editCancelOpsi();
}

var editOpsiSortable = null;

function renderEditOpsi(criteria) {
    var container = document.getElementById('editOpsiList');
    container.innerHTML = '';
    if (!criteria || criteria.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-400 italic">Belum ada opsi.</p>';
        return;
    }
    criteria.forEach(function(c, i) {
        var row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-2 py-1';
        row.dataset.cid = c.id;
        row.innerHTML =
            '<div class="flex items-center gap-2 flex-1 min-w-0">' +
                '<span class="drag-handle cursor-grab text-gray-300 hover:text-gray-500 shrink-0">' +
                    '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6h2v2H8V6zm6 0h2v2h-2V6zM8 11h2v2H8v-2zm6 0h2v2h-2v-2zm-6 5h2v2H8v-2zm6 0h2v2h-2v-2z"/></svg>' +
                '</span>' +
                '<span class="text-xs text-gray-400 w-4 shrink-0">' + (i + 1) + '.</span>' +
                '<span class="text-sm text-gray-700 truncate">' + c.description + '</span>' +
            '</div>' +
            '<div class="flex gap-1 shrink-0">' +
                '<button type="button" onclick="editInlineOpsi(this)" class="text-blue-600 hover:text-blue-800 cursor-pointer p-0.5">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>' +
                '</button>' +
                '<button type="button" onclick="editDeleteOpsi(this)" class="text-red-500 hover:text-red-700 cursor-pointer p-0.5">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                '</button>' +
            '</div>';
        container.appendChild(row);
    });
    if (editOpsiSortable) editOpsiSortable.destroy();
    editOpsiSortable = new Sortable(container, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            var ids = [];
            container.querySelectorAll('[data-cid]').forEach(function(el) {
                ids.push(el.dataset.cid);
            });
            ids.forEach(function(id, idx) {
                var row = container.querySelector('[data-cid="' + id + '"]');
                if (row) row.querySelector('.w-4').textContent = (idx + 1) + '.';
            });
            var formData = new FormData();
            formData.append('_method', 'PUT');
            ids.forEach(function(id) {
                formData.append('items[]', id);
            });
            fetch('/items/' + editOpsiItemId + '/criteria/reorder', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            });
        }
    });
}

function editInlineOpsi(btn) {
    var row = btn.closest('[data-cid]');
    var cid = row.dataset.cid;
    var span = row.querySelector('.truncate');
    var currentVal = span.textContent;
    span.innerHTML = '<input type="text" value="' + currentVal.replace(/"/g, '&quot;') + '" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">';
    var input = span.querySelector('input');
    input.focus();
    var actionsDiv = row.querySelector('.flex.gap-1');
    actionsDiv.innerHTML =
        '<button type="button" onclick="editSaveInlineOpsi(this)" class="cursor-pointer text-xs font-medium px-1" style="background:#DCFCE7;color:#16A34A">Simpan</button>' +
        '<button type="button" onclick="editCancelInlineOpsi(this)" class="text-gray-500 hover:text-gray-700 cursor-pointer text-xs font-medium px-1">Batal</button>';
}

function editSaveInlineOpsi(btn) {
    var row = btn.closest('[data-cid]');
    var cid = row.dataset.cid;
    var input = row.querySelector('input');
    var val = input.value.trim();
    if (!val) return;

    var formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('description', val);

    fetch('/items/' + editOpsiItemId + '/criteria/' + cid, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(function(r) { return r.json(); }).then(function(resp) {
        if (resp.success) {
            row.querySelector('.truncate').textContent = val;
            var actionsDiv = row.querySelector('.flex.gap-1');
            actionsDiv.innerHTML =
                '<button type="button" onclick="editInlineOpsi(this)" class="text-blue-600 hover:text-blue-800 cursor-pointer p-0.5">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>' +
                '</button>' +
                '<button type="button" onclick="editDeleteOpsi(this)" class="text-red-500 hover:text-red-700 cursor-pointer p-0.5">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                '</button>';
            var item = itemData.find(function(x) { return x.id === editOpsiItemId; });
            if (item) {
                var c = item.criteria.find(function(x) { return x.id == cid; });
                if (c) c.description = val;
            }
        }
    });
}

function editCancelInlineOpsi(btn) {
    var row = btn.closest('[data-cid]');
    var cid = row.dataset.cid;
    var item = itemData.find(function(x) { return x.id === editOpsiItemId; });
    var c = item ? item.criteria.find(function(x) { return x.id == cid; }) : null;
    var original = c ? c.description : '';
    row.querySelector('.truncate').textContent = original;
    var actionsDiv = row.querySelector('.flex.gap-1');
    actionsDiv.innerHTML =
        '<button type="button" onclick="editInlineOpsi(this)" class="text-blue-600 hover:text-blue-800 cursor-pointer p-0.5">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>' +
        '</button>' +
        '<button type="button" onclick="editDeleteOpsi(this)" class="text-red-500 hover:text-red-700 cursor-pointer p-0.5">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
        '</button>';
}

function editDeleteOpsi(btn) {
    var row = btn.closest('[data-cid]');
    var cid = row.dataset.cid;
    if (!confirm('Hapus opsi ini?')) return;

    var formData = new FormData();
    formData.append('_method', 'DELETE');

    fetch('/items/' + editOpsiItemId + '/criteria/' + cid, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(function(r) { return r.json(); }).then(function(resp) {
        if (resp.success) {
            row.remove();
            var item = itemData.find(function(x) { return x.id === editOpsiItemId; });
            if (item) {
                item.criteria = item.criteria.filter(function(x) { return x.id != cid; });
            }
            var container = document.getElementById('editOpsiList');
            if (!container.children.length) {
                container.innerHTML = '<p class="text-xs text-gray-400 italic">Belum ada opsi.</p>';
            }
        }
    });
}

function editAddOpsi() {
    document.getElementById('editInlineAdd').classList.remove('hidden');
    document.getElementById('edit_opsi_input').focus();
}
function editCancelOpsi() {
    document.getElementById('editInlineAdd').classList.add('hidden');
    document.getElementById('edit_opsi_input').value = '';
}
function editSaveOpsi() {
    var input = document.getElementById('edit_opsi_input');
    var val = input.value.trim();
    if (!val) return;

    var formData = new FormData();
    formData.append('description', val);

    fetch('/items/' + editOpsiItemId + '/criteria', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(function(r) { return r.json(); }).then(function(resp) {
        if (resp.success && resp.criterion) {
            var item = itemData.find(function(x) { return x.id === editOpsiItemId; });
            if (item) {
                item.criteria.push({ id: resp.criterion.id, description: resp.criterion.description });
            }
            renderEditOpsi(item ? item.criteria : []);
            input.value = '';
            editCancelOpsi();
        }
    });
}
</script>
@endpush
