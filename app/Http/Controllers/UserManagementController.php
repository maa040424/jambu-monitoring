<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Tampilkan halaman manajemen user.
     */
    public function index()
    {
        $users = User::orderByDesc('last_login_at')->orderBy('name')->get();

        return view('users', compact('users'));
    }

    /**
     * Simpan user baru (petani).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'petani',
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Akun petani berhasil dibuat.');
    }

    /**
     * Hapus user (tidak bisa hapus diri sendiri).
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Akun {$user->name} berhasil dihapus.");
    }

    /**
     * Reset password user ke default (jambu123).
     */
    public function resetPassword(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak bisa mereset password akun sendiri.');
        }

        $defaultPassword = 'jambu123';

        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Password {$user->name} berhasil direset ke default ({$defaultPassword}).");
    }
}
