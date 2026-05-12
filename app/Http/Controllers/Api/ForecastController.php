<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MLService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    protected MLService $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Get prediction forecast.
     */
    public function index(Request $request)
    {
        $source = $request->query('source', 'dummy');
        $steps = $request->query('steps', 24);
        $limit = $request->query('limit', 500);

        $forecastData = $this->mlService->getForecast($source, (int) $steps, (int) $limit);

        if (!$forecastData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data prediksi. Pastikan Flask ML Service sudah berjalan (python ml/app.py) di port 5001.'
            ], 500);
        }

        return response()->json($forecastData);
    }
}
