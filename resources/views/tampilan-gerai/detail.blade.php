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
    <div class="flex gap-2">
        <a href="/tampilan-gerai" style="background:#F3F4F6;color:#374151" class="px-3 py-1.5 text-xs font-medium rounded-lg hover:opacity-80">Kembali</a>
    </div>
</div>

@php
    $badge = match ($prefix) {
        'pra-monitoring' => ['#F3E8FF', '#7C3AED', 'Pra-Monitoring'],
        're-monitoring' => ['#FEF3C7', '#D97706', 'Re-Monitoring'],
        default => ['#DBEAFE', '#1D4ED8', 'Monitoring'],
    };
    $totalPhotos = $blocks->sum(fn ($b) => $b->photos->count());
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

<div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700">Data Tampilan Gerai</h3>
    </div>
    <div class="p-4 sm:p-5 flex items-center gap-6">
        <div>
            <p class="text-2xl font-bold text-blue-600">{{ $blocks->count() }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Blok Keterangan</p>
        </div>
        <div class="w-px self-stretch bg-gray-200"></div>
        <div>
            <p class="text-2xl font-bold text-blue-600">{{ $totalPhotos }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Foto</p>
        </div>
    </div>
</div>

@php $blockIdx = 0; @endphp
@forelse ($blocks as $block)
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-700">{{ $loop->iteration }}. {{ $block->keterangan ?: 'Tanpa keterangan' }}</h3>
            @if ($block->photos->isNotEmpty())
                <span class="shrink-0 text-[10px] font-medium text-gray-500">{{ $block->photos->count() }} foto</span>
            @endif
        </div>
        <div class="p-4 sm:p-5">
            @if ($block->photos->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    @foreach ($block->photos as $photo)
                        <button type="button" onclick="openLightbox({{ $blockIdx }}, {{ $loop->index }})" class="group relative rounded-lg overflow-hidden border border-gray-200 bg-gray-100 block" style="aspect-ratio:1/1;cursor:zoom-in">
                            <img src="{{ $photo->url() }}" alt="Foto tampilan gerai" loading="lazy"
                                 style="width:100%;height:100%;object-fit:cover" class="group-hover:opacity-90 transition-opacity">
                        </button>
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
    <button id="tgPrev" type="button" onclick="event.stopPropagation(); prevPhoto()" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.5rem;line-height:1;border-radius:9999px;background:rgba(255,255,255,0.12);cursor:pointer;visibility:hidden" aria-label="Sebelumnya">&lsaquo;</button>
    <button id="tgNext" type="button" onclick="event.stopPropagation(); nextPhoto()" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.5rem;line-height:1;border-radius:9999px;background:rgba(255,255,255,0.12);cursor:pointer;visibility:hidden" aria-label="Berikutnya">&rsaquo;</button>
    <img id="tgLightboxImg" src="" alt="Foto tampilan gerai" style="max-width:92vw;max-height:82vh;object-fit:contain;border-radius:0.5rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);background:#111">
    <span id="tgLightboxCount" style="position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.9);font-size:0.875rem;font-weight:500"></span>
</div>
@endsection

@push('scripts')
<script>
var tgPhotoGroups = {!! json_encode($blocks->map(fn ($b) => $b->photos->map(fn ($p) => $p->url())->values())->values(), JSON_HEX_TAG) !!};
var tgBlock = 0;
var tgPhoto = 0;

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
    var group = tgPhotoGroups[tgBlock];
    if (!group || tgPhoto <= 0) return;
    tgPhoto--;
    renderLightbox();
}

function nextPhoto() {
    var group = tgPhotoGroups[tgBlock];
    if (!group || tgPhoto >= group.length - 1) return;
    tgPhoto++;
    renderLightbox();
}

document.addEventListener('keydown', function (e) {
    var lightbox = document.getElementById('tgLightbox');
    if (lightbox.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') prevPhoto();
    if (e.key === 'ArrowRight') nextPhoto();
});
</script>
@endpush
