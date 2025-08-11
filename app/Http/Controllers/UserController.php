<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Airport; // Tambahkan ini


class UserController extends Controller
{
    // Middleware bisa ditambahkan di sini juga
    // public function __construct()
    // {
    //     $this->middleware('can:manage-users');
    // }

    /**
     * Menampilkan daftar semua user.
     */
    public function index()
    {
        // Ambil semua user kecuali master itu sendiri, urutkan berdasarkan nama
        $users = User::where('role', '!=', 'master')->with('airport')->orderBy('name')->get();
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

        $airports = Airport::orderBy('name')->get();

        return view('users.edit', compact('user', 'airports'));
    }

    /**
     * Mengupdate role user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,user',
            'airport_id' => 'required|exists:airports,id', // Validasi airport
        ]);

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Role dan bandara akun berhasil diperbarui.');
    }
}