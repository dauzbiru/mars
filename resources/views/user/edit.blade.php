@extends('layouts.admin')

@section('title', 'Edit User - MARS')

@section('content')
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6">Edit User</h1>

        <form method="POST" action="/user/{{ $user->id }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}" required autocomplete="username"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('username')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <hr class="my-4">

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                <input id="password" type="password" name="password" autocomplete="new-password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="role" value="admin" {{ old('role', $user->role ?? 'admin') == 'admin' ? 'checked' : '' }} class="text-blue-600">
                        <span class="text-sm text-gray-700">Admin</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="role" value="guest" {{ old('role', $user->role ?? 'admin') == 'guest' ? 'checked' : '' }} class="text-blue-600">
                        <span class="text-sm text-gray-700">Guest</span>
                    </label>
                    @if (auth()->user()->isSuperAdmin())
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="role" value="superadmin" {{ old('role', $user->role ?? 'admin') == 'superadmin' ? 'checked' : '' }} class="text-purple-600">
                        <span class="text-sm text-gray-700">Superadmin</span>
                    </label>
                    @endif
                </div>
                @error('role')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <a href="/user" class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">Batal</a>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium" style="background:#DCFCE7;color:#16A34A">Simpan</button>
            </div>
        </form>
    </div>
@endsection
