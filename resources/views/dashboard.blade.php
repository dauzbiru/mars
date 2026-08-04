@extends('layouts.admin')

@section('title', 'Dashboard - MARS')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
@endpush

@section('content')
    @if (isset($role) && $role === 'guest')
        <div class="max-w-lg mx-auto mt-16 text-center">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Selamat Datang, {{ auth()->user()->name }}</h1>
            <p class="text-gray-500 mb-8">Silakan pilih jenis laporan yang akan dibuat</p>
            <div class="flex flex-col gap-4">
                <a href="/monitoring"
                    style="background:#3B82F6;color:#FFFFFF"
                    class="block w-full px-6 py-5 text-lg font-semibold rounded-xl hover:opacity-80 transition">
                    + Buat Laporan Monitoring
                </a>
                <a href="/pra-monitoring"
                    style="background:#3B82F6;color:#FFFFFF"
                    class="block w-full px-6 py-5 text-lg font-semibold rounded-xl hover:opacity-80 transition">
                    + Buat Laporan Pra-Monitoring
                </a>
            </div>
        </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5">
            <p class="text-sm text-gray-500">Total Gerai</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalGerai }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5">
            <p class="text-sm text-gray-500">Monitoring {{ $periodeLabel }}</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $monitoringPeriode }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5">
            <p class="text-sm text-gray-500">Pra-Monitoring Bulan Ini</p>
            <p class="text-3xl font-bold text-gray-600 mt-1">{{ $praMonitoringBulanIni }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Monitoring Terbaru --}}
        <div class="bg-white rounded-xl shadow-md">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Monitoring Terbaru</h2>
                <a href="/report/monitoring" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
            </div>
            <div class="p-5">
                @if ($monitoringTerbaru->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada data monitoring.</p>
                @else
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2 font-medium">Gerai</th>
                                <th class="pb-2 font-medium">Petugas</th>
                                <th class="pb-2 font-medium">Tanggal</th>
                                <th class="pb-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monitoringTerbaru as $report)
                                <tr class="border-t">
                                    <td class="py-2.5 text-gray-800">{{ $report->gerai->nama_gerai }}</td>
                                    <td class="py-2.5 text-gray-500">{{ $report->user?->name ?? '-' }}</td>
                                    <td class="py-2.5 text-gray-500">{{ $report->checkin_at ? $report->checkin_at->format('d-m-Y') : '-' }}</td>
                                    <td class="py-2.5">
                                        @if ($report->submit_at)
                                            <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Selesai</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Proses</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Pra-Monitoring Terbaru --}}
        <div class="bg-white rounded-xl shadow-md">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Pra-Monitoring Terbaru</h2>
                <a href="/report/pra-monitoring" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
            </div>
            <div class="p-5">
                @if ($praMonitoringTerbaru->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada data pra-monitoring.</p>
                @else
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2 font-medium">Gerai</th>
                                <th class="pb-2 font-medium">Petugas</th>
                                <th class="pb-2 font-medium">Tanggal</th>
                                <th class="pb-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($praMonitoringTerbaru as $report)
                                <tr class="border-t">
                                    <td class="py-2.5 text-gray-800">{{ $report->gerai->nama_gerai }}</td>
                                    <td class="py-2.5 text-gray-500">{{ $report->user?->name ?? '-' }}</td>
                                    <td class="py-2.5 text-gray-500">{{ $report->checkin_at ? $report->checkin_at->format('d-m-Y') : '-' }}</td>
                                    <td class="py-2.5">
                                        @if ($report->submit_at)
                                            <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Selesai</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Proses</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Grafik Batang --}}
    <div class="bg-white rounded-xl shadow-md p-5 mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-semibold text-gray-800">Grade per Periode</h2>
            </div>
            <select id="periodSelect" onchange="loadChart(this.value)" class="text-sm border rounded-lg px-3 py-1.5 text-gray-700">
                @foreach ($periods as $p)
                    <option value="{{ $p->label }}" {{ $p->label === $selectedPeriod ? 'selected' : '' }}>{{ $p->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative" style="height: 300px;">
            <p id="chartLoading" class="text-center text-gray-400 text-sm pt-20">Memuat data grafik...</p>
            <canvas id="gradeChart" class="hidden"></canvas>
        </div>
    </div>
    @endif
@push('scripts')
<script>
var chart = null;

function buildChart(labels, data) {
    var canvas = document.getElementById('gradeChart');
    var loading = document.getElementById('chartLoading');
    canvas.classList.remove('hidden');
    loading.classList.add('hidden');

    var ctx = canvas.getContext('2d');
    if (chart) {
        chart.destroy();
    }

    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'A', data: data.map(function(d) { return d.A; }), backgroundColor: '#22c55e', borderRadius: 4 },
                { label: 'B', data: data.map(function(d) { return d.B; }), backgroundColor: '#3b82f6', borderRadius: 4 },
                { label: 'C', data: data.map(function(d) { return d.C; }), backgroundColor: '#eab308', borderRadius: 4 },
                { label: 'D', data: data.map(function(d) { return d.D; }), backgroundColor: '#f97316', borderRadius: 4 },
                { label: 'E', data: data.map(function(d) { return d.E; }), backgroundColor: '#ef4444', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            clip: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    offset: 2,
                    color: '#374151',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value) { return value > 0 ? value : ''; }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    ticks: { precision: 0, maxTicksLimit: 15 },
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function loadChart(period) {
    fetch('/dashboard/chart-data?period=' + encodeURIComponent(period))
        .then(function(r) { return r.json(); })
        .then(function(res) {
            buildChart(res.labels, res.data);
        }).catch(function(err) {
            console.error('Gagal memuat chart:', err);
        });
}

buildChart(@json($chartLabels), @json($chartData));
</script>
@endpush
@endsection
