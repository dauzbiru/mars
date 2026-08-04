@extends('layouts.admin')

@section('title', 'Import Gerai - MARS')

@section('content')
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Import Gerai</h1>
        <p class="text-sm text-gray-500 mb-6">Upload file Excel untuk import data gerai.</p>

        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <p class="font-medium mb-1">Format file:</p>
            <p>Kolom A: Kode Gerai<br>Kolom B: Nama Gerai<br>Kolom C: Franchisee<br>Kolom D: Alamat<br>Kolom E: Email<br>Kolom F: No Telepon<br>Kolom G: Opening (format dd-mm-yyyy, opsional)<br>Kolom H: Nama Kota (opsional, auto dari kode)<br>Kolom I: Area (opsional, auto dari kode)</p>
            <a href="/gerais/template" target="_blank" class="mt-2 inline-block text-blue-600 hover:underline font-medium">Download template &rarr;</a>
        </div>

        <form method="POST" action="/gerais/import" enctype="multipart/form-data">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih file Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('file')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">Import</button>
        </form>
    </div>
@endsection
