@extends('layouts.admin')

@section('title', 'Daftar Gerai Pendampingan - MARS')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Daftar Gerai Pendampingan</h2>
                @php
                    $totalAll = $reports->count();
                    $totalSelesai = $reports->filter(fn($r) => $r->wa_sent_at)->count();
                    $totalBelum = $totalAll - $totalSelesai;
                @endphp
                <div id="filterBtns" class="flex items-center gap-1">
                    <button onclick="filterPendampingan('all')" data-filter="all" class="filter-btn px-3 py-1.5 rounded-full text-xs font-medium focus:outline-none bg-blue-100 text-blue-700">All ({{ $totalAll }})</button>
                    <button onclick="filterPendampingan('selesai')" data-filter="selesai" class="filter-btn px-3 py-1.5 rounded-full text-xs font-medium focus:outline-none bg-gray-100 text-gray-500">Selesai ({{ $totalSelesai }})</button>
                    <button onclick="filterPendampingan('belum')" data-filter="belum" class="filter-btn px-3 py-1.5 rounded-full text-xs font-medium focus:outline-none bg-gray-100 text-gray-500">Belum ({{ $totalBelum }})</button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs sm:text-sm text-gray-500">Periode: {{ $period->label }}</span>
                <button onclick="openCustomMessageModal()"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            </div>
        </div>

        @if ($reports->isEmpty())
            <div class="p-6 text-center text-sm text-gray-400">Belum ada data monitoring untuk periode {{ $period->label }}.</div>
        @else
            <div class="overflow-x-auto">
                <table id="pendampinganTable" class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Kode Gerai</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Nama Gerai</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap hidden sm:table-cell">Franchisee</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Petugas</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-center">Grade</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-right">Nilai</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-center">Status</th>
                            <th class="px-3 sm:px-5 py-3 whitespace-nowrap text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($reports as $r)
                            @php
                                $grade = \App\Models\MonitoringReport::gradeFromScore((float) $r->nilai);
                                $hasF = $r->major || $r->minor;
                                $phone = $r->gerai->no_telepon;
                                $waNumber = '';
                                if ($phone) {
                                    $digits = preg_replace('/\D/', '', $phone);
                                    if (strlen($digits) >= 10) {
                                        if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);
                                        elseif (!str_starts_with($digits, '62')) $digits = '62' . $digits;
                                        $waNumber = $digits;
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 sm:px-5 py-3 text-gray-500 whitespace-nowrap">{{ $r->gerai->kode_gerai }}</td>
                                <td class="px-3 sm:px-5 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $r->gerai->nama_gerai }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-500 whitespace-nowrap hidden sm:table-cell">{{ $r->gerai->franchisee }}</td>
                                <td class="px-3 sm:px-5 py-3 text-gray-700 whitespace-nowrap">{{ $r->user?->name ?? '-' }}</td>
                                <td class="px-3 sm:px-5 py-3 text-center font-semibold whitespace-nowrap {{ $grade === 'A' ? 'text-green-600' : ($grade === 'B' ? 'text-blue-600' : ($grade === 'C' ? 'text-yellow-600' : ($grade === 'D' ? 'text-orange-500' : 'text-red-600'))) }}">{{ $grade }}</td>
                                <td class="px-3 sm:px-5 py-3 text-right font-semibold text-blue-600 whitespace-nowrap">{{ $r->nilai ? round((float) $r->nilai) : '-' }}</td>
                                <td class="px-3 sm:px-5 py-3 text-center whitespace-nowrap">
                                    <span data-report-id="{{ $r->id }}"
                                        class="wa-status cursor-pointer inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $r->wa_sent_at ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $r->wa_sent_at ? 'Selesai' : 'Belum' }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-5 py-3 text-center whitespace-nowrap">
                                    <button type="button"
                                        data-gerai="{{ $r->gerai->kode_gerai }} - {{ $r->gerai->nama_gerai }}"
                                        data-kode="{{ $r->gerai->kode_gerai }}"
                                        data-nama="{{ $r->gerai->nama_gerai }}"
                                        data-franchisee="{{ $r->gerai->franchisee }}"
                                        data-grade="{{ $grade }}"
                                        data-nilai="{{ $r->nilai ? round((float) $r->nilai) : '-' }}"
                                        data-finding="{{ base64_encode(json_encode($hasF ? $r->only(['major', 'minor', 'pengawas', 'rata_rata_aj', 'tds', 'mesin_ozon', 'peringatan_awal', 'note', 'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca', 'penjelasan_isi', 'penjelasan_isi_3', 'ttd_petugas', 'ttd_pimpinan']) : [])) }}"
                                        data-phone="{{ $waNumber }}"
                                        data-report-id="{{ $r->id }}"
                                        class="btn-wa inline-flex items-center justify-center w-8 h-8 rounded-full hover:opacity-80 wa-btn"
                                        style="background:#25D366;color:white">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Custom Message Modal --}}
    <div id="customMessageModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black/50" onclick="closeCustomMessageModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Pesan WhatsApp</h3>
            <textarea id="customMessageInput" rows="6" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Tulis pesan yang akan dikirim ke gerai...">{{ $period->label }}</textarea>
            <p class="text-xs text-gray-400 mt-2">Klik placeholder untuk menyisipkan:</p>
            <div class="flex flex-wrap gap-1.5 mt-1">
                <button onclick="insertPlaceholder('{kode_gerai}')" type="button" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">{kode_gerai}</button>
                <button onclick="insertPlaceholder('{nama_gerai}')" type="button" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">{nama_gerai}</button>
                <button onclick="insertPlaceholder('{franchisee}')" type="button" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">{franchisee}</button>
                <button onclick="insertPlaceholder('{grade}')" type="button" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">{grade}</button>
                <button onclick="insertPlaceholder('{nilai}')" type="button" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">{nilai}</button>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <button onclick="closeCustomMessageModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Tutup</button>
                <button onclick="saveCustomMessage()" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600">Simpan</button>
            </div>
        </div>
    </div>

    {{-- Temuan Modal --}}
    <div id="temuanModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black/50" onclick="closeTemuanModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 max-h-[90vh] flex flex-col">
            <h3 class="text-base font-semibold text-gray-800 mb-1">Temuan Monitoring</h3>
            <p id="temuanGeraiLabel" class="text-xs text-gray-500 mb-3"></p>
            <div id="temuanContent" class="flex-1 overflow-y-auto text-sm text-gray-800 space-y-3"></div>
            <div class="mt-4 pt-4 border-t border-gray-200 space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nomor Telepon</label>
                    <input type="text" id="waNumber" placeholder="628xxxxxxxx"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <p id="waError" class="text-xs text-red-500 hidden">Nomor tidak valid. Gunakan format: 628xx...</p>
                <div class="flex justify-end gap-3">
                    <button onclick="closeTemuanModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Tutup</button>
                    <button onclick="sendWa()" class="px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600">Kirim WhatsApp</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Modal --}}
    <div id="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black/50" onclick="closeConfirmModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full mx-4 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-2" id="confirmTitle">Konfirmasi</h3>
            <p class="text-sm text-gray-600 mb-4" id="confirmMessage"></p>
            <div class="flex justify-end gap-3">
                <button onclick="closeConfirmModal()" id="confirmCancelBtn" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Batal</button>
                <button id="confirmOkBtn" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600">Ya</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function filterPendampingan(filter) {
        document.querySelectorAll('.filter-btn').forEach(function(btn) {
            var active = btn.dataset.filter === filter;
            btn.className = 'filter-btn px-3 py-1.5 rounded-full text-xs font-medium focus:outline-none ' +
                (active ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500');
        });
        document.querySelectorAll('#pendampinganTable tbody tr').forEach(function(row) {
            if (filter === 'all') { row.style.display = ''; return; }
            var status = row.querySelector('.wa-status');
            if (!status) { row.style.display = 'none'; return; }
            var isSelesai = status.textContent.trim() === 'Selesai';
            row.style.display = (filter === 'selesai' && isSelesai) || (filter === 'belum' && !isSelesai) ? '' : 'none';
        });
    }

    var customWaMessage = '';
    var currentReportId = null;
    var confirmCallback = null;
    var currentGeraiData = {};

    function insertPlaceholder(tag) {
        var ta = document.getElementById('customMessageInput');
        var pos = ta.selectionStart;
        var text = ta.value;
        ta.value = text.slice(0, pos) + tag + text.slice(pos);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = pos + tag.length;
    }

    // WA button click
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.wa-btn');
        if (btn) {
            var gerai = btn.dataset.gerai;
            var phone = btn.dataset.phone;
            var reportId = btn.dataset.reportId;
            var finding = {};
            try {
                finding = JSON.parse(atob(btn.dataset.finding));
            } catch(_) {}
            currentGeraiData = {
                kode_gerai: btn.dataset.kode || '',
                nama_gerai: btn.dataset.nama || '',
                franchisee: btn.dataset.franchisee || '',
                grade: btn.dataset.grade || '',
                nilai: btn.dataset.nilai || ''
            };
            openTemuanModal(gerai, finding, phone, reportId);
        }
    });

    // WA status toggle click
    document.addEventListener('click', function(e) {
        var el = e.target.closest('.wa-status');
        if (!el) return;
        var reportId = el.dataset.reportId;
        var isSent = el.textContent.trim() === 'Selesai';
        var msg = isSent ? 'Ubah status WA menjadi Belum?' : 'Tandai WA sudah dikirim?';
        showConfirm(msg, function() {
            toggleWaSent(reportId, el);
        });
    });

    function showConfirm(message, callback) {
        document.getElementById('confirmMessage').textContent = message;
        confirmCallback = callback;
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        confirmCallback = null;
    }

    document.getElementById('confirmOkBtn').addEventListener('click', function() {
        if (confirmCallback) confirmCallback();
        closeConfirmModal();
    });

    document.getElementById('confirmCancelBtn').addEventListener('click', closeConfirmModal);

    function openTemuanModal(gerai, f, number, reportId) {
        currentReportId = reportId;
        document.getElementById('temuanGeraiLabel').textContent = gerai;
        document.getElementById('waNumber').value = number || '';
        document.getElementById('waError').classList.add('hidden');

        var html = '';
        if (!f || !f.major && !f.minor && !f.pengawas && !f.rata_rata_aj && !f.mesin_ozon && !f.peringatan_awal && !f.note && !f.kondisi_cat && !f.kondisi_awning && !f.kondisi_vinyl && !f.kondisi_stiker_kaca && (!f.penjelasan_isi || !f.penjelasan_isi.some(function(t) { return t && t.trim(); })) && (!f.penjelasan_isi_3 || !Object.values(f.penjelasan_isi_3).some(function(t) { return t && t.trim(); }))) {
            html = '<p class="text-gray-400 italic">Belum ada temuan monitoring.</p>';
        } else {
            if (f.major) html += '<div><p class="text-xs font-medium text-gray-500">Major</p><p class="whitespace-pre-wrap" style="overflow-wrap:break-word;word-break:break-word;">' + esc(f.major) + '</p></div>';
            if (f.minor) html += '<div><p class="text-xs font-medium text-gray-500">Minor</p><p class="whitespace-pre-wrap" style="overflow-wrap:break-word;word-break:break-word;">' + esc(f.minor) + '</p></div>';
            var hasPeringatan = f.pengawas || f.rata_rata_aj || f.mesin_ozon || f.peringatan_awal || f.note || f.kondisi_cat || f.kondisi_awning || f.kondisi_vinyl || f.kondisi_stiker_kaca;
            if (hasPeringatan) {
                var peringatanHtml = '';
                if (f.pengawas) peringatanHtml += '<div class="whitespace-pre-wrap" style="overflow-wrap:break-word;word-break:break-word;">' + esc(f.pengawas) + '</div>';
                if (f.rata_rata_aj) peringatanHtml += '<p class="my-0">Rerata AJ \u00B1 ' + esc(f.rata_rata_aj) + ' gln/hr</p>';
                if (f.tds) {
                    var tdsDisplay = f.tds.replace('/', ' ppm/');
                    if (f.tds.indexOf('/') !== -1) tdsDisplay += '\u00B0C';
                    peringatanHtml += '<p class="my-0">TDS: ' + tdsDisplay + '</p>';
                }
                if (f.mesin_ozon) peringatanHtml += '<p class="my-0">MO: ' + esc(f.mesin_ozon) + '</p>';
                if (f.peringatan_awal) peringatanHtml += '<div class="mt-4 mb-0 whitespace-pre-wrap" style="overflow-wrap:break-word;word-break:break-word;">' + esc(f.peringatan_awal) + '</div>';
                if (f.note) peringatanHtml += '<p class="mt-4 mb-0">Note:</p><div class="my-0 whitespace-pre-wrap" style="overflow-wrap:break-word;word-break:break-word;">' + esc(f.note) + '</div>';
                if (f.kondisi_cat || f.kondisi_awning || f.kondisi_vinyl || f.kondisi_stiker_kaca) {
                    peringatanHtml += '<p class="mt-4 mb-0">Checklist tampilan gerai:</p>';
                    peringatanHtml += '<p class="my-0">Kondisi cat: ' + (f.kondisi_cat || 'Baik') + '</p>';
                    peringatanHtml += '<p class="my-0">Kondisi awning: ' + (f.kondisi_awning || 'Baik') + '</p>';
                    peringatanHtml += '<p class="my-0">Kondisi vinyl reklame dinding/jalan: ' + (f.kondisi_vinyl || 'Baik') + '</p>';
                    peringatanHtml += '<p class="my-0">Kondisi stiker kaca: ' + (f.kondisi_stiker_kaca || 'Baik') + '</p>';
                }
                html += '<div><p class="text-xs font-medium text-gray-500 mb-1">Peringatan Awal:</p><div class="text-sm text-gray-800">' + peringatanHtml + '</div></div>';
            }
            var penjelasan = f.penjelasan_isi || [];
            var hasPenjelasan = penjelasan.some(function(t) { return t && t.trim(); });
            if (hasPenjelasan) {
                var penjelasanHtml = '';
                penjelasan.forEach(function(t, i) {
                    if (t.trim()) penjelasanHtml += '<p class="text-sm text-gray-800">' + (i + 1) + '. ' + esc(t) + '</p>';
                });
                html += '<div><p class="text-xs font-medium text-gray-500 mb-1">Penjelasan Formulir 2</p>' + penjelasanHtml + '</div>';
            }
            var penjelasan3 = f.penjelasan_isi_3 || {};
            var hasPenjelasan3 = Object.values(penjelasan3).some(function(t) { return t && t.trim(); });
            if (hasPenjelasan3) {
                var penjelasan3Html = '';
                var idx = 1;
                Object.keys(penjelasan3).forEach(function(key) {
                    if (penjelasan3[key] && penjelasan3[key].trim()) {
                        penjelasan3Html += '<p class="text-sm text-gray-800">' + (idx++) + '. ' + esc(penjelasan3[key]) + '</p>';
                    }
                });
                html += '<div><p class="text-xs font-medium text-gray-500 mb-1">Penjelasan Formulir 3</p>' + penjelasan3Html + '</div>';
            }
        }
        document.getElementById('temuanContent').innerHTML = html;
        document.getElementById('temuanModal').classList.remove('hidden');
    }

    function closeTemuanModal() {
        document.getElementById('temuanModal').classList.add('hidden');
    }

    function sendWa() {
        var number = document.getElementById('waNumber').value.trim().replace(/[^0-9]/g, '');
        if (number.length < 8) {
            document.getElementById('waError').classList.remove('hidden');
            return;
        }
        document.getElementById('waError').classList.add('hidden');
        closeTemuanModal();
        var text = customWaMessage || document.getElementById('temuanGeraiLabel').textContent;
        text = text.replace(/\{kode_gerai\}/g, currentGeraiData.kode_gerai)
                   .replace(/\{nama_gerai\}/g, currentGeraiData.nama_gerai)
                   .replace(/\{franchisee\}/g, currentGeraiData.franchisee)
                   .replace(/\{grade\}/g, currentGeraiData.grade)
                   .replace(/\{nilai\}/g, currentGeraiData.nilai);
        window.open('https://wa.me/' + number + '?text=' + encodeURIComponent(text), '_blank');
        if (currentReportId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/gerai-pendampingan/' + currentReportId + '/mark-sent');
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var label = document.querySelector('span[data-report-id="' + currentReportId + '"]');
                    if (label) {
                        label.textContent = 'Selesai';
                        label.className = 'cursor-pointer inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
                    }
                }
            };
            xhr.send();
        }
    }

    function openCustomMessageModal() {
        if (customWaMessage) {
            document.getElementById('customMessageInput').value = customWaMessage;
        }
        document.getElementById('customMessageModal').classList.remove('hidden');
    }

    function closeCustomMessageModal() {
        document.getElementById('customMessageModal').classList.add('hidden');
    }

    function saveCustomMessage() {
        customWaMessage = document.getElementById('customMessageInput').value.trim();
        closeCustomMessageModal();
    }

    function esc(s) {
        if (!s) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(s));
        return div.innerHTML;
    }

    function toggleWaSent(reportId, el) {
        var isSent = el.textContent.trim() === 'Selesai';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/gerai-pendampingan/' + reportId + '/mark-sent');
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var res = JSON.parse(xhr.responseText);
                el.textContent = res.sent ? 'Selesai' : 'Belum';
                el.className = 'wa-status cursor-pointer inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' +
                    (res.sent ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500');
            }
        };
        xhr.send();
    }
    </script>
    @endpush
@endsection
