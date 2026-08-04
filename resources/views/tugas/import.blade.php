@extends('layouts.admin')

@section('title', 'Import ' . $title . ' - MARS')

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 mt-1">Import Excel</h2>
        <p class="text-xs text-gray-500 mt-1">Upload file Excel dengan kolom <strong>Kondisi</strong> dan <strong>Penjelasan</strong>.</p>
    </div>

    <div class="px-4 sm:px-6 py-4 space-y-4">
        <a href="/tugas/penjelasan-formulir/{{ $formulir }}/template" target="_blank"
            class="inline-block px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
            Download Template Excel
        </a>

        <form method="POST" action="/tugas/penjelasan-formulir/{{ $formulir }}/import" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <button type="submit"
                class="w-full px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                Import
            </button>
        </form>
    </div>
</div>
@endsection
