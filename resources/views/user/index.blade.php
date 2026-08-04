@extends('layouts.admin')

@section('title', 'Admin - MARS')

@section('content')
    @if ($tab === 'user')
        <div class="bg-white rounded-xl shadow-md">
            <div class="sticky top-0 bg-white z-10 px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Daftar User</h2>
                    <span class="text-xs sm:text-sm text-gray-500">{{ $users->count() }} user</span>
                </div>
                <div class="relative flex items-center gap-1 sm:gap-2 shrink-0">
                    <input type="text" id="searchUser" placeholder="Cari user..."
                        class="absolute right-full mr-2 w-0 px-0 py-2 border-0 text-sm focus:outline-none transition-all duration-200 ease-in-out rounded-lg opacity-0 pointer-events-none"
                        autocomplete="off" oninput="filterUser(this.value)">
                    <button type="button" onclick="toggleSearch('searchUser', this)" class="shrink-0 p-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <ul id="userSuggest" class="hidden mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto list-none p-0 w-64"></ul>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[500px]">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider sticky top-0 z-10">
                            <th class="px-4 sm:px-6 py-3">#</th>
                            <th class="px-4 sm:px-6 py-3">Name</th>
                            <th class="px-4 sm:px-6 py-3">Username</th>
                            <th class="px-4 sm:px-6 py-3">Role</th>
                            <th class="px-4 sm:px-6 py-3 hidden sm:table-cell">Dibuat</th>
                            <th class="px-4 sm:px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="divide-y divide-gray-200">
                        @foreach ($users as $u)
                            <tr class="hover:bg-gray-50 {{ $u->id === $user->id ? 'bg-blue-50' : '' }}">
                                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm font-medium text-gray-800 whitespace-nowrap">
                                    {{ $u->name }}
                                    @if ($u->id === $user->id)
                                        <span class="ml-1 text-[10px] sm:text-xs text-blue-600 font-normal">(kamu)</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-600">{{ $u->username }}</td>
                                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm">
                                    @if ($u->role === 'guest')
                                        <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">Guest</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Admin</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-xs sm:text-sm text-gray-500 hidden sm:table-cell">{{ $u->created_at->format('d-m-Y') }}</td>
                                <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">
                                    <button onclick="openEditModal(@js($u->id), @js($u->name), @js($u->username), @js($u->role))"
                                        class="inline-block px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEF3C7;color:#D97706">Edit</button>
                                    @if ($u->id !== $user->id)
                                        <form method="POST" action="/user/{{ $u->id }}" onsubmit="showConfirm('Hapus user {{ $u->name }}?', function(){ this.submit(); }.bind(this)); return false;" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="inline-block px-3 py-1 text-xs font-medium rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#DC2626">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->isEmpty())
                <div class="px-4 sm:px-6 py-8 text-center text-sm text-gray-500">Belum ada user.</div>
            @endif
        </div>
    @endif

    {{-- Floating Tambah --}}
    <button onclick="openCreateModal()"
        style="background:#3B82F6;color:#FFFFFF"
        class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full shadow-lg hover:opacity-80 flex items-center justify-center">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
    </button>

    {{-- Modal Create --}}
    <div id="createModal" class="fixed inset-0 z-50 flex items-center justify-center {{ $errors->any() ? '' : 'hidden' }}">
        <div class="fixed inset-0 bg-black/50" onclick="closeCreateModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-6">Tambah Petugas</h2>
            <form method="POST" action="/user">
                @csrf
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                        <ul class="list-disc list-inside m-0 p-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                        class="w-full px-4 py-2 border {{ $errors->has('name') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" required autocomplete="username"
                        class="w-full px-4 py-2 border {{ $errors->has('username') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required autocomplete="new-password"
                        class="w-full px-4 py-2 border {{ $errors->has('password') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="role" value="admin" {{ old('role', 'admin') === 'admin' ? 'checked' : '' }} class="text-blue-600">
                            <span class="text-sm text-gray-700">Admin</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="role" value="guest" {{ old('role') === 'guest' ? 'checked' : '' }} class="text-blue-600">
                            <span class="text-sm text-gray-700">Guest</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black/50" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-6">Edit User</h2>
            <form method="POST" action="" id="editForm">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" id="editName" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="editUsername" required autocomplete="username"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <hr class="my-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password" autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="role" value="admin" id="editRoleAdmin" class="text-blue-600">
                            <span class="text-sm text-gray-700">Admin</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="role" value="guest" id="editRoleGuest" class="text-blue-600">
                            <span class="text-sm text-gray-700">Guest</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 rounded-lg hover:opacity-80 text-sm font-medium" style="background:#DCFCE7;color:#16A34A">Simpan</button>
                </div>
            </form>
        </div>
    </div>

<script>
var userData = {!! json_encode($users->map(fn($u) => ['name' => $u->name, 'username' => $u->username]), JSON_HEX_TAG) !!};
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}
function openEditModal(id, name, username, role) {
    document.getElementById('editForm').action = '/user/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editUsername').value = username;
    document.getElementById('editRoleAdmin').checked = role === 'admin';
    document.getElementById('editRoleGuest').checked = role === 'guest';
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
function filterUser(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#userTableBody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
    var list = document.getElementById('userSuggest');
    list.innerHTML = '';
    if (!q) { list.classList.add('hidden'); return; }
    var matches = userData.filter(function(u) {
        return u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q);
    }).slice(0, 8);
    if (matches.length === 0) { list.classList.add('hidden'); return; }
    matches.forEach(function(u) {
        var li = document.createElement('li');
        li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
        li.innerHTML = '<span class="font-medium text-gray-800">' + u.name + '</span><span class="text-gray-500"> - ' + u.username + '</span>';
        li.addEventListener('mousedown', function(e) {
            e.preventDefault();
            document.getElementById('searchUser').value = u.name;
            list.classList.add('hidden');
            filterUser(u.name);
        });
        list.appendChild(li);
    });
    var btn = document.getElementById('searchUser').parentElement.querySelector('button');
    positionSuggest(btn, 'userSuggest');
    list.classList.remove('hidden');
}

document.getElementById('searchUser').addEventListener('blur', function() {
    setTimeout(function() { document.getElementById('userSuggest').classList.add('hidden'); }, 200);
});
</script>
@endsection
