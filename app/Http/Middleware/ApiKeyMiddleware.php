<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Validasi API key dari header X-API-KEY.
     * Digunakan untuk melindungi endpoint POST sensor data
     * agar hanya perangkat IoT yang memiliki key yang bisa kirim data.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        $validKey = config('services.sensor.api_key');

        if (empty($validKey) || $apiKey !== $validKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key tidak valid. Sertakan header X-API-KEY yang benar.',
            ], 401);
        }

        return $next($request);
    }
}
