@extends('layouts.admin')

@section('title', 'Performa Gerai - MARS')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
@endpush

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Performa Gerai</h2>
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                <div class="relative flex items-center gap-1 sm:gap-2 shrink-0">
                    <input type="text" id="geraiSearch" placeholder="Cari kode/nama gerai..."
                        class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                        autocomplete="off" oninput="onSearchInput(this.value)">
                    <button type="button" onclick="toggleSearch('geraiSearch', this)" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <ul id="geraiSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
                </div>
            </div>
        </div>

        <div class="p-5 overflow-hidden">
            @if ($geraiId)
                @if (!empty($reportData))
                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                                    <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Tanggal</th>
                                    @foreach ($reportData as $rd)
                                        <th class="px-3 sm:px-5 py-3 text-center whitespace-nowrap">{{ $rd['tanggal'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 sm:px-5 py-3 font-medium text-gray-600">Nilai</td>
                                    @foreach ($reportData as $rd)
                                        <td class="px-3 sm:px-5 py-3 text-center font-semibold text-blue-600">{{ $rd['skor'] }}</td>
                                    @endforeach
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 sm:px-5 py-3 font-medium text-gray-600">Grade</td>
                                    @foreach ($reportData as $rd)
                                        @php $grd = \App\Models\MonitoringReport::gradeFromScore((float) $rd['skor']); @endphp
                                        <td class="px-3 sm:px-5 py-3 text-center font-semibold {{ $grd === 'A' ? 'text-green-600' : ($grd === 'B' ? 'text-blue-600' : ($grd === 'C' ? 'text-yellow-600' : ($grd === 'D' ? 'text-orange-500' : 'text-red-600'))) }}">{{ $grd }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <div>
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Trend Skor {{ $geraiNama }}</h2>
                    @if (empty($chartLabels))
                        <p class="text-sm text-gray-500 text-center">Belum ada data monitoring untuk gerai ini.</p>
                    @else
                        <div class="relative" style="height: 350px;">
                            <canvas id="performaChart"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-500 text-center">Pilih gerai untuk melihat grafik performa.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
var geraiList = [
    @foreach ($gerais as $g)
        { id: {{ $g->id }}, kode: @json($g->kode_gerai), nama: @json($g->nama_gerai) },
    @endforeach
];

var input = document.getElementById('geraiSearch');
var suggest = document.getElementById('geraiSuggest');

function renderSuggest(filter) {
    var q = filter.toLowerCase();
    var items = geraiList.filter(function(g) {
        return g.kode.toLowerCase().indexOf(q) !== -1 || g.nama.toLowerCase().indexOf(q) !== -1;
    });
    suggest.innerHTML = '';
    if (items.length === 0) {
        suggest.classList.add('hidden');
        return;
    }
    items.forEach(function(g) {
        var li = document.createElement('li');
        li.className = 'px-3 py-2.5 cursor-pointer hover:bg-blue-50 border-b border-gray-100 text-sm';
        li.textContent = g.kode + ' - ' + g.nama;
        li.addEventListener('click', function() {
            input.value = g.kode + ' - ' + g.nama;
            suggest.classList.add('hidden');
            window.location.href = '/daftar-nilai/performa?gerai_id=' + g.id;
        });
        suggest.appendChild(li);
    });
    var btn = document.getElementById('geraiSearch').parentElement.querySelector('button');
    positionSuggest(btn, 'geraiSuggest');
    suggest.classList.remove('hidden');
}

var expandTimer;
function onSearchInput(val) {
    clearTimeout(expandTimer);
    expandTimer = setTimeout(function() { renderSuggest(val); }, 150);
}

input.addEventListener('blur', function() {
    setTimeout(function() {
        suggest.classList.add('hidden');
    }, 200);
});
document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) suggest.classList.add('hidden');
});
</script>
    @if ($geraiId && !empty($chartLabels))
        <script>
            const ctx = document.getElementById('performaChart').getContext('2d');
            Chart.register(ChartDataLabels);
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Skor',
                        data: @json($chartData),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#2563eb',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'bottom',
                            color: '#2563eb',
                            font: { weight: 'bold', size: 11 },
                            formatter: (value) => value,
                        }
                    },
                    scales: {
                        y: {
                            min: 700,
                            max: 1000,
                            grid: {
                                color: 'rgba(0,0,0,0.05)',
                            },
                            ticks: {
                                font: { size: 12 },
                            },
                            afterBuildTicks: function(axis) { axis.ticks = [700, 800, 900, 1000].map(v => ({ value: v })); }
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                font: { size: 12 },
                            }
                        }
                    }
                }
            });
        </script>
    @endif
@endpush
