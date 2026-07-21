<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - MARS')</title>
    <link rel="icon" type="image/png" href="/images/biru-favicon.png?v=5">
    <link rel="shortcut icon" href="/favicon.ico?v=5">
    <link rel="stylesheet" href="/build/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" integrity="sha256-GzSkJVLJbxDk36qko2cnawOGiqz/Y8GsQv/jMTUrx1Q=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/airbnb.css" integrity="sha256-LmZ7wnicF1GBpKNxhhOURrtTXXl7vgjlNtFyVcjZsHk=" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" integrity="sha256-Huqxy3eUcaCwqqk92RwusapTfWlvAasF6p2rxV6FJaE=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js" integrity="sha256-cvHCpHmt9EqKfsBeDHOujIlR5wZ8Wy3s90da1L3sGkc=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        button[type="submit"], button:not([type]) {
            transition: all 0.15s ease;
            cursor: pointer;
        }
        button[type="submit"]:hover, button:not([type]):hover {
            filter: brightness(0.85);
        }
        button[type="submit"]:active, button:not([type]):active {
            transform: scale(0.97);
            filter: brightness(0.75);
        }
        .swal2-popup.swal2-toast {
            font-size: 13px;
        }
        .swal2-popup:not(.swal2-toast) {
            max-width: 360px !important;
            padding: 24px !important;
            border-radius: 12px !important;
        }
        .swal2-popup:not(.swal2-toast) .swal2-icon {
            margin: 0 auto 14px !important;
            width: 48px !important;
            height: 48px !important;
        }
        .swal2-popup:not(.swal2-toast) .swal2-title {
            font-size: 16px !important;
            padding: 0 !important;
            margin-bottom: 8px !important;
        }
        .swal2-popup:not(.swal2-toast) .swal2-html-container {
            font-size: 14px !important;
            margin: 0 !important;
        }
        .swal2-popup:not(.swal2-toast) .swal2-actions {
            margin-top: 14px !important;
            gap: 8px !important;
        }
        .swal2-popup:not(.swal2-toast) .swal2-styled {
            padding: 8px 20px !important;
            font-size: 14px !important;
            border-radius: 8px !important;
        }
    </style>
    @stack('head')
</head>
<body class="bg-gray-100 min-h-screen">
@if (request('embedded'))
    <main class="p-4 sm:p-6">
        @yield('content')
    </main>
@else
    {{-- Overlay --}}
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-20 hidden" onclick="toggleSidebar()"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-md border-r transform -translate-x-full transition-transform duration-200 flex flex-col">

        <div class="p-4 border-b">
            <div id="sidebarMars">
                <img src="/images/logo.png" alt="MARS" class="h-10 w-auto">
            </div>
        </div>

        <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
            @if (auth()->user()->role === 'guest')
                <a href="/guest"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('guest') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Beranda
                </a>
                <hr class="border-gray-200 my-2">
                <a href="/report/monitoring"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/monitoring') && !request()->is('report/pra-monitoring') && !request()->is('report/re-monitoring') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Monitoring
                </a>
                <a href="/report/pra-monitoring"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/pra-monitoring') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Pra-Monitoring
                </a>
                <a href="/report/re-monitoring"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/re-monitoring') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Re-Monitoring
                </a>
            @else
                <a href="/dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
                <a href="/user"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('user') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    User
                </a>
                <a href="/gerais"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('gerais*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Gerai
                </a>
                <a href="/pgs"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('pgs*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Data PG
                </a>
                {{-- Tugas Dropdown --}}
                @php
                    $isTugasActive = request()->is('categories*') || request()->is('tugas/penjelasan-formulir-2') || request()->is('tugas/penjelasan-formulir-3');
                @endphp
                <button onclick="toggleTugas()"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isTugasActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span class="flex-1 text-left">Tugas</span>
                    <svg id="tugasArrow" class="w-4 h-4 transition-transform {{ $isTugasActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="tugasSubmenu" class="ml-4 space-y-1 {{ $isTugasActive ? '' : 'hidden' }}">
                    <a href="/categories"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('categories*') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Daftar Tugas
                    </a>
                    <a href="/tugas/penjelasan-formulir-2"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('tugas/penjelasan-formulir-2') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Penjelasan Formulir 2
                    </a>
                    <a href="/tugas/penjelasan-formulir-3"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('tugas/penjelasan-formulir-3') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Penjelasan Formulir 3
                    </a>
                </div>
                <a href="/komplain"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('komplain*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    Data Komplain
                </a>
                <hr class="border-gray-200 my-1">
                {{-- Monitoring Dropdown --}}
                @php
                    $isMonitoringActive = request()->is('report/monitoring') || request()->is('daftar-nilai') || request()->is('daftar-nilai/peringkat') || request()->is('daftar-nilai/performa') || request()->is('daftar-nilai/import') || request()->is('semester-periods*') || request()->is('gerai-pendampingan') || request()->is('nilai-pairing');
                @endphp
                <button onclick="toggleMonitoring()"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isMonitoringActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="flex-1 text-left">Monitoring</span>
                    <svg id="monitoringArrow" class="w-4 h-4 transition-transform {{ $isMonitoringActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="monitoringSubmenu" class="ml-4 space-y-1 {{ $isMonitoringActive ? '' : 'hidden' }}">
                    <a href="/report/monitoring"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/monitoring') && !request()->is('report/pra-monitoring') && !request()->is('report/re-monitoring') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Laporan Monitoring
                    </a>
                    <a href="/daftar-nilai"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('daftar-nilai') && !request()->is('daftar-nilai/performa') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Daftar Nilai Monitoring
                    </a>
                    <a href="/daftar-nilai/peringkat"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('daftar-nilai/peringkat') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Peringkat Monitoring
                    </a>
                    <a href="/daftar-nilai/performa"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('daftar-nilai/performa') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Performa Gerai
                    </a>
                    <a href="/gerai-pendampingan"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('gerai-pendampingan') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Daftar Gerai Pendampingan
                    </a>
                    <a href="/nilai-pairing"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('nilai-pairing') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Nilai Pairing
                    </a>
                    <a href="/daftar-nilai/import"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('daftar-nilai/import') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Import Nilai
                    </a>
                    <a href="/semester-periods"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('semester-periods*') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-700' }}">
                        Periode Semester
                    </a>
                </div>
                <a href="/report/pra-monitoring"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/pra-monitoring') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Pra-Monitoring
                </a>
                <a href="/report/re-monitoring"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/re-monitoring') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Re-Monitoring
                </a>
                <a href="/report/evaluasi"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('report/evaluasi') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Evaluasi
                </a>
                <a href="/excel-template"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('excel-template') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Template Excel
                </a>
                <a href="/settings"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan
                </a>
            @endif
        </nav>

        <div class="p-4 border-t">
            <button onclick="showConfirm('Yakin ingin logout?', function(){ document.getElementById('sidebarLogoutForm').submit(); })" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-red-50 hover:text-red-600">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
            <form id="sidebarLogoutForm" method="POST" action="/logout" class="hidden">
                @csrf
            </form>
        </div>

        <div class="px-4 py-3 text-center text-xs text-gray-400">
            &copy; 2026 VirdauzyRizky. All Rights Reserved.
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-h-screen">
        {{-- Navbar --}}
        <header class="sticky top-0 z-30 bg-white shadow-sm border-b h-14 flex items-center px-4 gap-1 sm:gap-3 shrink-0">
            <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-800 shrink-0 relative w-6 h-6" id="burgerBtn">
                <span class="absolute left-0 top-0 w-full h-[2px] bg-current rounded" id="burgerTop" style="transition:transform 0.3s"></span>
                <span class="absolute left-0 top-1/2 -mt-[1px] w-full h-[2px] bg-current rounded" id="burgerMid" style="transition:opacity 0.3s"></span>
                <span class="absolute left-0 bottom-0 w-full h-[2px] bg-current rounded" id="burgerBot" style="transition:transform 0.3s"></span>
            </button>
            <h1 class="text-lg font-bold text-gray-800 truncate transition-all duration-300" id="navbarMars">MARS <small class="text-xs font-normal text-gray-400 hidden sm:inline">(Monitoring Assessment and Reporting System)</small></h1>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                @php
                    $pendingReports = collect();
                    $isAssessmentPage = request()->is('*/assessment') || request()->is('*/assessment/*') || request()->is('*/temuan');
                    if (!$isAssessmentPage) {
                        $userId = auth()->id();
                        $rows = \DB::select("
                            SELECT 'monitoring' AS report_type, id FROM monitoring_reports
                            WHERE user_id = ? AND submit_at IS NULL AND checkin_at IS NOT NULL
                            UNION ALL
                            SELECT 'pra_monitoring' AS report_type, id FROM pra_monitoring_reports
                            WHERE user_id = ? AND submit_at IS NULL AND checkin_at IS NOT NULL
                            UNION ALL
                            SELECT 're_monitoring' AS report_type, id FROM re_monitoring_reports
                            WHERE user_id = ? AND submit_at IS NULL AND checkin_at IS NOT NULL
                            UNION ALL
                            SELECT 'evaluasi' AS report_type, id FROM evaluasi_reports
                            WHERE user_id = ? AND tanggal IS NULL
                        ", [$userId, $userId, $userId, $userId]);
                        $grouped = collect($rows)->groupBy('report_type');
                        $typeModelMap = [
                            'monitoring'    => \App\Models\MonitoringReport::class,
                            'pra_monitoring' => \App\Models\PraMonitoringReport::class,
                            're_monitoring'  => \App\Models\ReMonitoringReport::class,
                            'evaluasi'       => \App\Models\EvaluasiReport::class,
                        ];
                        foreach ($typeModelMap as $type => $modelClass) {
                            if ($grouped->has($type)) {
                                $pendingReports = $pendingReports->concat(
                                    $modelClass::whereIn('id', $grouped[$type]->pluck('id'))->with('gerai')->get()
                                );
                            }
                        }
                        $pendingReports = $pendingReports->sortByDesc(function ($r) {
                            return $r->checkin_at ?? $r->created_at;
                        })->values();
                    }
                @endphp
                @if (!$isAssessmentPage)
                <div class="relative" id="notifWrapper">
                    <button onclick="document.getElementById('notifDropdown').classList.toggle('hidden')" class="text-gray-500 hover:text-gray-700" style="background:none;border:none;cursor:pointer;position:relative;padding:6px;">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if ($pendingReports->isNotEmpty())
                        <span style="position:absolute;top:0;right:0;background:#EF4444;color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;line-height:1;border:2px solid #fff;">{{ $pendingReports->count() }}</span>
                        @endif
                    </button>
                    @if ($pendingReports->isNotEmpty())
                    <div id="notifDropdown" class="hidden absolute right-0 top-full mt-1 w-72 bg-white rounded-lg shadow-lg border py-2 z-50 max-h-80 overflow-y-auto">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase">Laporan Belum Submit</p>
                        </div>
                        @foreach ($pendingReports as $r)
                        @php
                            $prefix = match(class_basename($r)) {
                                'PraMonitoringReport' => 'pra-monitoring',
                                'ReMonitoringReport' => 're-monitoring',
                                'EvaluasiReport' => 'evaluasi',
                                default => 'monitoring',
                            };
                        @endphp
                        <a href="/{{ $prefix }}/{{ $r->id }}/assessment"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $r->gerai->kode_gerai ?? '-' }} - {{ $r->gerai->nama_gerai ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ str_replace('-', ' ', ucfirst($prefix)) }} • {{ ($r->checkin_at ?? $r->created_at)?->format('d M Y H:i') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
                <div class="relative" id="buatLaporanWrapper" @if (auth()->user()->role === 'guest' && request()->is('guest')) style="display:none" @endif>
                <button onclick="toggleBuatLaporan()" style="background:#3B82F6;color:#FFFFFF" class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg hover:opacity-80 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Laporan
                </button>
                <div id="buatLaporanDropdown" class="hidden absolute right-0 top-full mt-1 w-52 bg-white rounded-lg shadow-lg border py-1 z-50">
                    <a href="/monitoring" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Monitoring
                    </a>
                    <a href="/pra-monitoring" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Pra-Monitoring
                    </a>
                    <a href="/re-monitoring" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Re-Monitoring
                    </a>
                    @if (auth()->user()->role === 'admin')
                    <a href="/evaluasi" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Evaluasi
                    </a>
                    @endif
                </div>
            </div>
            <span class="text-sm font-medium text-gray-700 shrink-0">{{ auth()->user()->name }}</span>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-auto">
            @yield('content')
        </main>
    </div>
@endif

    <script>
        function toggleSearch(inputId, btn, submitOnCollapse) {
            var input = document.getElementById(inputId);
            if (input.classList.contains('w-0')) {
                input.classList.remove('w-0', 'px-0', 'border-0', 'opacity-0', 'pointer-events-none');
                input.classList.add('w-48', 'sm:w-64', 'px-3', 'border', 'border-gray-300', 'rounded-lg', 'opacity-100', 'pointer-events-auto');
                input.focus();
            } else {
                input.classList.add('w-0', 'px-0', 'border-0', 'opacity-0', 'pointer-events-none');
                input.classList.remove('w-48', 'sm:w-64', 'px-3', 'border', 'border-gray-300', 'rounded-lg', 'opacity-100', 'pointer-events-auto');
                input.value = '';
                input.dispatchEvent(new Event('input'));
                if (submitOnCollapse) input.closest('form').submit();
            }
        }

        function positionSuggest(btn, listId) {
            var list = document.getElementById(listId);
            if (!list) return;
            if (list.parentElement !== document.body) {
                document.body.appendChild(list);
            }
            var rect = btn.getBoundingClientRect();
            list.style.position = 'fixed';
            list.style.top = (rect.bottom + 4) + 'px';
            list.style.right = (window.innerWidth - rect.right) + 'px';
        }

        document.addEventListener('click', function(e) {
            document.querySelectorAll('[id^="searchGerai"], [id="searchLaporan"], [id="searchRanking"], [id="searchPraMonitoring"], [id="searchKomplain"], [id="searchUser"], [id="searchPg"], [id="searchPeriode"], [id="geraiSearch"]').forEach(function(input) {
                var container = input.closest('.relative');
                if (container && !container.contains(e.target) && !input.classList.contains('w-0') && input.value === '') {
                    input.classList.add('w-0', 'px-0', 'border-0', 'opacity-0', 'pointer-events-none');
                    input.classList.remove('w-48', 'sm:w-64', 'px-3', 'border', 'border-gray-300', 'rounded-lg', 'opacity-100', 'pointer-events-auto');
                }
            });
        });

        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');

            var top = document.getElementById('burgerTop');
            var mid = document.getElementById('burgerMid');
            var bot = document.getElementById('burgerBot');
            var navbarMars = document.getElementById('navbarMars');
            var sidebarMars = document.getElementById('sidebarMars');
            if (sidebar.classList.contains('-translate-x-full')) {
                top.style.transform = 'none';
                mid.style.opacity = '1';
                bot.style.transform = 'none';
                navbarMars.style.opacity = '1';
                navbarMars.style.transform = 'translateX(0)';
            } else {
                top.style.transform = 'translateY(11px) rotate(45deg)';
                mid.style.opacity = '0';
                bot.style.transform = 'translateY(-11px) rotate(-45deg)';
                navbarMars.style.opacity = '0';
                navbarMars.style.transform = 'translateX(20px)';
            }
        }
        function toggleBuatLaporan() {
            var dd = document.getElementById('buatLaporanDropdown');
            dd.classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            var wrapper = document.getElementById('buatLaporanWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('buatLaporanDropdown').classList.add('hidden');
            }
            var notifWrapper = document.getElementById('notifWrapper');
            if (notifWrapper && !notifWrapper.contains(e.target)) {
                document.getElementById('notifDropdown').classList.add('hidden');
            }
        });
        function toggleTugas() {
            document.getElementById('tugasSubmenu').classList.toggle('hidden');
            document.getElementById('tugasArrow').classList.toggle('rotate-180');
        }
        function toggleMonitoring() {
            document.getElementById('monitoringSubmenu').classList.toggle('hidden');
            document.getElementById('monitoringArrow').classList.toggle('rotate-180');
        }

        function closeModal() {
            Swal.close();
        }

        function showAlert(msg) {
            Swal.fire({ icon: 'info', title: msg, confirmButtonText: 'OK', confirmButtonColor: '#3B82F6' });
        }

        function showConfirm(msg, onConfirm) {
            Swal.fire({
                title: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3B82F6',
                cancelButtonColor: '#9CA3AF',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Tidak'
            }).then(function(result) {
                if (result.isConfirmed) onConfirm();
            });
        }

        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.method && form.method.toUpperCase() === 'GET') return;
            var submitBtn = e.submitter;
            if (submitBtn && submitBtn.type === 'submit') {
                submitBtn.disabled = true;
                setTimeout(function() { submitBtn.disabled = false; }, 3000);
            }
        });
    </script>
    <script>
        @if (session('success'))
        Swal.fire({ toast: true, position: 'top', icon: 'success', title: @json(session('success')), showConfirmButton: false, timer: 3000, timerProgressBar: true, confirmButtonColor: '#3B82F6' });
        @endif
        @if (session('warning'))
        Swal.fire({ toast: true, position: 'top', icon: 'warning', title: @json(session('warning')), showConfirmButton: false, timer: 3000, timerProgressBar: true, confirmButtonColor: '#3B82F6' });
        @endif
        @if (session('error'))
        Swal.fire({ toast: true, position: 'top', icon: 'error', title: @json(session('error')), showConfirmButton: false, timer: 3000, timerProgressBar: true, confirmButtonColor: '#3B82F6' });
        @endif
    </script>
@stack('scripts')
</body>
</html>
