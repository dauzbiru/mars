@extends('layouts.admin')

@section('title', 'Analisis Min Max - MARS')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 mt-1">Analisis Min Max Checklist</h2>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">Pilih periode untuk melihat nilai minimum, maksimum, dan rata-rata tiap checklist dari semua gerai.</p>
    </div>

    <div class="px-4 sm:px-6 py-4">
        <form method="GET" action="/report/analytics/excel" target="_blank">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode Semester</label>
                <select name="semester_period_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">Pilih Periode</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}">{{ $p->label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                class="w-full px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                Export Excel
            </button>
        </form>
    </div>
</div>
@endsection