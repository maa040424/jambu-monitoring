<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ForecastController extends Controller
{
    /**
     * Tampilkan halaman prediksi ARIMA.
     * Mode data dibaca dari session.
     */
    public function index(Request $request)
    {
        if (!$request->session()->has('data_mode')) {
            return redirect()->route('mode.select');
        }

        $mode = $request->session()->get('data_mode', 'dummy');

        return view('forecast', compact('mode'));
    }
}
