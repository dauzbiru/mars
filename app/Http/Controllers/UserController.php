<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'user');

        return view('user.index', [
            'user' => Auth::user(),
            'users' => User::when(!Auth::user()->isSuperAdmin(), fn ($q) => $q->where('role', '!=', 'superadmin'))->latest()->get(),
            'tab' => $tab,
        ]);
    }

    public function create()
    {
        return view('user.create');
    }

    public function store(Request $request)
    {
        $allowedRoles = Auth::user()->isSuperAdmin() ? ['admin', 'guest', 'superadmin'] : ['admin', 'guest'];

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        return redirect('/user')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        if (!Auth::user()->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403);
        }

        return view('user.edit', ['user' => $user]);
    }

    public function updateUser(Request $request, User $user)
    {
        if (!Auth::user()->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403);
        }

        $allowedRoles = Auth::user()->isSuperAdmin() ? ['admin', 'guest', 'superadmin'] : ['admin', 'guest'];

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
        ]);

        $user->role = $request->role;
        $user->save();

        if ($request->filled('password')) {
            $user->update(['password' => $request->password]);
        }

        return redirect('/user')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('warning', 'Tidak bisa menghapus akun sendiri.');
        }

        if (!Auth::user()->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403);
        }

        $user->delete();
        return redirect('/user')->with('success', 'User berhasil dihapus.');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users,username,' . $user->id,
            'current_password' => 'required_with:password|current_password',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => $request->password]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
