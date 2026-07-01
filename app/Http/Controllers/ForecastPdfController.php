<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForecastPdfController extends Controller
{
    /**
     * Proxy request ke Flask ML service untuk generate PDF laporan ARIMA.
     * PDF berisi grafik aktual vs prediksi dan tabel hasil prediksi.
     */
    public function export(Request $request)
    {
        $source = $request->session()->get('data_mode', 'dummy');
        $mlUrl = config('services.ml.url', 'http://127.0.0.1:5001');

        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)  // PDF generation can take a while
                ->get("{$mlUrl}/predict/pdf", [
                    'source' => $source,
                    'steps' => 24,
                    'limit' => 1000,
                ]);

            if (!$response->successful()) {
                $body = $response->json();
                $message = $body['message'] ?? 'Gagal generate PDF dari ML service (HTTP ' . $response->status() . ').';
                Log::error('PDF Export Error: ' . $message);
                return redirect()->route('forecast')->with('error', $message);
            }

            $filename = "prediksi_arima_{$source}_" . now()->format('Ymd_Hi') . '.pdf';

            return response($response->body())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PDF Export Connection Error: ' . $e->getMessage());
            return redirect()->route('forecast')
                ->with('error', 'Tidak dapat terhubung ke ML Service. Pastikan Flask (python ml/app.py) sedang berjalan di port 5001.');

        } catch (\Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->route('forecast')
                ->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }
}

