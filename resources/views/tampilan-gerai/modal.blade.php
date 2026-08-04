<div id="tgModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.5)" onclick="closeTampilanGeraiModal()">
    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 flex flex-col" style="max-height:80vh" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 shrink-0">
            <h3 class="text-sm font-bold text-gray-800">Foto Tampilan Gerai</h3>
            <button type="button" onclick="closeTampilanGeraiModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer p-1" aria-label="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="tgBlocks" class="flex-1 overflow-y-auto px-4 py-3 space-y-3"></div>

        <div class="px-4 py-2.5 border-t border-gray-100 shrink-0 space-y-2">
            <button type="button" onclick="tgAddBlock()" class="w-full py-2 rounded-xl border-2 border-blue-300 text-sm font-semibold text-blue-600 hover:bg-blue-50 cursor-pointer transition-colors" style="border-style:dashed">
                + Tambah Keterangan
            </button>
            <button type="button" onclick="closeTampilanGeraiModal()" class="w-full py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold cursor-pointer hover:bg-gray-200 transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var tgType = @json($tgType);
var tgReportId = @json($tgReportId);
var tgLoaded = false;
var tgTimers = {};

function tgCsrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
}

function tgFetch(url, options) {
    options = options || {};
    options.headers = Object.assign({ 'X-CSRF-TOKEN': tgCsrf(), 'Accept': 'application/json' }, options.headers || {});
    return fetch(url, options);
}

function openTampilanGeraiModal() {
    document.getElementById('tgModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (!tgLoaded) {
        tgLoad();
    }
}

function closeTampilanGeraiModal() {
    tgFlushKeterangan();
    document.getElementById('tgModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function tgLoad() {
    tgFetch('/tampilan-gerai/' + tgType + '/' + tgReportId)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            tgLoaded = true;
            var container = document.getElementById('tgBlocks');
            container.innerHTML = '';
            if (!data.blocks || data.blocks.length === 0) {
                tgAddBlock();
                return;
            }
            data.blocks.forEach(tgRenderBlock);
        });
}

function tgRenderBlock(block) {
    var container = document.getElementById('tgBlocks');
    var div = document.createElement('div');
    div.className = 'bg-gray-50 border border-gray-100 rounded-xl p-3';
    div.dataset.blockId = block.id;

    var header = document.createElement('div');
    header.className = 'flex items-center justify-between mb-1';
    var label = document.createElement('label');
    label.className = 'text-[10px] font-medium text-gray-500';
    label.textContent = 'Keterangan';
    var right = document.createElement('div');
    right.className = 'flex items-center gap-2';
    var status = document.createElement('span');
    status.className = 'tg-status text-[10px] text-green-600';
    status.textContent = '';
    var del = document.createElement('button');
    del.type = 'button';
    del.className = 'text-[10px] text-red-500 hover:text-red-700 cursor-pointer';
    del.textContent = 'Hapus';
    del.onclick = function () { tgDeleteBlock(div); };
    right.appendChild(status);
    right.appendChild(del);
    header.appendChild(label);
    header.appendChild(right);

    var ta = document.createElement('textarea');
    ta.rows = 1;
    ta.className = 'w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none overflow-hidden';
    ta.placeholder = 'Tulis keterangan kondisi tampilan gerai...';
    ta.value = block.keterangan || '';
    ta.oninput = function () {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
        tgSaveKeterangan(block.id, ta.value);
    };
    ta.onblur = function () { tgSaveKeterangan(block.id, ta.value, true); };

    var photos = document.createElement('div');
    photos.className = 'flex flex-wrap gap-1.5 mt-1.5 tg-photos';
    (block.photos || []).forEach(function (p) {
        photos.appendChild(tgPhotoTile(p.id, p.url));
    });
    photos.appendChild(tgAddPhotoTile(block.id));

    div.appendChild(header);
    div.appendChild(ta);
    div.appendChild(photos);
    container.appendChild(div);
    ta.style.height = 'auto';
    ta.style.height = ta.scrollHeight + 'px';
}

function tgPhotoTile(id, url) {
    var tile = document.createElement('div');
    tile.className = 'relative rounded-lg overflow-hidden border border-gray-200 bg-white shrink-0';
    tile.style.width = '64px';
    tile.style.height = '64px';
    tile.dataset.photoId = id;
    var img = document.createElement('img');
    img.src = url;
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'cover';
    img.loading = 'lazy';
    var x = document.createElement('button');
    x.type = 'button';
    x.className = 'absolute rounded-full bg-white text-gray-600 flex items-center justify-center cursor-pointer';
    x.style.top = '2px';
    x.style.right = '2px';
    x.style.width = '16px';
    x.style.height = '16px';
    x.style.fontSize = '10px';
    x.style.lineHeight = '1';
    x.style.background = 'rgba(0,0,0,0.55)';
    x.style.color = '#fff';
    x.innerHTML = '&times;';
    x.onclick = function () { tgDeletePhoto(tile); };
    tile.appendChild(img);
    tile.appendChild(x);
    return tile;
}

function tgAddPhotoTile(blockId) {
    var label = document.createElement('label');
    label.className = 'rounded-lg bg-white flex items-center justify-center cursor-pointer hover:bg-blue-50 shrink-0';
    label.style.width = '64px';
    label.style.height = '64px';
    label.style.border = '2px dashed #93C5FD';
    label.innerHTML = '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.className = 'hidden';
    input.onchange = function () { tgUploadPhoto(label, input, blockId); };
    label.appendChild(input);
    return label;
}

function tgCompress(file) {
    return new Promise(function (resolve) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
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
                canvas.toBlob(function (blob) {
                    if (blob) {
                        resolve(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
                    } else {
                        resolve(file);
                    }
                }, 'image/jpeg', 0.6);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

function tgEnsureBlock(blockId) {
    if (blockId && document.querySelector('#tgBlocks [data-block-id="' + blockId + '"]')) {
        return Promise.resolve(blockId);
    }
    return tgFetch('/tampilan-gerai/' + tgType + '/' + tgReportId + '/block', { method: 'POST' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var id = data.id;
            tgRenderBlock({ id: id, keterangan: '', photos: [] });
            return id;
        });
}

function tgUploadPhoto(label, input, blockId) {
    var file = input.files[0];
    if (!file) return;
    label.style.opacity = '0.5';
    var originalContainer = label.parentNode;
    tgCompress(file).then(function (compressed) {
        return tgEnsureBlock(blockId).then(function (id) {
            var fd = new FormData();
            fd.append('foto', compressed);
            fd.append('block_id', id);
            return tgFetch('/tampilan-gerai/' + tgType + '/' + tgReportId + '/photo', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); });
        });
    }).then(function (data) {
        input.value = '';
        if (!data || !data.id) throw new Error('upload failed');
        var blockDiv = document.querySelector('#tgBlocks [data-block-id="' + data.block_id + '"]');
        var photos = (blockDiv && blockDiv.querySelector('.tg-photos')) || originalContainer;
        var tile = tgPhotoTile(data.id, data.url);
        if (photos && photos.contains(label)) {
            photos.insertBefore(tile, label);
        } else if (photos) {
            photos.appendChild(tile);
        }
        if (label.parentNode) label.style.opacity = '';
    }).catch(function () {
        input.value = '';
        if (label.parentNode) label.style.opacity = '';
        alert('Gagal mengunggah foto.');
    });
}

function tgDeletePhoto(tile) {
    var id = tile.dataset.photoId;
    tgFetch('/tampilan-gerai/photo/' + id, { method: 'DELETE' })
        .then(function () { tile.remove(); });
}

function tgDeleteBlock(div) {
    var id = div.dataset.blockId;
    if (tgTimers[id]) { clearTimeout(tgTimers[id]); delete tgTimers[id]; }
    tgFetch('/tampilan-gerai/block/' + id, { method: 'DELETE' })
        .then(function () { div.remove(); });
}

function tgSaveKeterangan(id, value, immediate) {
    if (immediate) {
        if (tgTimers[id]) { clearTimeout(tgTimers[id]); delete tgTimers[id]; }
        tgSendKeterangan(id, value);
        return;
    }
    if (tgTimers[id]) clearTimeout(tgTimers[id]);
    tgTimers[id] = setTimeout(function () {
        delete tgTimers[id];
        tgSendKeterangan(id, value);
    }, 500);
}

function tgSendKeterangan(id, value) {
    var statusEl = document.querySelector('#tgBlocks [data-block-id="' + id + '"] .tg-status');
    if (statusEl) { statusEl.textContent = 'Menyimpan...'; statusEl.style.color = '#d97706'; }
    var fd = new URLSearchParams();
    fd.append('keterangan', value);
    fd.append('_token', tgCsrf());
    tgFetch('/tampilan-gerai/block/' + id, { method: 'PATCH', body: fd, keepalive: true })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.deleted) {
                var div = document.querySelector('#tgBlocks [data-block-id="' + id + '"]');
                if (div) div.remove();
                return;
            }
            if (statusEl) {
                statusEl.textContent = 'Tersimpan';
                statusEl.style.color = '#16a34a';
                setTimeout(function () {
                    if (statusEl.textContent === 'Tersimpan') statusEl.textContent = '';
                }, 1500);
            }
        })
        .catch(function () {
            if (statusEl) { statusEl.textContent = 'Gagal simpan'; statusEl.style.color = '#dc2626'; }
        });
}

function tgFlushKeterangan() {
    document.querySelectorAll('#tgBlocks [data-block-id]').forEach(function (div) {
        var id = div.dataset.blockId;
        if (tgTimers[id]) { clearTimeout(tgTimers[id]); delete tgTimers[id]; }
        var ta = div.querySelector('textarea');
        if (ta) tgSendKeterangan(id, ta.value);
    });
}

function tgFlushKeteranganBeacon() {
    if (!navigator.sendBeacon) {
        tgFlushKeterangan();
        return;
    }
    document.querySelectorAll('#tgBlocks [data-block-id]').forEach(function (div) {
        var ta = div.querySelector('textarea');
        if (!ta) return;
        var id = div.dataset.blockId;
        if (tgTimers[id]) { clearTimeout(tgTimers[id]); delete tgTimers[id]; }
        var fd = new FormData();
        fd.append('keterangan', ta.value);
        fd.append('_token', tgCsrf());
        fd.append('_method', 'PATCH');
        navigator.sendBeacon('/tampilan-gerai/block/' + id, fd);
    });
}

window.addEventListener('beforeunload', tgFlushKeteranganBeacon);

function tgAddBlock() {
    tgFetch('/tampilan-gerai/' + tgType + '/' + tgReportId + '/block', { method: 'POST' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            tgRenderBlock({ id: data.id, keterangan: '', photos: [] });
        });
}
</script>
@endpush
