@extends('layouts.admin')

@section('title', 'Nilai Pairing - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Nilai Pairing</h2>
            @if($selectedGerai)
            <a href="/nilai-pairing/excel?gerai_id={{ $selectedGerai->id }}"
                style="background:#059669;color:#fff" class="inline-block px-4 py-2 text-xs font-medium rounded-lg hover:opacity-80">Download Excel</a>
            @endif
        </div>

        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <form method="GET" action="/nilai-pairing">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Gerai</label>
                <select name="gerai_id" onchange="this.form.submit()"
                    class="w-full sm:w-80 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Gerai --</option>
                    @foreach($gerais as $g)
                        <option value="{{ $g->id }}" {{ $selectedGerai && $selectedGerai->id == $g->id ? 'selected' : '' }}>
                            {{ $g->kode_gerai }} - {{ $g->nama_gerai }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($selectedGerai)
        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="table-layout:auto;">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-4 py-3 text-left font-medium text-gray-500 sticky left-0 bg-gray-50">Item</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 bg-gray-50">Bobot</th>
                        @foreach($nonPairingUsers as $u)
                            <th class="px-4 py-3 text-center font-medium text-gray-500 whitespace-nowrap">{{ $u->name }}</th>
                        @endforeach
                        @foreach($pairingUsers as $u)
                            <th class="px-4 py-3 text-center font-medium text-blue-600 whitespace-nowrap">{{ $u->name }}</th>
                        @endforeach
                    </tr>
                    @if($nonPairingUsers->count() || $pairingUsers->count())
                    <tr class="border-b border-gray-200">
                        <th colspan="2" class="px-4 py-1 sticky left-0 bg-white"></th>
                        @for($i = 0; $i < $nonPairingUsers->count(); $i++)
                            <th class="px-4 py-1 text-center text-xs font-normal text-gray-400">Non-Pairing</th>
                        @endfor
                        @for($i = 0; $i < $pairingUsers->count(); $i++)
                            <th class="px-4 py-1 text-center text-xs font-normal text-blue-400">Pairing</th>
                        @endfor
                    </tr>
                    @endif
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($items as $cat)
                        @foreach($cat->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sticky left-0 bg-white">
                                    <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $item->bobot }}</td>
                                @foreach($nonPairingUsers as $u)
                                    <td class="px-4 py-3 text-center text-gray-600 text-xs">{{ $resultsByUser[$u->id][$item->id] ?? '-' }}</td>
                                @endforeach
                                @foreach($pairingUsers as $u)
                                    <td class="px-4 py-3 text-center text-blue-600 text-xs">{{ $resultsByUser[$u->id][$item->id] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $nonPairingUsers->count() + $pairingUsers->count() }}" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada data item.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-sm text-gray-500">Pilih gerai untuk melihat data pairing.</div>
        @endif
    </div>
@endsection
