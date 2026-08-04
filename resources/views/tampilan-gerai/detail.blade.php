@extends('layouts.admin')

@section('title', 'Detail Tampilan Gerai - MARS')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Detail Tampilan Gerai</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $report->gerai?->kode_gerai ?? '-' }} - {{ $report->gerai?->nama_gerai ?? '-' }}</p>
            </div>
            <a href="/tampilan-gerai" class="shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg" style="background:#F3F4F6;color:#374151">Kembali</a>
        </div>
    </div>

    @if ($blocks->isNotEmpty())
        <div class="space-y-3">
            @foreach ($blocks as $block)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-sm text-gray-700 whitespace-pre-wrap mb-2">{{ $block->keterangan ?: '(tanpa keterangan)' }}</p>
                @if ($block->photos->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach ($block->photos as $photo)
                    <a href="{{ $photo->url() }}" target="_blank" class="rounded-lg overflow-hidden border border-gray-200 block" style="width:96px;height:96px">
                        <img src="{{ $photo->url() }}" style="width:100%;height:100%;object-fit:cover" loading="lazy" alt="Foto tampilan gerai">
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-gray-400 italic">Tanpa foto</p>
                @endif
            </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-sm text-gray-500">
            Belum ada data tampilan gerai.
        </div>
    @endif
</div>
@endsection
