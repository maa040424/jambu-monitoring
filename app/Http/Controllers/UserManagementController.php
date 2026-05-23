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
        $currentUser = auth()->user();
        if ($currentUser->role === 'superadmin') {
            // Super Admin can manage everyone
            $users = User::orderByDesc('last_login_at')->orderBy('name')->get();
        } else {
            // Admin can only manage petani (ordinary users)
            $users = User::where('role', 'petani')->orderByDesc('last_login_at')->orderBy('name')->get();
        }

        return view('users', compact('users'));
    }

    /**
     * Simpan user baru (petani/admin).
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();
        
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // If superadmin, validate the role input
        if ($currentUser->role === 'superadmin') {
            $rules['role'] = ['required', 'string', 'in:admin,petani'];
        }

        $request->validate($rules);

        // Determine role to save
        $role = 'petani';
        if ($currentUser->role === 'superadmin' && $request->has('role')) {
            $role = $request->role;
        }

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $role,
        ]);

        $roleText = $role === 'admin' ? 'Admin' : 'Petani';

        return redirect()->route('users.index')
            ->with('success', "Akun {$roleText} berhasil dibuat.");
    }

    /**
     * Hapus user (tidak bisa hapus diri sendiri).
     */
    public function destroy(User $user)
    {
        $currentUser = auth()->user();

        if ($user->id === $currentUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // Prevent normal admin from deleting admins/superadmins
        if ($currentUser->role === 'admin' && $user->role !== 'petani') {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak memiliki wewenang untuk menghapus akun ini.');
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
        $currentUser = auth()->user();

        if ($user->id === $currentUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak bisa mereset password akun sendiri.');
        }

        // Prevent normal admin from resetting passwords of admins/superadmins
        if ($currentUser->role === 'admin' && $user->role !== 'petani') {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak memiliki wewenang untuk mereset password akun ini.');
        }

        $defaultPassword = 'jambu123';

        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Password {$user->name} berhasil direset ke default ({$defaultPassword}).");
    }
}
