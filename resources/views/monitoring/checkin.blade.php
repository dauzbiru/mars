@extends('layouts.admin')

@section('title', 'Checkin - ' . $gerai->nama_gerai)

@push('head')
<link rel="stylesheet" href="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps.css" />
@endpush

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 mt-1">Checkin Gerai</h2>
    </div>

    <div class="px-4 sm:px-6 py-4">
        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-700"><strong>Gerai:</strong> {{ $gerai->kode_gerai }} - {{ $gerai->nama_gerai }}</p>
            @if ($gerai->franchisee)<p class="text-sm text-gray-700 mt-1"><strong>Franchisee:</strong> {{ $gerai->franchisee }}</p>@endif
        </div>

        <form method="POST" action="/{{ $prefix }}/checkin/{{ $gerai->id }}">
            @csrf
            <input type="hidden" name="pairing" id="pairingField" value="{{ request('pairing') }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Checkin</label>
                <div id="map" class="w-full h-48 rounded-lg border border-gray-300 mb-2" style="z-index:10;position:relative;"></div>
                <div class="flex gap-2">
                    <input type="text" name="location" id="location" required readonly
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 bg-gray-50"
                        placeholder="Mendeteksi lokasi...">
                    <button type="button" id="refreshLocation" style="background:#3B82F6;color:#FFFFFF" class="px-3 py-2 text-sm font-medium rounded-lg hover:opacity-80 whitespace-nowrap">
                        Refresh
                    </button>
                </div>
                <p id="locationStatus" class="text-xs text-gray-400 mt-1"></p>
                @error('location')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="checkin_at" value="{{ now()->format('Y-m-d') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jam</label>
                    <input type="text" value="{{ now()->format('H:i:s') }}" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600">
                </div>
            </div>

            @if ($periods->isNotEmpty())
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode Semester</label>
                    <select name="periode_label" id="periode_label"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">Pilih Periode</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->label }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $p->label }}
                        </option>
                    @endforeach
                </select>
                @error('periode_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <button type="submit"
                style="background:#DCFCE7;color:#16A34A"
                class="w-full px-4 py-2.5 text-sm font-medium rounded-lg hover:opacity-80 active:bg-green-300 active:scale-[0.98] transition-all">
                Checkin
            </button>
        </form>
    </div>
</div>

<div id="existingPopup" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none" onclick="closePopup()">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-800">Data Sudah Ada</h3>
                <p class="text-sm text-gray-500">{{ $gerai->kode_gerai }} - {{ $gerai->nama_gerai }}</p>
            </div>
        </div>
        <p class="text-sm text-gray-700 mb-4" id="popupMessage"></p>
        <button onclick="closePopup()" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            Kembali
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps-web.min.js"></script>
<script src="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/services/services-web.min.js"></script>
<script>
(function() {
    var input = document.getElementById('location');
    var status = document.getElementById('locationStatus');
    var timedOut = false;

    var map = tt.map({ key: 'NGS8boaT3CH45v33wXWzwWO5FUSeI2Zl', container: 'map', center: [118, -2.5], zoom: 5 });
    tt.setProductInfo('MARS', '1.0');
    var marker = null;
    setTimeout(function() { map.resize(); }, 200);

    function setMarker(lat, lng, geocode) {
        if (marker) marker.remove();
        marker = new tt.Marker({ draggable: true }).setLngLat([lng, lat]).addTo(map);
        map.flyTo({ center: [lng, lat], zoom: 15 });
        lastPos = lng.toFixed(6) + ',' + lat.toFixed(6);
        if (geocode !== false) geoCode(lat, lng);
    }

    var lastPos = '';

    setInterval(function () {
        if (!marker) return;
        var p = marker.getLngLat();
        var key = p.lng.toFixed(6) + ',' + p.lat.toFixed(6);
        if (key !== lastPos) {
            lastPos = key;
            input.value = p.lat.toFixed(6) + ', ' + p.lng.toFixed(6);
            status.textContent = 'Mendapatkan alamat...';
            geoCode(p.lat, p.lng);
        }
    }, 800);

    map.on('click', function (e) {
        setMarker(e.lngLat.lat, e.lngLat.lng);
    });

    function geoCode(lat, lng) {
        status.textContent = 'Mengonversi ke alamat...';
        tt.services.reverseGeocode({
            key: 'NGS8boaT3CH45v33wXWzwWO5FUSeI2Zl',
            position: { lat: lat, lng: lng }
        })
        .then(function(res) {
            var a = res.addresses[0].address;
            input.value = a.freeformAddress + ', ' + a.country || (lat.toFixed(6) + ', ' + lng.toFixed(6));
            status.textContent = 'Lokasi terdeteksi otomatis.';
            input.readOnly = false;
            input.classList.remove('bg-gray-50');
        })
        .catch(function() {
            input.value = lat.toFixed(6) + ', ' + lng.toFixed(6);
            status.textContent = 'Lokasi (koordinat) terdeteksi.';
            input.readOnly = false;
            input.classList.remove('bg-gray-50');
        });
    }

    if (!navigator.geolocation) {
        status.textContent = 'Geolocation tidak didukung. Isi manual.';
        input.readOnly = false;
        input.classList.remove('bg-gray-50');
        input.placeholder = 'Masukkan lokasi manual';
        return;
    }

    function detectLocation() {
        timedOut = false;
        input.readOnly = true;
        input.classList.add('bg-gray-50');
        input.placeholder = 'Mendeteksi lokasi...';
        status.textContent = 'Mendapatkan lokasi...';

        setTimeout(function() {
            if (input.readOnly) {
                timedOut = true;
                input.readOnly = false;
                input.classList.remove('bg-gray-50');
                input.placeholder = 'Masukkan lokasi manual';
                status.textContent = 'Deteksi lokasi lambat, isi manual atau tunggu...';
            }
        }, 5000);

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                if (timedOut) return;
                setMarker(pos.coords.latitude, pos.coords.longitude);
            },
            function(err) {
                var msg = 'Gagal mengambil lokasi';
                switch(err.code) {
                    case err.PERMISSION_DENIED: msg = 'Izin lokasi ditolak. Isi manual.'; break;
                    case err.POSITION_UNAVAILABLE: msg = 'Lokasi tidak tersedia.'; break;
                    case err.TIMEOUT: msg = 'Waktu habis. Coba lagi.'; break;
                }
                status.textContent = msg;
                input.readOnly = false;
                input.classList.remove('bg-gray-50');
                input.placeholder = 'Masukkan lokasi manual';
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }

    detectLocation();
    document.getElementById('refreshLocation').addEventListener('click', detectLocation);

    var mapContainer = document.getElementById('map');
    var origToggleSidebar = window.toggleSidebar;
    window.toggleSidebar = function() {
        origToggleSidebar();
        var sidebar = document.getElementById('sidebar');
        if (sidebar.classList.contains('-translate-x-full')) {
            mapContainer.style.pointerEvents = 'auto';
        } else {
            mapContainer.style.pointerEvents = 'none';
        }
    };

    var existingPeriods = @json(isset($existingPeriods) ? $existingPeriods : []);
    var pairingField = document.getElementById('pairingField');
    if (localStorage.getItem('pairingOn') === '1') {
        pairingField.value = '1';
    }
    var form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        var sel = document.getElementById('periode_label');
        if (sel && existingPeriods.indexOf(sel.value) !== -1 && pairingField.value !== '1') {
            e.preventDefault();
            document.getElementById('popupMessage').textContent = 'Laporan atau nilai untuk gerai ini sudah ada di periode ' + sel.value + '. Silahkan pilih periode lain.';
            document.getElementById('existingPopup').style.display = 'flex';
        }
    });
})();

function closePopup() {
    document.getElementById('existingPopup').style.display = 'none';
}
</script>
@endpush
