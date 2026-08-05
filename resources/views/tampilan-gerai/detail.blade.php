@extends('layouts.admin')

@section('title', 'Tampilan Gerai - ' . ($report->gerai?->nama_gerai ?? 'Gerai'))

@section('content')
<div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div>
        <a href="/tampilan-gerai" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke Daftar Tampilan Gerai</a>
        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mt-1">{{ $report->gerai?->kode_gerai ?? '-' }} - {{ $report->gerai?->nama_gerai ?? '-' }}
            @if($report->is_pairing)
                <span style="background:#6B7280;color:#fff;font-size:11px;padding:2px 8px;border-radius:4px;margin-left:6px;vertical-align:middle;">Pairing</span>
            @endif
        </h2>
    </div>
</div>

@php
    $badge = match ($prefix) {
        'pra-monitoring' => ['#F3E8FF', '#7C3AED', 'Pra-Monitoring'],
        're-monitoring' => ['#FEF3C7', '#D97706', 'Re-Monitoring'],
        default => ['#DBEAFE', '#1D4ED8', 'Monitoring'],
    };
@endphp

<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Informasi Laporan</h3>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full">
        <tbody class="divide-y divide-gray-200">
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500 w-1/3">Kode Gerai</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->gerai?->kode_gerai ?? '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Nama Gerai</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->gerai?->nama_gerai ?? '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Franchisee</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->gerai?->franchisee ?? '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Tipe Laporan</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">
                    <span style="background:{{ $badge[0] }};color:{{ $badge[1] }}" class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded">{{ $badge[2] }}</span>
                </td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Lokasi Checkin</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->location ?? '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Checkin</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->checkin_at?->format('d-m-Y H:i:s') ?? '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Submit</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->submit_at?->format('d-m-Y H:i:s') ?? '-' }}</td>
            </tr>
            <tr>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-500">Petugas</td>
                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">{{ $report->user?->name ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

@php $blockIdx = 0; @endphp
@forelse ($blocks as $block)
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4" id="block-{{ $block->id }}" data-editing="0">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <span class="text-xl font-semibold text-gray-700 shrink-0">{{ $loop->iteration }}.</span>
                <span class="tg-ket-text text-xl font-semibold text-gray-700 flex-1 min-w-0">{{ $block->keterangan ?: '' }}</span>
                <input type="text" value="{{ $block->keterangan ?? '' }}" placeholder="Tambah keterangan..."
                    class="tg-ket-input flex-1 min-w-0 border-0 bg-transparent text-xl font-semibold text-gray-700 focus:outline-none focus:ring-0 p-0 hidden"
                    style="box-shadow:none"
                    onblur="saveKet({{ $block->id }}, this.value)" onkeydown="if(event.key==='Enter'){this.blur()}">
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($block->photos->isNotEmpty())
                    <span class="text-[10px] font-medium text-gray-500">{{ $block->photos->count() }} foto</span>
                @endif
                <button type="button" onclick="toggleEditBlock(this)" class="tg-edit-btn" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:6px;cursor:pointer;background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE">Edit</button>
                <button type="button" onclick="hapusBlock({{ $block->id }})" class="tg-del-btn" style="width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;border-radius:6px;background:rgba(220,38,38,0.08);cursor:pointer;color:#DC2626;font-size:12px" title="Hapus blok">&#10005;</button>
            </div>
        </div>
        <div class="p-4 sm:p-5">
            @if ($block->photos->isNotEmpty())
                <div class="flex gap-3 overflow-x-auto pb-2" style="scroll-snap-type:x mandatory">
                    @foreach ($block->photos as $photo)
                        <div class="relative shrink-0 group" style="width:120px;height:120px;scroll-snap-align:start">
                            <button type="button" onclick="openLightbox({{ $blockIdx }}, {{ $loop->index }})" class="block w-full h-full rounded-lg overflow-hidden border border-gray-200 bg-gray-100" style="cursor:pointer">
                                <img src="{{ $photo->url() }}" alt="Foto tampilan gerai" loading="lazy"
                                     style="width:100%;height:100%;object-fit:cover" class="group-hover:opacity-90 transition-opacity">
                            </button>
                            <button type="button" onclick="hapusFoto({{ $photo->id }})"
                                class="tg-photo-del hidden"
                                style="position:absolute;top:4px;right:4px;width:1.25rem;height:1.25rem;align-items:center;justify-content:center;border-radius:9999px;background:rgba(220,38,38,0.85);color:#fff;font-size:10px;cursor:pointer" title="Hapus foto">&#10005;</button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400 italic">Tanpa foto.</p>
            @endif
        </div>
        @php $blockIdx++; @endphp
    </div>
@empty
    <div class="bg-white rounded-xl shadow-md p-8 text-center text-sm text-gray-500">
        Belum ada data tampilan gerai untuk laporan ini.
    </div>
@endforelse

{{-- Lightbox Foto --}}
<div id="tgLightbox" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.9)" onclick="closeLightbox()">
    <button type="button" onclick="event.stopPropagation(); closeLightbox()" style="position:absolute;top:1rem;right:1rem;width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;line-height:1;border-radius:9999px;background:rgba(255,255,255,0.12);cursor:pointer" aria-label="Tutup">&times;</button>
    <button id="tgPrev" type="button" onclick="event.stopPropagation(); prevPhoto()" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;font-weight:300;line-height:1;border-radius:9999px;background:rgba(255,255,255,0.15);cursor:pointer;visibility:hidden" aria-label="Sebelumnya">&#10094;</button>
    <button id="tgNext" type="button" onclick="event.stopPropagation(); nextPhoto()" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;font-weight:300;line-height:1;border-radius:9999px;background:rgba(255,255,255,0.15);cursor:pointer;visibility:hidden" aria-label="Berikutnya">&#10095;</button>
    <img id="tgLightboxImg" src="" alt="Foto tampilan gerai" style="max-width:92vw;max-height:82vh;object-fit:contain;border-radius:0.5rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);background:#111">
    <span id="tgLightboxCount" style="position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.9);font-size:0.875rem;font-weight:500"></span>
</div>
@endsection

@push('scripts')
<script>
var tgPhotoGroups = {!! json_encode($blocks->map(fn ($b) => $b->photos->map(fn ($p) => $p->url())->values())->values(), JSON_HEX_TAG) !!};
var tgBlock = 0;
var tgPhoto = 0;
var tgCsrf = '{{ csrf_token() }}';
var tgOrigKet = {};

function openLightbox(blockIdx, photoIdx) {
    tgBlock = blockIdx;
    tgPhoto = photoIdx;
    renderLightbox();
    document.getElementById('tgLightbox').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function renderLightbox() {
    var group = tgPhotoGroups[tgBlock];
    if (!group || !group.length) return;
    if (tgPhoto >= group.length) tgPhoto = group.length - 1;
    if (tgPhoto < 0) tgPhoto = 0;
    document.getElementById('tgLightboxImg').src = group[tgPhoto];
    document.getElementById('tgLightboxCount').textContent = (tgPhoto + 1) + ' / ' + group.length;
    document.getElementById('tgPrev').style.visibility = tgPhoto > 0 ? 'visible' : 'hidden';
    document.getElementById('tgNext').style.visibility = tgPhoto < group.length - 1 ? 'visible' : 'hidden';
}

function closeLightbox() {
    document.getElementById('tgLightbox').classList.add('hidden');
    document.body.style.overflow = '';
}

function prevPhoto() {
    var g = tgPhotoGroups[tgBlock];
    if (!g || tgPhoto <= 0) return;
    tgPhoto--;
    renderLightbox();
}

function nextPhoto() {
    var g = tgPhotoGroups[tgBlock];
    if (!g || tgPhoto >= g.length - 1) return;
    tgPhoto++;
    renderLightbox();
}

function saveKet(blockId, val) {
    fetch('/tampilan-gerai/block/' + blockId, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tgCsrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ keterangan: val })
    });
}

function toggleEditBlock(btn) {
    var card = btn.closest('[id^="block-"]');
    var editing = card.dataset.editing === '1';
    var blockId = card.id.replace('block-', '');

    var textEl = card.querySelector('.tg-ket-text');
    var inputEl = card.querySelector('.tg-ket-input');
    var photoDels = card.querySelectorAll('.tg-photo-del');
    var delBtn = card.querySelector('.tg-del-btn');

    if (!editing) {
        tgOrigKet[blockId] = inputEl.value;
        card.dataset.editing = '1';
        textEl.classList.add('hidden');
        inputEl.classList.remove('hidden');
        inputEl.focus();
        photoDels.forEach(function (d) { d.classList.remove('hidden'); d.style.display = 'flex'; });
        btn.textContent = 'Selesai';
        btn.style.background = '#F0FDF4';
        btn.style.color = '#16A34A';
        btn.style.borderColor = '#BBF7D0';
        delBtn.innerHTML = 'Batal';
        delBtn.style.width = 'auto';
        delBtn.style.padding = '4px 10px';
        delBtn.style.fontSize = '11px';
        delBtn.style.fontWeight = '600';
        delBtn.style.background = '#FEF3C7';
        delBtn.style.color = '#D97706';
        delBtn.style.borderColor = '#FDE68A';
        delBtn.style.border = '1px solid #FDE68A';
        delBtn.onclick = function () { cancelEditBlock(card, btn, delBtn); };
    } else {
        saveKet(blockId, inputEl.value);
        textEl.textContent = inputEl.value || '';
        textEl.classList.remove('hidden');
        inputEl.classList.add('hidden');
        photoDels.forEach(function (d) { d.classList.add('hidden'); d.style.display = ''; });
        btn.textContent = 'Edit';
        btn.style.background = '#EFF6FF';
        btn.style.color = '#2563EB';
        btn.style.borderColor = '#BFDBFE';
        delBtn.innerHTML = '&#10005;';
        delBtn.style.width = '1.75rem';
        delBtn.style.padding = '';
        delBtn.style.fontSize = '12px';
        delBtn.style.fontWeight = '';
        delBtn.style.background = 'rgba(220,38,38,0.08)';
        delBtn.style.color = '#DC2626';
        delBtn.style.border = '';
        delBtn.onclick = function () { hapusBlock(parseInt(blockId)); };
        card.dataset.editing = '0';
    }
}

function cancelEditBlock(card, editBtn, delBtn) {
    var blockId = card.id.replace('block-', '');
    var textEl = card.querySelector('.tg-ket-text');
    var inputEl = card.querySelector('.tg-ket-input');
    var photoDels = card.querySelectorAll('.tg-photo-del');

    inputEl.value = tgOrigKet[blockId] || '';
    textEl.classList.remove('hidden');
    inputEl.classList.add('hidden');
    photoDels.forEach(function (d) { d.classList.add('hidden'); d.style.display = ''; });
    editBtn.textContent = 'Edit';
    editBtn.style.background = '#EFF6FF';
    editBtn.style.color = '#2563EB';
    editBtn.style.borderColor = '#BFDBFE';
    delBtn.innerHTML = '&#10005;';
    delBtn.style.width = '1.75rem';
    delBtn.style.padding = '';
    delBtn.style.fontSize = '12px';
    delBtn.style.fontWeight = '';
    delBtn.style.background = 'rgba(220,38,38,0.08)';
    delBtn.style.color = '#DC2626';
    delBtn.style.border = '';
    delBtn.onclick = function () { hapusBlock(parseInt(blockId)); };
    card.dataset.editing = '0';
}

function hapusFoto(photoId, btn) {
    if (!confirm('Hapus foto ini?')) return;
    fetch('/tampilan-gerai/photo/' + photoId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': tgCsrf, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); }).then(function () { location.reload(); });
}

function hapusBlock(blockId) {
    if (!confirm('Hapus blok ini beserta semua fotonya?')) return;
    fetch('/tampilan-gerai/block/' + blockId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': tgCsrf, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); }).then(function () { location.reload(); });
}

document.addEventListener('keydown', function (e) {
    var lb = document.getElementById('tgLightbox');
    if (lb.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') prevPhoto();
    if (e.key === 'ArrowRight') nextPhoto();
});
</script>
@endpush
