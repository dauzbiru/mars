@extends('layouts.admin')

@section('title', 'Edit Gerai - MARS')

@section('content')
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6">Edit Gerai</h1>

        <form method="POST" action="/gerais/{{ $gerai->id }}">
            @csrf @method('PUT')

            <div class="mb-4">
                <label for="kode_gerai" class="block text-sm font-medium text-gray-700 mb-1">Kode Gerai</label>
                <input id="kode_gerai" type="text" name="kode_gerai" value="{{ old('kode_gerai', $gerai->kode_gerai) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('kode_gerai')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="nama_gerai" class="block text-sm font-medium text-gray-700 mb-1">Nama Gerai</label>
                <input id="nama_gerai" type="text" name="nama_gerai" value="{{ old('nama_gerai', $gerai->nama_gerai) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('nama_gerai')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="franchisee" class="block text-sm font-medium text-gray-700 mb-1">Franchisee</label>
                <input id="franchisee" type="text" name="franchisee" value="{{ old('franchisee', $gerai->franchisee) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('franchisee')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea id="alamat" name="alamat" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('alamat', $gerai->alamat) }}</textarea>
                @error('alamat')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $gerai->email) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No Telepon</label>
                <input id="no_telepon" type="text" name="no_telepon" value="{{ old('no_telepon', $gerai->no_telepon) }}" placeholder="08xxxxxxxxxx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('no_telepon')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="opening_at" class="block text-sm font-medium text-gray-700 mb-1">Opening</label>
                <input id="opening_at" type="date" name="opening_at" value="{{ old('opening_at', $gerai->opening_at?->format('Y-m-d')) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('opening_at')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="nama_kota" class="block text-sm font-medium text-gray-700 mb-1">Nama Kota</label>
                <input id="nama_kota" type="text" name="nama_kota" value="{{ old('nama_kota', $gerai->nama_kota) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('nama_kota')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="area" class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                <input id="area" type="text" name="area" value="{{ old('area', $gerai->area) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('area')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <a href="/gerais" class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">Batal</a>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
@endsection
