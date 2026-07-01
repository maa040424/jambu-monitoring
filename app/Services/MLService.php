<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ml.url', 'http://127.0.0.1:5001');
    }

    /**
     * Ambil prediksi ARIMA dari service ML Python.
     *
     * @param string $source  Mode data ('dummy' atau 'real')
     * @param int    $hours   Jumlah jam prediksi ke depan
     * @param int    $limit   Jumlah data historis untuk training
     * @return array|null
     */
    public function getForecast(string $source = 'dummy', int $hours = 4, int $limit = 1000): ?array
    {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->get("{$this->baseUrl}/predict", [
                    'source' => $source,
                    'hours'  => $hours,
                    'limit'  => $limit,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('MLService: Prediksi gagal.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('MLService: Exception saat request prediksi.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
