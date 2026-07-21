@extends('layouts.admin')

@section('title', $category->name . ' - ' . $report->gerai->nama_gerai)

@section('content')
<div class="max-w-lg mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="min-w-0 flex-1">
            <h2 class="text-base font-bold text-gray-900 truncate">{{ $report->gerai->kode_gerai }} - {{ $report->gerai->nama_gerai }}
                @if($report->is_pairing)
                    <span style="background:#6B7280;color:#fff;font-size:10px;padding:1px 6px;border-radius:4px;margin-left:6px;vertical-align:middle;">Pairing</span>
                @endif
            </h2>
            <p class="text-xs text-gray-500">{{ $category->name }}</p>
        </div>
        <div class="text-right shrink-0">
            <p class="text-xs text-gray-400">Skor</p>
            <p id="liveScore" class="text-lg font-bold text-blue-600">0</p>
        </div>
    </div>

    <form method="POST" action="/{{ $prefix }}/{{ $report->id }}/assessment/{{ $category->id }}/form">
        @csrf

        @if ($category->items->isNotEmpty())
            <div class="space-y-3 mb-5">
                @foreach ($category->items as $item)
                    @php $result = $results->get($item->id) @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-gray-800">{{ $loop->iteration }}. {{ $item->name }}</h4>
                                @if($item->bobot)
                                    @php
                                        $selected = $result ? $result->criterion_id : null;
                                        $selectedCriterion = $selected ? $item->criteria->firstWhere('id', $selected) : null;
                                        $count = $item->criteria->count();
                                        $interval = $count > 1 ? $item->bobot / ($count - 1) : 0;
                                        $nilai = $selectedCriterion ? $item->bobot - ($interval * $item->criteria->search(fn($c) => $c->id === $selectedCriterion->id)) : $item->bobot;
                                    @endphp
                                    <span data-bobot="{{ $item->bobot }}" data-interval="{{ $interval }}" class="score-badge text-xs font-bold px-2 py-0.5 rounded-full {{ $nilai < $item->bobot ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">{{ $nilai == (int)$nilai ? (int)$nilai : number_format($nilai, 2, '.', '') }}/{{ $item->bobot == (int)$item->bobot ? (int)$item->bobot : $item->bobot }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($item->criteria->isNotEmpty())
                            <div class="p-3 space-y-1.5">
                                    @php
                                        $count = $item->criteria->count();
                                        $interval = $item->bobot && $count > 1 ? $item->bobot / ($count - 1) : 0;
                                    @endphp
                                @foreach ($item->criteria as $c)
                                    @php
                                        $isSelected = $result ? $result->criterion_id === $c->id : $loop->first;
                                        $nilai = $interval !== null ? $item->bobot - ($interval * $loop->index) : null;
                                    @endphp
                                    <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition {{ $isSelected ? 'bg-blue-50 border-blue-400' : 'border-gray-100 hover:bg-gray-50' }}">
                                        <div class="shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $isSelected ? 'border-blue-600' : 'border-gray-300' }}">
                                            @if ($isSelected)
                                                <div class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
                                            @endif
                                        </div>
                                        <input type="radio" name="criterion[{{ $item->id }}]" value="{{ $c->id }}" {{ $isSelected ? 'checked' : '' }} data-nilai="{{ $nilai ?? 0 }}" class="opacity-0 absolute inset-0 w-full h-full cursor-pointer z-10" onchange="recalculateScore()">
                                        <span class="text-sm text-gray-700 flex-1">{{ $c->description }} @if($nilai !== null)<span class="text-xs text-gray-400 ml-1">({{ $nilai == (int)$nilai ? (int)$nilai : number_format($nilai, 2, '.', '') }})</span>@endif</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 text-sm text-gray-400 italic">Belum ada opsi penilaian.</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-sm text-gray-500">Belum ada item.</div>
        @endif

        <button type="submit" class="w-full py-3 text-sm font-semibold rounded-xl shadow-sm hover:opacity-80 transition" style="background:#DCFCE7;color:#16A34A">Simpan</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
function updateRadioVisual(radio) {
    var name = radio.getAttribute('name');
    document.querySelectorAll('input[type=radio][name="' + name + '"]').forEach(function(r) {
        var lbl = r.closest('label');
        if (!lbl) return;
        var outer = lbl.querySelector('.rounded-full.border-2');
        if (r === radio) {
            lbl.classList.remove('border-gray-100', 'hover:bg-gray-50');
            lbl.classList.add('bg-blue-50', 'border-blue-400');
            if (outer) {
                outer.classList.remove('border-gray-300');
                outer.classList.add('border-blue-600');
                if (!outer.querySelector('.rounded-full.bg-blue-600')) {
                    var dot = document.createElement('div');
                    dot.className = 'w-2.5 h-2.5 rounded-full bg-blue-600';
                    outer.appendChild(dot);
                }
            }
        } else {
            lbl.classList.remove('bg-blue-50', 'border-blue-400');
            lbl.classList.add('border-gray-100', 'hover:bg-gray-50');
            if (outer) {
                outer.classList.remove('border-blue-600');
                outer.classList.add('border-gray-300');
                var dot = outer.querySelector('.rounded-full.bg-blue-600');
                if (dot) dot.remove();
            }
        }
    });
}

function recalculateScore() {
    var total = 0;
    document.querySelectorAll('input[type=radio][data-nilai]:checked').forEach(function(el) {
        total += parseFloat(el.getAttribute('data-nilai'));
        updateRadioVisual(el);
    });
    document.getElementById('liveScore').textContent = total % 1 === 0 ? total.toString() : total.toFixed(2);

    document.querySelectorAll('.score-badge[data-bobot]').forEach(function(badge) {
        var bobot = parseFloat(badge.dataset.bobot);
        var interval = parseFloat(badge.dataset.interval) || 0;
        var card = badge.closest('.bg-white');
        if (!card) return;
        var checked = card.querySelector('input[type=radio]:checked');
        if (!checked) return;
        var radios = Array.from(card.querySelectorAll('input[type=radio]'));
        var idx = radios.indexOf(checked);
        var nilai = bobot - (interval * idx);
        var display = nilai % 1 === 0 ? nilai.toString() : nilai.toFixed(2);
        badge.textContent = display + '/' + (bobot % 1 === 0 ? bobot.toString() : bobot.toFixed(2));
        badge.className = 'score-badge text-xs font-bold px-2 py-0.5 rounded-full ' + (nilai < bobot ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700');
    });
}
recalculateScore();
</script>
@endpush