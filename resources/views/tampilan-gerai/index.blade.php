@extends('layouts.admin')

@section('title', 'Data Tampilan Gerai - MARS')

@section('content')
<div class="bg-white rounded-xl shadow-md">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800">Data Tampilan Gerai</h2>
    </div>

    @if ($groups->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full min-w-[600px]">
            <thead>
                <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider">
                    <th class="px-4 sm:px-6 py-3">Gerai</th>
                    <th class="px-4 sm:px-6 py-3">Tipe</th>
                    <th class="px-4 sm:px-6 py-3">Checkin</th>
                    <th class="px-4 sm:px-6 py-3">Petugas</th>
                    <th class="px-4 sm:px-6 py-3">Submit</th>
                    <th class="px-4 sm:px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($groups as $group)
                    @php
                        $report = $group->first()->reportable;
                        $prefix = match (class_basename($report)) {
                            'PraMonitoringReport' => 'pra-monitoring',
                            'ReMonitoringReport' => 're-monitoring',
                            default => 'monitoring',
                        };
                        $badge = match ($prefix) {
                            'pra-monitoring' => ['#F3E8FF', '#7C3AED', 'Pra-Monitoring'],
                            're-monitoring' => ['#FEF3C7', '#D97706', 'Re-Monitoring'],
                            default => ['#DBEAFE', '#1D4ED8', 'Monitoring'],
                        };
                        $gerai = $report->gerai;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-800">
                            <span class="font-medium">{{ $gerai?->kode_gerai ?? '-' }}</span> - {{ $gerai?->nama_gerai ?? '-' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3">
                            <span style="background:{{ $badge[0] }};color:{{ $badge[1] }}" class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded">{{ $badge[2] }}</span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $report->checkin_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $report->user?->name ?? '-' }}</td>
                        <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $report->submit_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">
                            <a href="/tampilan-gerai/{{ $prefix }}/{{ $report->id }}/detail"
                                style="background:#DBEAFE;color:#2563EB" class="inline-block px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center text-sm text-gray-500">
        Belum ada data.
    </div>
    @endif
</div>
@endsection
