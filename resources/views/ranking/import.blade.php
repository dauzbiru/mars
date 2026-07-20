@extends('layouts.admin')

@section('title', 'Import Nilai - MARS')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Import Nilai Monitoring</h1>
        <a href="/daftar-nilai/import/template"
            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
            Download Template
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="font-semibold text-gray-800 mb-2">Format Template</h2>
        <div class="text-sm text-gray-600 space-y-1">
            <p>Kolom: <strong>Kode Gerai</strong> | <strong>Nama Gerai</strong> | <strong>Tanggal</strong> (DD-MM-YYYY) | <strong>Petugas</strong> (username) | <strong>Skor</strong></p>
            <p>Nama Gerai hanya sebagai referensi, sistem tetap membaca berdasarkan Kode Gerai.</p>
            <p>Cocok dengan kolom yang tampil di halaman Daftar Nilai.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="POST" action="/daftar-nilai/import" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">File Excel</label>
                <input type="file" name="file" accept=".xlsx" required
                    class="block w-full text-sm text-gray-500 border rounded-lg cursor-pointer file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-sm file:font-medium hover:file:bg-blue-100">
                @error('file')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                Import
            </button>
        </form>
    </div>
@endsection
