<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard.
     * Mode data dibaca dari session (dipilih di halaman mode-select).
     */
    public function index(Request $request)
    {
        // Jika belum pilih mode, redirect ke mode selector
        if (!$request->session()->has('data_mode')) {
            return redirect()->route('mode.select');
        }

        $mode = $request->session()->get('data_mode', 'dummy');

        return view('dashboard', compact('mode'));
    }
}
