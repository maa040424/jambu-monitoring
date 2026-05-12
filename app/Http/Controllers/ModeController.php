<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ModeController extends Controller
{
    /**
     * Tampilkan halaman pemilihan mode data.
     * Ditampilkan setelah login berhasil.
     */
    public function showSelect()
    {
        // Jika sudah punya mode di session, tampilkan tetap halaman select
        // agar user bisa ganti mode kapan saja
        return view('mode-select');
    }

    /**
     * Simpan pilihan mode ke session, redirect ke dashboard.
     */
    public function setMode(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:dummy,real',
        ]);

        $request->session()->put('data_mode', $request->mode);

        return redirect()->route('dashboard');
    }

    /**
     * Switch mode (untuk admin via AJAX dari dashboard).
     */
    public function switchMode(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:dummy,real',
        ]);

        $request->session()->put('data_mode', $request->mode);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'mode' => $request->mode,
            ]);
        }

        return redirect()->back();
    }
}
