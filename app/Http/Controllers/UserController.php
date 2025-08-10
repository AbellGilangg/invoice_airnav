<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua user.
     */
    public function index()
    {
        // Ambil semua user kecuali master itu sendiri, urutkan berdasarkan nama
        $users = User::where('role', '!=', 'master')->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan form untuk mengedit role user.
     */
    public function edit(User $user)
    {
        // Master tidak bisa mengedit dirinya sendiri
        if ($user->role === 'master') {
            abort(403, 'Akun master tidak dapat diubah.');
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Mengupdate role user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Role akun berhasil diperbarui.');
    }
}