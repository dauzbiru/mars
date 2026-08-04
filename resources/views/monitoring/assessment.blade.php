@extends('layouts.admin')

@section('title', 'Penilaian - ' . $report->gerai->nama_gerai)

@section('content')
@if (session('warning'))
    <div class="mb-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
        <strong>{{ session('warning') }}</strong>
        @if (session('incomplete'))
            <ul class="mt-1 list-disc list-inside">
                @foreach (session('incomplete') as $section)
                    <li>{{ $section }}</li>
                @endforeach
            </ul>
        @endif
        @if (session('unfilled'))
            <ul class="mt-1 list-disc list-inside text-xs text-yellow-700">
                @foreach (session('unfilled') as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

<div class="max-w-lg mx-auto">
    {{-- Score Card --}}
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 mb-5 text-white shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs text-blue-200 font-medium uppercase tracking-wide">Total Skor</p>
                <p class="text-4xl font-bold mt-1">{{ $totalScore == (int)$totalScore ? (int)$totalScore : number_format($totalScore, 2, '.', '') }}
                    <span style="background:rgba(255,255,255,0.25);color:#fff;font-size:11px;padding:2px 8px;border-radius:4px;vertical-align:middle;margin-left:6px;font-weight:500;">{{ match($prefix) { 'pra-monitoring' => 'Pra-Monitoring', 're-monitoring' => 'Re-Monitoring', default => 'Monitoring' } }}</span>
                </p>
                <div class="mt-3 flex items-center gap-4 text-xs text-blue-100">
                    <span>{{ $categories->sum(fn($c) => $c->items->count()) }} item</span>
                    <span>{{ $results->whereNotNull('criterion_id')->count() }} terisi</span>
                </div>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-bold">{{ $report->gerai->kode_gerai }}
                    @if($report->is_pairing)
                        <span style="background:rgba(255,255,255,0.25);color:#fff;font-size:10px;padding:1px 6px;border-radius:4px;margin-left:4px;vertical-align:middle;">Pairing</span>
                    @endif
                </p>
                <p class="text-xs text-blue-200 mt-0.5">{{ $report->gerai->nama_gerai }}</p>
                <p class="text-xs text-blue-200 mt-1">{{ $report->checkin_at->format('d-m-Y H:i') }}</p>
                <p class="text-xs text-blue-200 mt-0.5">{{ str_starts_with($report->gerai->no_telepon ?? '', '62') ? '0' . substr($report->gerai->no_telepon, 2) : ($report->gerai->no_telepon ?? '-') }}</p>
                <p class="text-xs text-blue-200 mt-0.5">{{ $report->gerai->franchisee }}</p>
            </div>
        </div>
    </div>

    {{-- Category Items --}}
    <div class="space-y-2 mb-5">
        @forelse ($categories as $cat)
            @php
                $total = $cat->items->count();
                $filled = $cat->items->filter(fn($i) => $results->get($i->id)?->criterion_id)->count();
                $allDone = $total > 0 && $filled === $total;
                $cs = $catScores[$cat->id] ?? ['score' => 0, 'max' => 0];
                $pct = $cs['max'] > 0 ? round(($cs['score'] / $cs['max']) * 100) : 0;
            @endphp
            <a href="/{{ $prefix }}/{{ $report->id }}/assessment/{{ $cat->id }}/form"
               class="block bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3.5 active:bg-gray-50 transition">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $allDone ? 'bg-green-100' : 'bg-gray-100' }}">
                            @if ($allDone)
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="text-xs font-bold text-gray-400">{{ $filled }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-800 truncate">{{ $cat->name }}</h3>
                            <p class="text-xs text-gray-400">{{ $filled }}/{{ $total }} item</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-3">
                        <span class="text-xs font-bold {{ $cs['max'] > 0 && $cs['score'] < $cs['max'] ? 'text-yellow-600' : ($cs['max'] > 0 && $cs['score'] >= $cs['max'] ? 'text-blue-600' : 'text-gray-400') }}">{{ $cs['score'] == (int)$cs['score'] ? (int)$cs['score'] : number_format($cs['score'], 2, '.', '') }}{{ $cs['max'] > 0 ? '/' . $cs['max'] : '' }}</span>
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
                {{-- Progress bar --}}
                @if ($cs['max'] > 0)
                <div class="mt-2.5 w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $allDone ? 'bg-green-500' : 'bg-blue-500' }}" style="width:{{ $pct }}%"></div>
                </div>
                @endif
            </a>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-sm text-gray-500">
                Belum ada kategori penilaian.
            </div>
        @endforelse
    </div>

    {{-- Temuan Monitoring --}}
    @php
        $hasFinding = $report->major || $report->minor || $report->peringatan_awal || $report->ttd_petugas || $report->ttd_pimpinan;
        $findingComplete = $hasFinding && count($incomplete) === 0;
        $findingPartial = $hasFinding && count($incomplete) > 0;
    @endphp
    <a href="/{{ $prefix }}/{{ $report->id }}/temuan"
       class="block bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3.5 mb-6 active:bg-gray-50 transition">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $findingComplete ? 'bg-green-100' : ($findingPartial ? 'bg-yellow-100' : 'bg-gray-100') }}">
                    @if ($findingComplete)
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    @elseif ($findingPartial)
                        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">{{ $prefix === 'evaluasi' ? 'Temuan Evaluasi' : ($prefix === 'pra-monitoring' ? 'Temuan Pra-Monitoring' : ($prefix === 're-monitoring' ? 'Temuan Re-Monitoring' : 'Temuan Monitoring')) }}</h3>
                    <p class="text-xs {{ $findingComplete ? 'text-green-600' : ($findingPartial ? 'text-yellow-600' : 'text-gray-400') }}">{{ $findingComplete ? 'Sudah terisi semua' : ($findingPartial ? 'Ada yang belum terisi' : 'Major, Minor, Peringatan Awal, TTD') }}</p>
                </div>
            </div>
            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </a>

    {{-- Bottom Actions --}}
    <div class="flex gap-3">
        @if ($report->submit_at)
        <form method="POST" action="/{{ $prefix }}/{{ $report->id }}/cancel" class="flex-1" onsubmit="showConfirm('Batalkan perubahan? Semua perubahan yang sudah disimpan akan dikembalikan ke keadaan awal.', function(){ this.submit(); }.bind(this)); return false;">
            @csrf
            <button type="submit" class="w-full py-3 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl active:bg-gray-50 transition">Batalkan</button>
        </form>
        @else
        <form method="POST" action="/{{ $prefix }}/{{ $report->id }}" class="flex-1" onsubmit="showConfirm('Batalkan laporan ini? Laporan akan dihapus.', function(){ this.submit(); }.bind(this)); return false;">
            @csrf @method('DELETE')
            <button type="submit" class="w-full py-3 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl active:bg-gray-50 transition">Batalkan</button>
        </form>
        @endif
        <form method="POST" action="/{{ $prefix }}/{{ $report->id }}/submit" id="submit-form" class="flex-1">
            @csrf
            <button type="submit" style="background:#3B82F6;color:#FFFFFF" class="w-full py-3 text-sm font-semibold rounded-xl shadow-sm hover:opacity-80 transition">Submit Laporan</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
var incomplete = @json($incomplete);

document.getElementById('submit-form').addEventListener('submit', function(e) {
    if (incomplete.length > 0) {
        e.preventDefault();
        var msg = 'Lengkapi bagian berikut sebelum submit:\n';
        incomplete.forEach(function(section) {
            msg += '\n• ' + section;
        });
        showAlert(msg);
    }
});
</script>
@endpush