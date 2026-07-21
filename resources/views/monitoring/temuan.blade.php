@extends('layouts.admin')

@section('title', ($prefix === 'evaluasi' ? 'Temuan Evaluasi' : 'Temuan Monitoring') . ' - ' . $report->gerai->nama_gerai)

@section('content')
<div class="max-w-lg mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4">
        <a href="/{{ $prefix }}/{{ $report->id }}/assessment" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-white shadow-sm">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="min-w-0">
            <h2 class="text-base font-bold text-gray-900 truncate">{{ $prefix === 'evaluasi' ? 'Temuan Evaluasi' : 'Temuan Monitoring' }}</h2>
        </div>
    </div>

    <form method="POST" action="/{{ $prefix }}/{{ $report->id }}/temuan" enctype="multipart/form-data">
        @csrf

        {{-- Major & Minor --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-3">
            <button type="button" class="dropdown-header w-full px-4 py-3.5 flex items-center justify-between cursor-pointer active:bg-gray-50 text-left" onclick="toggleDropdown(this)">
                <h3 class="text-sm font-semibold text-gray-800">Major & Minor</h3>
                <svg class="dropdown-chevron w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="dropdown-body px-4 pb-4 space-y-3" style="display:none">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Major</label>
                    <textarea name="major" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('major', $finding?->major ?: '-') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Minor</label>
                    <textarea name="minor" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('minor', $finding?->minor ?: '-') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Pengisian Temuan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-3">
            <button type="button" class="dropdown-header w-full px-4 py-3.5 flex items-center justify-between cursor-pointer active:bg-gray-50 text-left" onclick="toggleDropdown(this)">
                <h3 class="text-sm font-semibold text-gray-800">Pengisian Temuan</h3>
                <svg class="dropdown-chevron w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="dropdown-body px-4 pb-4 space-y-3" style="display:none">
                @if ($prefix === 'pra-monitoring')
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Pengawas</label>
                    <textarea name="pengawas" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="PS&#10;PS" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('pengawas', $finding?->pengawas ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Rata-rata AJ</label>
                        <textarea name="rata_rata_aj" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="250" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('rata_rata_aj', $finding?->rata_rata_aj ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mesin Ozon</label>
                        <textarea name="mesin_ozon" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="D 123" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('mesin_ozon', $finding?->mesin_ozon ?? '') }}</textarea>
                    </div>
                </div>
                @else
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Pengawas</label>
                    <textarea name="pengawas" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="PS&#10;PS" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('pengawas', $finding?->pengawas ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Rata-rata AJ</label>
                        <textarea name="rata_rata_aj" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="250" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('rata_rata_aj', $finding?->rata_rata_aj ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">TDS</label>
                        <textarea name="tds" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="90/30" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('tds', $finding?->tds ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mesin Ozon</label>
                        <textarea name="mesin_ozon" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="D 123" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('mesin_ozon', $finding?->mesin_ozon ?? '') }}</textarea>
                    </div>
                </div>
                @endif
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <label class="text-xs font-medium text-gray-500">Peringatan Awal</label>
                        <button type="button" id="btnCekTypo" onclick="cekTypo()" class="text-[10px] font-medium text-purple-600 bg-purple-50 border border-purple-200 rounded px-1.5 py-0.5 hover:bg-purple-100 cursor-pointer transition-colors">Cek Typo</button>
                        <span id="typoSpinner" class="hidden text-[10px] text-purple-500">Loading...</span>
                    </div>
                    <textarea name="peringatan_awal" id="peringatanAwal" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('peringatan_awal', $finding?->peringatan_awal ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Note</label>
                    <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('note', $finding?->note ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Checklist Tampilan Gerai --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-3">
            <button type="button" class="dropdown-header w-full px-4 py-3.5 flex items-center justify-between cursor-pointer active:bg-gray-50 text-left" onclick="toggleDropdown(this)">
                <h3 class="text-sm font-semibold text-gray-800">Checklist Tampilan Gerai</h3>
                <svg class="dropdown-chevron w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="dropdown-body px-4 pb-4 space-y-3" style="display:none">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Kondisi Cat</label>
                    <textarea name="kondisi_cat" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('kondisi_cat', $finding?->kondisi_cat ?? 'Baik') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Kondisi Awning</label>
                    <textarea name="kondisi_awning" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('kondisi_awning', $finding?->kondisi_awning ?? 'Baik') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Kondisi Vinyl Reklame Dinding/Jalan</label>
                    <textarea name="kondisi_vinyl" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('kondisi_vinyl', $finding?->kondisi_vinyl ?? 'Baik') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Kondisi Stiker Kaca</label>
                    <textarea name="kondisi_stiker_kaca" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ old('kondisi_stiker_kaca', $finding?->kondisi_stiker_kaca ?? 'Baik') }}</textarea>
                </div>
            </div>
        </div>

        @if (!empty($groupLabels) && $prefix !== 'pra-monitoring')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-3">
            <button type="button" class="dropdown-header w-full px-4 py-3.5 flex items-center justify-between cursor-pointer active:bg-gray-50 text-left" onclick="toggleDropdown(this)">
                <h3 class="text-sm font-semibold text-gray-800">Penjelasan Formulir 2</h3>
                <svg class="dropdown-chevron w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="dropdown-body px-4 pb-4 space-y-3" style="display:none">
                @foreach ($groupLabels as $i => $label)
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $label }}</label>
                        <div class="relative">
                            <textarea name="penjelasan_isi[]" rows="1" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none overflow-hidden" placeholder="Ketik atau pilih penjelasan..." oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ isset($finding->penjelasan_isi[$i]) ? $finding->penjelasan_isi[$i] : '' }}</textarea>
                            <ul class="suggest-list hidden absolute z-10 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto list-none p-0 m-0 mt-1" style="max-height:200px"></ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @if (count($zeroScoreItems) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-3">
            <button type="button" class="dropdown-header w-full px-4 py-3.5 flex items-center justify-between cursor-pointer active:bg-gray-50 text-left" onclick="toggleDropdown(this)">
                <h3 class="text-sm font-semibold text-gray-800">{{ $prefix === 'pra-monitoring' ? 'Penjelasan' : 'Penjelasan Formulir 3' }}</h3>
                <svg class="dropdown-chevron w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="dropdown-body px-4 pb-4 space-y-3" style="display:none">
                @foreach ($zeroScoreItems as $item)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $item['name'] }}</label>
                    <div class="relative" data-formulir="3">
                        <textarea name="penjelasan_isi_3[{{ $item['id'] }}]" rows="1" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none overflow-hidden" placeholder="Ketik atau pilih penjelasan..." oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ isset($finding->penjelasan_isi_3[$item['id']]) ? $finding->penjelasan_isi_3[$item['id']] : '' }}</textarea>
                        <ul class="suggest-list hidden absolute z-10 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto list-none p-0 m-0 mt-1" style="max-height:200px"></ul>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- TTD --}}
        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 text-center">
                    <h3 class="text-xs font-semibold text-gray-700">Tanda Tangan Petugas</h3>
                </div>
                <div class="p-4 text-center">
                    <label class="inline-block cursor-pointer">
                        <input type="file" name="ttd_petugas" accept="image/jpeg,image/png" class="hidden" onchange="compressImage(this)">
                        <span class="inline-block px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100">Pilih File</span>
                    </label>
                    <div class="mt-2 flex justify-center preview-container" style="{{ $finding && $finding->ttd_petugas ? '' : 'display:none' }}">
                        <img src="{{ $finding && $finding->ttd_petugas ? asset('storage/' . $finding->ttd_petugas) : '' }}" class="h-16 rounded-lg border preview-img">
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 text-center">
                    <h3 class="text-xs font-semibold text-gray-700">Tanda Tangan Pimpinan</h3>
                </div>
                <div class="p-4 text-center">
                    <label class="inline-block cursor-pointer">
                        <input type="file" name="ttd_pimpinan" accept="image/jpeg,image/png" class="hidden" onchange="compressImage(this)">
                        <span class="inline-block px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100">Pilih File</span>
                    </label>
                    <div class="mt-2 flex justify-center preview-container" style="{{ $finding && $finding->ttd_pimpinan ? '' : 'display:none' }}">
                        <img src="{{ $finding && $finding->ttd_pimpinan ? asset('storage/' . $finding->ttd_pimpinan) : '' }}" class="h-16 rounded-lg border preview-img">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full py-3 text-sm font-semibold rounded-xl shadow-sm hover:opacity-80 transition" style="background:#DCFCE7;color:#16A34A">Simpan</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
function compressImage(input) {
    var file = input.files[0];
    if (!file) return;
    var wrapper = input.closest('.p-4');
    var label = input.closest('label').querySelector('span');
    var previewContainer = wrapper.querySelector('.preview-container');
    var previewImg = wrapper.querySelector('.preview-img');
    label.textContent = 'Memproses...';
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = new Image();
        img.onload = function() {
            previewImg.src = e.target.result;
            previewContainer.style.display = '';
            var maxDim = 800;
            var w = img.width, h = img.height;
            if (w > maxDim || h > maxDim) {
                if (w > h) { h = h * maxDim / w; w = maxDim; }
                else { w = w * maxDim / h; h = maxDim; }
            }
            var canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);
            canvas.toBlob(function(blob) {
                var compressed = new File([blob], file.name, { type: 'image/jpeg' });
                var dt = new DataTransfer();
                dt.items.add(compressed);
                input.files = dt.files;
                label.textContent = 'Terpilih';
            }, 'image/jpeg', 0.6);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function toggleDropdown(btn) {
    var body = btn.nextElementSibling;
    var chevron = btn.querySelector('.dropdown-chevron');
    if (body.style.display === 'none') {
        body.style.display = '';
        chevron.style.transform = 'rotate(0deg)';
        body.querySelectorAll('textarea').forEach(function(ta) {
            ta.style.height = 'auto';
            ta.style.height = ta.scrollHeight + 'px';
        });
    } else {
        body.style.display = 'none';
        chevron.style.transform = 'rotate(-90deg)';
    }
}



var penjelasanItems2 = @json($penjelasanItems->map(fn($i) => ['kondisi' => $i->kondisi, 'penjelasan' => $i->penjelasan]));

var penjelasanItems3 = @json($penjelasanItems3->map(fn($i) => ['kondisi' => $i->kondisi, 'penjelasan' => $i->penjelasan]));

function getSuggestList(input) {
    var wrapper = input.closest('.relative');
    return wrapper ? wrapper.querySelector('.suggest-list') : null;
}

function getItemsForInput(input) {
    if (input.closest('[data-formulir="3"]')) {
        return penjelasanItems3;
    }
    return penjelasanItems2;
}

function renderSuggestList(input, items) {
    var list = getSuggestList(input);
    if (!list) return;
    list.innerHTML = '';
    if (items.length === 0) {
        list.classList.add('hidden');
        return;
    }
    items.forEach(function(item, idx) {
        var li = document.createElement('li');
        li.className = 'px-3 py-2.5 cursor-pointer hover:bg-blue-50' + (idx < items.length - 1 ? ' border-b border-gray-100' : '');
        var divKondisi = document.createElement('div');
        divKondisi.textContent = item.kondisi;
        divKondisi.className = 'text-sm font-semibold text-gray-800';
        var divPenjelasan = document.createElement('div');
        divPenjelasan.textContent = item.penjelasan;
        divPenjelasan.className = 'text-xs text-gray-500 mt-0.5 break-words';
        li.appendChild(divKondisi);
        li.appendChild(divPenjelasan);
        li.addEventListener('mousedown', function(e) {
            e.preventDefault();
            input.value = item.penjelasan;
            input.dispatchEvent(new Event('input'));
            list.classList.add('hidden');
        });
        list.appendChild(li);
    });
    list.classList.remove('hidden');
}

document.querySelectorAll('.relative textarea').forEach(function(input) {
    input.addEventListener('focus', function() {
        var items = getItemsForInput(this);
        var val = this.value.toLowerCase();
        var filtered = items.filter(function(item) {
            return item.kondisi.toLowerCase().indexOf(val) !== -1 || item.penjelasan.toLowerCase().indexOf(val) !== -1;
        });
        renderSuggestList(this, filtered);
    });
    input.addEventListener('input', function() {
        var items = getItemsForInput(this);
        var val = this.value.toLowerCase();
        var filtered = items.filter(function(item) {
            return item.kondisi.toLowerCase().indexOf(val) !== -1 || item.penjelasan.toLowerCase().indexOf(val) !== -1;
        });
        renderSuggestList(this, filtered);
    });
    input.addEventListener('blur', function() {
        var list = getSuggestList(this);
        if (list) {
            setTimeout(function() {
                list.classList.add('hidden');
            }, 200);
        }
    });
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('.suggest-list').forEach(function(list) {
            list.classList.add('hidden');
        });
    }
});

document.querySelectorAll('textarea').forEach(function(ta) {
    ta.style.height = '';
    if (ta.scrollHeight > ta.offsetHeight) {
        ta.style.height = ta.scrollHeight + 'px';
    }
});

function cekTypo() {
    var ta = document.getElementById('peringatanAwal');
    var text = ta.value.trim();
    if (!text) { showAlert('Isi Peringatan Awal terlebih dahulu.'); return; }

    var spinner = document.getElementById('typoSpinner');
    var btn = document.getElementById('btnCekTypo');
    spinner.classList.remove('hidden');
    btn.classList.add('opacity-50', 'pointer-events-none');

    fetch('/ai/check-typo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ text: text })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        spinner.classList.add('hidden');
        btn.classList.remove('opacity-50', 'pointer-events-none');

        if (data.error) {
            showAlert('Error: ' + data.error);
            return;
        }

        if (!data.changed) {
            showAlert('Tidak ditemukan typo dalam teks.');
            return;
        }

        showTypoModal(data.original, data.corrected);
    })
    .catch(function(err) {
        spinner.classList.add('hidden');
        btn.classList.remove('opacity-50', 'pointer-events-none');
        showAlert('Gagal menghubungi server: ' + err.message);
    });
}

function showTypoModal(original, corrected) {
    var existing = document.getElementById('typoModal');
    if (existing) existing.remove();

    var modal = document.createElement('div');
    modal.id = 'typoModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
    modal.innerHTML =
        '<div class="fixed inset-0 bg-black/50" onclick="closeTypoModal()"></div>' +
        '<div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">' +
            '<h3 class="text-base font-semibold text-gray-800 mb-1">Hasil Koreksi Typo</h3>' +
            '<p class="text-xs text-gray-500 mb-4">AI menemukan perbaikan untuk teks Anda.</p>' +
            '<div class="mb-3">' +
                '<label class="block text-xs text-gray-400 mb-1">Sebelum</label>' +
                '<div class="px-3 py-2.5 bg-red-50 border border-red-200 rounded-xl text-sm text-gray-700 whitespace-pre-wrap max-h-40 overflow-y-auto">' + escapeHtml(original) + '</div>' +
            '</div>' +
            '<div class="mb-4">' +
                '<label class="block text-xs text-gray-400 mb-1">Sesudah</label>' +
                '<div class="px-3 py-2.5 bg-green-50 border border-green-200 rounded-xl text-sm text-gray-700 whitespace-pre-wrap max-h-40 overflow-y-auto">' + escapeHtml(corrected) + '</div>' +
            '</div>' +
            '<div class="flex justify-end gap-3">' +
                '<button onclick="closeTypoModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">Batal</button>' +
                '<button onclick="applyTypoFix()" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">Terapkan</button>' +
            '</div>' +
        '</div>';
    document.body.appendChild(modal);
    window._typoCorrected = corrected;
}

function applyTypoFix() {
    var ta = document.getElementById('peringatanAwal');
    ta.value = window._typoCorrected;
    ta.style.height = 'auto';
    ta.style.height = ta.scrollHeight + 'px';
    closeTypoModal();
}

function closeTypoModal() {
    var m = document.getElementById('typoModal');
    if (m) m.remove();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

var autoSaveTimer = null;
var temuanForm = document.querySelector('form[enctype]');
var temuanFields = temuanForm.querySelectorAll('textarea, input[type=text]');
temuanFields.forEach(function(el) {
    el.addEventListener('input', function() {
        if (this.tagName === 'TEXTAREA') {
            this.style.height = '';
            this.style.height = this.scrollHeight + 'px';
        }
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            var fd = new FormData(temuanForm);
            fd.delete('ttd_petugas');
            fd.delete('ttd_pimpinan');
            fd.append('_token', '{{ csrf_token() }}');
            fetch('/{{ $prefix }}/{{ $report->id }}/temuan', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        }, 1000);
    });
});

</script>
@endpush