@extends('layouts.admin')

@section('title', 'Template Excel - MARS')

@section('content')
<div class="max-w-lg mx-auto space-y-4">
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @php
        $monitoringExists = Storage::exists('excel-template-monitoring.xlsx');
        $praMonitoringExists = Storage::exists('excel-template-pra-monitoring.xlsx');
        $evaluasiExists = Storage::exists('excel-template-evaluasi.xlsx');
        $wordExists = Storage::exists('word-template-pra-monitoring.docx') || Storage::exists('word-template-pra-monitoring.doc');
    @endphp

    {{-- Monitoring --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">Template Monitoring</h2>
        </div>
        <div class="px-4 sm:px-6 py-4">
            <p class="text-sm text-gray-600 mb-4">
                Upload file Excel (.xlsx) untuk laporan <strong>Monitoring</strong>.
                Placeholder akan diganti dengan data laporan saat ekspor.
            </p>
            <form method="POST" enctype="multipart/form-data" action="/excel-template/upload">
                @csrf
                <input type="hidden" name="type" value="monitoring">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Template Monitoring</label>
                    <input type="file" name="template" accept=".xlsx" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @error('template')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Upload
                </button>
            </form>
            @if ($monitoringExists)
                <div class="mt-4 p-3 bg-green-50 rounded-lg flex items-center justify-between">
                    <span class="text-sm text-green-700">Template Monitoring sudah diupload</span>
                    <form method="POST" action="/excel-template/delete" onsubmit="showConfirm('Hapus template monitoring?', function(){ this.submit(); }.bind(this)); return false;">
                        @csrf @method('DELETE')
                        <input type="hidden" name="type" value="monitoring">
                        <button class="text-xs text-red-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Pra-Monitoring --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">Template Pra-Monitoring</h2>
        </div>
        <div class="px-4 sm:px-6 py-4">
            <p class="text-sm text-gray-600 mb-4">
                Upload file Excel (.xlsx) untuk laporan <strong>Pra-Monitoring</strong>.
                Placeholder akan diganti dengan data laporan saat ekspor.
            </p>
            <form method="POST" enctype="multipart/form-data" action="/excel-template/upload">
                @csrf
                <input type="hidden" name="type" value="pra-monitoring">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Template Pra-Monitoring</label>
                    <input type="file" name="template" accept=".xlsx" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @error('template')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Upload
                </button>
            </form>
            @if ($praMonitoringExists)
                <div class="mt-4 p-3 bg-green-50 rounded-lg flex items-center justify-between">
                    <span class="text-sm text-green-700">Template Pra-Monitoring sudah diupload</span>
                    <form method="POST" action="/excel-template/delete" onsubmit="showConfirm('Hapus template pra-monitoring?', function(){ this.submit(); }.bind(this)); return false;">
                        @csrf @method('DELETE')
                        <input type="hidden" name="type" value="pra-monitoring">
                        <button class="text-xs text-red-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Evaluasi --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">Template Evaluasi</h2>
        </div>
        <div class="px-4 sm:px-6 py-4">
            <p class="text-sm text-gray-600 mb-4">
                Upload file Excel (.xlsx) untuk laporan <strong>Evaluasi</strong>.
            </p>
            <form method="POST" enctype="multipart/form-data" action="/excel-template/evaluasi/upload">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Template Evaluasi</label>
                    <input type="file" name="template" accept=".xlsx" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @error('template')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    style="background:#3B82F6;color:#FFFFFF"
                    class="w-full px-4 py-2.5 text-sm font-medium rounded-lg hover:opacity-80">
                    Upload
                </button>
            </form>
            @if ($evaluasiExists)
                <div class="mt-4 p-3 bg-green-50 rounded-lg flex items-center justify-between">
                    <span class="text-sm text-green-700">Template Evaluasi sudah diupload</span>
                    <form method="POST" action="/excel-template/evaluasi/delete" onsubmit="showConfirm('Hapus template evaluasi?', function(){ this.submit(); }.bind(this)); return false;">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Template Surat Word (Pra-Monitoring) --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">Template Surat Pra-Monitoring (Word)</h2>
        </div>
        <div class="px-4 sm:px-6 py-4">
            <p class="text-sm text-gray-600 mb-4">
                Upload file Word (.docx) untuk surat pengantar laporan <strong>Pra-Monitoring</strong>.
            </p>
            <form method="POST" enctype="multipart/form-data" action="/word-template/upload">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Template Surat</label>
                    <input type="file" name="template" accept=".doc,.docx" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @error('template')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Upload
                </button>
            </form>
            @if ($wordExists)
                <div class="mt-4 p-3 bg-green-50 rounded-lg flex items-center justify-between">
                    <span class="text-sm text-green-700">Template Surat Word sudah diupload</span>
                    <form method="POST" action="/word-template/delete" onsubmit="showConfirm('Hapus template surat word?', function(){ this.submit(); }.bind(this)); return false;">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    </div>
@endsection
