@extends('layouts.admin')

@section('title', 'Detail Komplain - ' . $komplain->nama_gerai)

@section('content')
    <div class="mb-4">
        <a href="/komplain" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-800">{{ $komplain->kode_gerai }} - {{ $komplain->nama_gerai }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $komplain->periode ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($komplain->prioritas === 'Mendesak')
                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">Mendesak</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">{{ $komplain->prioritas ?? 'Normal' }}</span>
                @endif
                @if ($komplain->status === 'Open')
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Open</span>
                @elseif ($komplain->status === 'On Progress')
                    <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">On Progress</span>
                @elseif ($komplain->status === 'Closed')
                    <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">Closed</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">{{ $komplain->status ?? '-' }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Informasi Komplain --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Informasi Komplain</h3>
            <button type="button" id="btnEditInfo" onclick="toggleInfo(true)"
                class="text-xs font-medium px-3 py-1 rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit</button>
        </div>

        {{-- View Mode --}}
        <div id="infoView" class="px-4 sm:px-6 py-4 space-y-3">
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Tanggal Komplain</span>
                <span class="font-medium text-gray-800">{{ $komplain->tanggal_komplain ? $komplain->tanggal_komplain->format('d M Y') : '-' }}</span>
            </div>
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Media Laporan</span>
                <span class="font-medium text-gray-800">{{ $komplain->media_laporan ?? '-' }}</span>
            </div>
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Kategori Laporan</span>
                <span class="font-medium text-gray-800">{{ $komplain->kategori_laporan ?? '-' }}</span>
            </div>
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Uraian Komplain</span>
                <span class="font-medium text-gray-800 whitespace-pre-wrap">{{ $komplain->uraian ?? '-' }}</span>
            </div>
        </div>

        {{-- Edit Mode --}}
        <form id="infoEdit" method="POST" action="/komplain/{{ $komplain->id }}" class="hidden">
            @csrf @method('PUT')
            <input type="hidden" name="kode_gerai" value="{{ $komplain->kode_gerai }}">
            <input type="hidden" name="nama_gerai" value="{{ $komplain->nama_gerai }}">
            <div class="px-4 sm:px-6 py-4 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">Tanggal Komplain</label>
                    <input type="text" name="tanggal_komplain" value="{{ $komplain->tanggal_komplain ? $komplain->tanggal_komplain->format('Y-m-d') : '' }}" required placeholder="Pilih tanggal"
                        class="fp-date flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">Media Laporan</label>
                    <input type="text" name="media_laporan" value="{{ $komplain->media_laporan }}" placeholder="Email, Telepon, dll"
                        class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">Kategori Laporan</label>
                    <select name="kategori_laporan"
                        class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih Kategori</option>
                        <option value="Kualitas Air Minum" {{ $komplain->kategori_laporan === 'Kualitas Air Minum' ? 'selected' : '' }}>Kualitas Air Minum</option>
                        <option value="Kelayakan Uji Lab" {{ $komplain->kategori_laporan === 'Kelayakan Uji Lab' ? 'selected' : '' }}>Kelayakan Uji Lab</option>
                        <option value="Standar Pelayanan kpd Pelanggan" {{ $komplain->kategori_laporan === 'Standar Pelayanan kpd Pelanggan' ? 'selected' : '' }}>Standar Pelayanan kpd Pelanggan</option>
                        <option value="Standar Operasional dari Karyawan" {{ $komplain->kategori_laporan === 'Standar Operasional dari Karyawan' ? 'selected' : '' }}>Standar Operasional dari Karyawan</option>
                        <option value="Pelanggaran PSSO" {{ $komplain->kategori_laporan === 'Pelanggaran PSSO' ? 'selected' : '' }}>Pelanggaran PSSO</option>
                        <option value="Antrian dan Titipan Galon Pelanggan" {{ $komplain->kategori_laporan === 'Antrian dan Titipan Galon Pelanggan' ? 'selected' : '' }}>Antrian dan Titipan Galon Pelanggan</option>
                        <option value="Jumlah Karyawan" {{ $komplain->kategori_laporan === 'Jumlah Karyawan' ? 'selected' : '' }}>Jumlah Karyawan</option>
                        <option value="Jam Operasional Gerai (tutup lebih awal dll)" {{ $komplain->kategori_laporan === 'Jam Operasional Gerai (tutup lebih awal dll)' ? 'selected' : '' }}>Jam Operasional Gerai (tutup lebih awal dll)</option>
                        <option value="Transaksi/Metode Pembayaran" {{ $komplain->kategori_laporan === 'Transaksi/Metode Pembayaran' ? 'selected' : '' }}>Transaksi/Metode Pembayaran</option>
                        <option value="Standar Uang Kembalian" {{ $komplain->kategori_laporan === 'Standar Uang Kembalian' ? 'selected' : '' }}>Standar Uang Kembalian</option>
                        <option value="Kondisi Halaman Parkir di Gerai" {{ $komplain->kategori_laporan === 'Kondisi Halaman Parkir di Gerai' ? 'selected' : '' }}>Kondisi Halaman Parkir di Gerai</option>
                        <option value="Kondisi Akses Jalan ke Gerai" {{ $komplain->kategori_laporan === 'Kondisi Akses Jalan ke Gerai' ? 'selected' : '' }}>Kondisi Akses Jalan ke Gerai</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400 sm:mt-1.5">Uraian Komplain</label>
                    <textarea name="uraian" rows="1" required placeholder="Isi uraian komplain..."
                        class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden"
                        oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'">{{ $komplain->uraian }}</textarea>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">Prioritas</label>
                    <select name="prioritas"
                        class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Normal" {{ $komplain->prioritas === 'Normal' ? 'selected' : '' }}>Normal</option>
                        <option value="Mendesak" {{ $komplain->prioritas === 'Mendesak' ? 'selected' : '' }}>Mendesak</option>
                    </select>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-3 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="toggleInfo(false)" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>

    {{-- Penanganan --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Penanganan</h3>
            <button type="button" id="btnEditPenanganan" onclick="togglePenanganan(true)"
                class="text-xs font-medium px-3 py-1 rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit</button>
        </div>

        {{-- View Mode --}}
        <div id="penangananView" class="px-4 sm:px-6 py-4 space-y-3">
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">PIC Penanganan</span>
                <span class="font-medium text-gray-800">{{ $komplain->pic_penanganan ?? '-' }}</span>
            </div>
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Tindak Lanjut</span>
                <span class="font-medium text-gray-800 whitespace-pre-wrap">{{ $komplain->tindak_lanjut ?? '-' }}</span>
            </div>
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Tanggal Follow Up</span>
                <span class="font-medium text-gray-800">{{ $komplain->tanggal_follow_up ? $komplain->tanggal_follow_up->format('d M Y') : '-' }}</span>
            </div>
            <div class="flex text-sm">
                <span class="w-40 shrink-0 text-gray-400">Tanggal Close</span>
                <span class="font-medium text-gray-800">{{ $komplain->tanggal_close ? $komplain->tanggal_close->format('d M Y') : '-' }}</span>
            </div>
        </div>

        {{-- Edit Mode --}}
        <form id="penangananEdit" method="POST" action="/komplain/{{ $komplain->id }}/penanganan" class="hidden">
            @csrf @method('PUT')
            <div class="px-4 sm:px-6 py-4 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">PIC Penanganan</label>
                    <div class="relative flex-1">
                        <input type="text" name="pic_penanganan" id="pic_penanganan" autocomplete="off" value="{{ $komplain->pic_penanganan }}" placeholder="Ketik nama franchisee atau PG..."
                            class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <ul id="picSuggest" class="absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto hidden"></ul>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400 sm:mt-1.5">Tindak Lanjut</label>
                    <textarea name="tindak_lanjut" rows="2" placeholder="Isi tindak lanjut..."
                        class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden"
                        oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'">{{ $komplain->tindak_lanjut }}</textarea>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">Tanggal Follow Up</label>
                    <input type="text" name="tanggal_follow_up" value="{{ $komplain->tanggal_follow_up ? $komplain->tanggal_follow_up->format('Y-m-d') : '' }}" placeholder="Pilih tanggal"
                        class="fp-date flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm">
                    <label class="w-40 shrink-0 text-gray-400">Tanggal Close</label>
                    <input type="text" name="tanggal_close" value="{{ $komplain->tanggal_close ? $komplain->tanggal_close->format('Y-m-d') : '' }}" placeholder="Pilih tanggal"
                        class="fp-date flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="px-4 sm:px-6 py-3 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="togglePenanganan(false)" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium cursor-pointer">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium cursor-pointer" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>

    @php
        $placeholders = [
            'kode_gerai' => $komplain->kode_gerai ?? '-',
            'nama_gerai' => $komplain->nama_gerai ?? '-',
            'kategori_laporan' => $komplain->kategori_laporan ?? '-',
            'tanggal_komplain' => $komplain->tanggal_komplain ? $komplain->tanggal_komplain->format('d M Y') : '-',
            'media_laporan' => $komplain->media_laporan ?? '-',
            'uraian' => $komplain->uraian ?? '-',
            'pic_penanganan' => $komplain->pic_penanganan ?? '-',
            'tanggal_follow_up' => $komplain->tanggal_follow_up ? $komplain->tanggal_follow_up->format('d M Y') : '-',
            'tanggal_close' => $komplain->tanggal_close ? $komplain->tanggal_close->format('d M Y') : '-',
            'tindak_lanjut' => $komplain->tindak_lanjut ?? '-',
            'prioritas' => $komplain->prioritas ?? '-',
            'status' => $komplain->status ?? '-',
        ];
        $guide = [
            'kode_gerai' => 'Kode gerai',
            'nama_gerai' => 'Nama gerai',
            'kategori_laporan' => 'Kategori laporan',
            'tanggal_komplain' => 'Tanggal komplain',
            'media_laporan' => 'Media laporan',
            'uraian' => 'Uraian komplain',
            'pic_penanganan' => 'PIC penanganan',
            'tanggal_follow_up' => 'Tanggal follow up',
            'tanggal_close' => 'Tanggal close',
            'tindak_lanjut' => 'Tindak lanjut',
            'prioritas' => 'Prioritas',
            'status' => 'Status komplain',
        ];
    @endphp
    {{-- WhatsApp Modal --}}
    <div id="waModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('waModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-base font-semibold text-gray-800">Kirim via WhatsApp</h3>
                <button id="btnEditTemplate" onclick="toggleTemplateEdit()" class="text-xs font-medium px-2.5 py-1 rounded-lg hover:opacity-80 cursor-pointer" style="background:#FEF3C7;color:#D97706">Edit Template</button>
            </div>
            <p class="text-xs text-gray-500 mb-4">Edit pesan jika diperlukan, lalu kirim.</p>
            <div class="mb-3">
                <label class="block text-xs text-gray-500 mb-1">Nomor Telepon</label>
                <input type="text" id="waNumberInput" value="0816526884" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            {{-- Preview Pesan --}}
            <div id="waPreviewSection">
                <label class="block text-xs text-gray-500 mb-1">Pesan (Preview)</label>
                <div id="waPreview" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 whitespace-pre-wrap min-h-[120px] max-h-[300px] overflow-y-auto"></div>
            </div>
            {{-- Edit Template --}}
            <div id="waTemplateSection" class="hidden">
                <label class="block text-xs text-gray-500 mb-1">Template Pesan</label>
                <textarea id="waTemplateInput" rows="10" oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none overflow-hidden whitespace-pre-wrap"></textarea>
                <div class="mt-2 p-2.5 bg-blue-50 border border-blue-200 rounded-xl">
                    <p class="text-xs font-medium text-blue-700 mb-1.5">Gunakan placeholder untuk mengisi otomatis:</p>
                    <div class="flex flex-wrap gap-1.5" id="placeholderGuide"></div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button onclick="saveTemplate()" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 cursor-pointer">Simpan Template</button>
                </div>
            </div>
            <div id="waFooter" class="mt-4 flex justify-end gap-3">
                <button onclick="document.getElementById('waModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Batal</button>
                <button onclick="sendWa()" class="px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600">Kirim</button>
            </div>
        </div>
    </div>

    <a href="#" id="waBtn" class="fixed bottom-6 right-6 z-40 w-14 h-14 bg-green-500 hover:bg-green-600 text-white rounded-full shadow-lg flex items-center justify-center" title="Kirim WhatsApp">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
    <script>
    var waPlaceholders = {!! json_encode($placeholders, JSON_HEX_TAG) !!};
    var waGuide = {!! json_encode($guide, JSON_HEX_TAG) !!};
    var waTemplate = {!! json_encode($komplain->wa_template, JSON_HEX_TAG) !!};

    function renderPlaceholderGuide() {
        var el = document.getElementById('placeholderGuide');
        el.innerHTML = '';
        Object.keys(waGuide).forEach(function(key) {
            var tag = document.createElement('button');
            tag.type = 'button';
            tag.className = 'inline-flex items-center gap-1 px-2 py-0.5 bg-white border border-blue-300 rounded-lg text-xs text-blue-700 hover:bg-blue-100 cursor-pointer';
            tag.innerHTML = '<code class="font-semibold">[' + key + ']</code><span class="text-blue-400 text-[10px]">' + waGuide[key] + '</span>';
            tag.addEventListener('click', function() {
                var ta = document.getElementById('waTemplateInput');
                var pos = ta.selectionStart;
                var before = ta.value.substring(0, pos);
                var after = ta.value.substring(ta.selectionEnd);
                ta.value = before + '[' + key + ']' + after;
                ta.focus();
                ta.selectionStart = ta.selectionEnd = pos + key.length + 2;
                updatePreview();
            });
            el.appendChild(tag);
        });
    }

    function renderPreview() {
        var msg = waTemplate;
        Object.keys(waPlaceholders).forEach(function(key) {
            msg = msg.split('[' + key + ']').join(waPlaceholders[key]);
        });
        var preview = document.getElementById('waPreview');
        preview.textContent = msg;
    }

    function updatePreview() {
        var msg = document.getElementById('waTemplateInput').value;
        Object.keys(waPlaceholders).forEach(function(key) {
            msg = msg.split('[' + key + ']').join(waPlaceholders[key]);
        });
        var preview = document.getElementById('waPreview');
        preview.textContent = msg;
    }

    function toggleTemplateEdit() {
        var editing = !document.getElementById('waTemplateSection').classList.contains('hidden');
        document.getElementById('waTemplateSection').classList.toggle('hidden', editing);
        document.getElementById('waPreviewSection').classList.toggle('hidden', !editing);
        document.getElementById('waFooter').classList.toggle('hidden', !editing);
        document.getElementById('btnEditTemplate').textContent = editing ? 'Edit Template' : 'Lihat Preview';
        document.getElementById('btnEditTemplate').style.background = editing ? '#FEF3C7' : '#DCFCE7';
        document.getElementById('btnEditTemplate').style.color = editing ? '#D97706' : '#16A34A';
        if (!editing) {
            var ta = document.getElementById('waTemplateInput');
            ta.style.height = 'auto';
            ta.style.height = ta.scrollHeight + 'px';
        }
    }

    document.getElementById('waBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('waModal').classList.remove('hidden');
        document.getElementById('waTemplateInput').value = waTemplate;
        renderPlaceholderGuide();
        renderPreview();
        var previewH = document.getElementById('waPreview');
        previewH.style.maxHeight = '300px';
    });

    document.getElementById('waTemplateInput').addEventListener('input', updatePreview);

    function saveTemplate() {
        var newTemplate = document.getElementById('waTemplateInput').value;
        waTemplate = newTemplate;
        fetch('/komplain/{{ $komplain->id }}/template', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ wa_template: newTemplate })
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                toggleTemplateEdit();
                renderPreview();
                showAlert('Template berhasil disimpan.');
            }
        });
    }

    function sendWa() {
        saveTemplate();
        var num = document.getElementById('waNumberInput').value.replace(/[^0-9]/g, '');
        if (num.length < 8) return;
        var msg = document.getElementById('waPreview').textContent;
        document.getElementById('waModal').classList.add('hidden');
        var form = document.createElement('form');
        form.method = 'GET';
        form.action = 'https://api.whatsapp.com/send';
        form.target = '_blank';
        var phone = document.createElement('input');
        phone.type = 'hidden';
        phone.name = 'phone';
        phone.value = '62' + num.replace(/^0/, '');
        var text = document.createElement('input');
        text.type = 'hidden';
        text.name = 'text';
        text.value = msg;
        form.appendChild(phone);
        form.appendChild(text);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    </script>

@push('head')
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr('.fp-date', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd M Y',
        locale: 'id',
        disableMobile: true
    });
    document.querySelectorAll('textarea[oninput]').forEach(function(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    });

    var picData = [
        @foreach($franchisees as $f) { name: @json($f), type: 'Franchisee' }, @endforeach
        @foreach($pgs as $p) { name: @json($p), type: 'PG' }, @endforeach
    ];
    var picInput = document.getElementById('pic_penanganan');
    var picList = document.getElementById('picSuggest');

    function renderPicSuggest(query) {
        picList.innerHTML = '';
        var val = query.toLowerCase();
        var items = picData.filter(function(item) {
            return item.name.toLowerCase().indexOf(val) !== -1;
        });
        if (items.length === 0) { picList.classList.add('hidden'); return; }
        items.forEach(function(item) {
            var li = document.createElement('li');
            li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm flex items-center justify-between';
            li.innerHTML = '<span class="font-medium text-gray-800">' + item.name + '</span><span class="text-xs text-gray-400">' + item.type + '</span>';
            li.addEventListener('mousedown', function(e) {
                e.preventDefault();
                picInput.value = item.name;
                picList.classList.add('hidden');
            });
            picList.appendChild(li);
        });
        picList.classList.remove('hidden');
    }

    picInput.addEventListener('focus', function() { renderPicSuggest(this.value); });
    picInput.addEventListener('input', function() { renderPicSuggest(this.value); });
    picInput.addEventListener('blur', function() {
        setTimeout(function() { picList.classList.add('hidden'); }, 200);
    });
});

function togglePenanganan(edit) {
    document.getElementById('penangananView').classList.toggle('hidden', edit);
    document.getElementById('penangananEdit').classList.toggle('hidden', !edit);
    document.getElementById('btnEditPenanganan').classList.toggle('hidden', edit);
    if (edit) {
        var ta = document.querySelector('#penangananEdit textarea[oninput]');
        if (ta) { ta.style.height = 'auto'; ta.style.height = ta.scrollHeight + 'px'; }
    }
}

function toggleInfo(edit) {
    document.getElementById('infoView').classList.toggle('hidden', edit);
    document.getElementById('infoEdit').classList.toggle('hidden', !edit);
    document.getElementById('btnEditInfo').classList.toggle('hidden', edit);
    if (edit) {
        var ta = document.querySelector('#infoEdit textarea[oninput]');
        if (ta) { ta.style.height = 'auto'; ta.style.height = ta.scrollHeight + 'px'; }
    }
}

</script>
@endpush
@endsection
