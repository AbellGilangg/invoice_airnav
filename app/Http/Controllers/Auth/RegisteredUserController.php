<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Airport; // Baris ini bisa dihapus atau dibiarkan saja
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // KITA TIDAK PERLU MENGAMBIL DATA BANDARA LAGI
        // $airports = Airport::all();
        return view('auth.register'); // Hapus 'compact('airports')'
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'role' => ['required', 'in:admin,user'],
            // 'airport_id' => ['required', 'exists:airports,id'], // HAPUS VALIDASI INI
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            // 'airport_id' => $request->airport_id, // HAPUS ISIAN INI
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', [], false));
    }
}