<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
    /**
     * Simpan data sensor baru.
     *
     * - Hitung status berdasarkan soil_moisture
     * - Simpan ke database dengan source (default: real)
     * - Kirim notifikasi Telegram jika status berubah
     */
    public function store(Request $request)
    {
        // ── 1. Validasi ──────────────────────────────────────
        $validated = $request->validate([
            'soil_moisture'   => 'required|numeric',
            'temperature'     => 'required|numeric',
            'humidity'        => 'required|numeric',
            'light_intensity' => 'required|numeric',
            'source'          => 'sometimes|in:dummy,real',
        ]);

        // ── 2. Hitung status berdasarkan soil_moisture ───────
        $status = $this->determineStatus($validated['soil_moisture']);

        // ── 3. Source: default 'real' (Arduino tidak perlu kirim param ini)
        $source = $validated['source'] ?? 'real';

        // ── 4. Simpan data + status + source ke database ─────
        $data = SensorData::create(array_merge($validated, [
            'status' => $status,
            'source' => $source,
        ]));

        // ── 5. Cek status sebelumnya & kirim Telegram ────────
        //       Hanya cek dari data dengan source yang sama
        $previousRecord = SensorData::where('id', '!=', $data->id)
            ->where('source', $source)
            ->latest()
            ->first();

        $previousStatus = $previousRecord?->status;

        if ($previousStatus !== $status) {
            $this->sendTelegramNotification($data, $status, $previousStatus);
        }

        // ── 6. Response ──────────────────────────────────────
        return response()->json([
            'status'  => 'success',
            'message' => 'Data stored',
            'data'    => $data,
        ], 201);
    }

    /**
     * Simulasikan data sensor masuk via web (menggunakan auth session).
     */
    public function simulate(Request $request)
    {
        // Menggunakan logika penyimpanan yang sama
        return $this->store($request);
    }

    /**
     * Ambil data sensor berdasarkan source (terbaru duluan).
     *
     * Query params:
     *   - source: 'dummy' atau 'real' (opsional, tanpa filter = semua)
     */
    public function index(Request $request)
    {
        $query = SensorData::latest();

        if ($request->has('source') && in_array($request->source, ['dummy', 'real'])) {
            $query->where('source', $request->source);
        }

        return response()->json($query->get());
    }

    /**
     * Hapus semua data berdasarkan source.
     */
    public function clear(Request $request)
    {
        $request->validate([
            'source'    => 'required|in:dummy,real',
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = SensorData::where('source', $request->source);

        // Filter by date range if provided
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [
                "{$request->from_date} 00:00:00",
                "{$request->to_date} 23:59:59",
            ]);
        } elseif ($request->from_date) {
            $query->where('created_at', '>=', "{$request->from_date} 00:00:00");
        } elseif ($request->to_date) {
            $query->where('created_at', '<=', "{$request->to_date} 23:59:59");
        }

        $count = $query->count();
        $query->delete();

        $label = $request->source === 'dummy' ? 'dummy' : 'real';
        $dateInfo = '';
        if ($request->from_date || $request->to_date) {
            $dateInfo = ' (periode: ' . ($request->from_date ?? '...') . ' s/d ' . ($request->to_date ?? '...') . ')';
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Berhasil menghapus {$count} data {$label}{$dateInfo}.",
            'deleted' => $count,
        ]);
    }

    /**
     * Hapus data sensor berdasarkan array ID.
     */
    public function destroySelected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:sensor_data,id',
        ]);

        $count = SensorData::whereIn('id', $request->ids)->count();
        SensorData::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "Berhasil menghapus {$count} record.",
            'deleted' => $count,
        ]);
    }

    /**
     * Tentukan status berdasarkan nilai soil_moisture.
     */
    private function determineStatus(float $soilMoisture): string
    {
        if ($soilMoisture < 20) {
            return 'Kritis';
        }

        if ($soilMoisture < 30) {
            return 'Perlu Penyiraman';
        }

        return 'Normal';
    }

    /**
     * Kirim notifikasi Telegram saat status berubah.
     */
    private function sendTelegramNotification(SensorData $data, string $newStatus, ?string $oldStatus): void
    {
        $emoji = match ($newStatus) {
            'Kritis'            => '🔴',
            'Perlu Penyiraman'  => '🟡',
            default             => '🟢',
        };

        $prefix = $data->source === 'dummy' ? "⚠️ <b>[SIMULASI/DUMMY]</b>\n" : "";

        $message  = "{$prefix}{$emoji} <b>Status Kebun Berubah!</b>\n\n";
        $message .= "Status: <b>{$newStatus}</b>\n";
        $message .= "Sebelumnya: " . ($oldStatus ?? '—') . "\n\n";
        $message .= "📊 <b>Data Sensor:</b>\n";
        $message .= "💧 Kelembaban Tanah: {$data->soil_moisture}%\n";
        $message .= "🌡️ Suhu: {$data->temperature}°C\n";
        $message .= "💨 Kelembaban Udara: {$data->humidity}%\n";
        $message .= "☀️ Intensitas Cahaya: {$data->light_intensity} lux\n\n";
        $message .= "🕐 Waktu: {$data->created_at}";

        app(TelegramService::class)->sendMessage($message);
    }

    /**
     * Generate bulk historical dummy data based on user input.
     */
    public function generateDummy(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:90',
        ]);

        $days = (int) $request->days;

        // Clear existing dummy data first
        SensorData::where('source', 'dummy')->delete();

        $records = [];
        $now = now();
        $start = $now->copy()->subDays($days);
        $intervalMinutes = 30; // 30 mins interval is perfect for ARIMA
        $totalPoints = ($days * 24 * 60) / $intervalMinutes;

        $soil = 55.0; // initial soil moisture

        for ($i = 0; $i < $totalPoints; $i++) {
            $t = $start->copy()->addMinutes($intervalMinutes * $i);
            $hour = (float) $t->format('H') + ((float) $t->format('i') / 60.0);

            // ── Temperature: 22-38°C, peaks at ~14:00 ──
            $tempBase = 28.0 + 7.0 * sin(pi() * ($hour - 6.0) / 12.0);
            $temp = $tempBase + $this->randomGauss(0, 1.5);
            $temp = max(20.0, min(40.0, $temp));

            // ── Humidity: inverse of temp, 50-90% ──
            $humBase = 75.0 - 15.0 * sin(pi() * ($hour - 6.0) / 12.0);
            $hum = $humBase + $this->randomGauss(0, 3.0);
            $hum = max(45.0, min(95.0, $hum));

            // ── Light: 0 at night, peaks ~1200 at noon ──
            if ($hour >= 6.0 && $hour <= 18.0) {
                $lightBase = 800.0 * sin(pi() * ($hour - 6.0) / 12.0);
                $light = $lightBase + $this->randomGauss(0, 80.0);
                if ((mt_rand() / mt_getrandmax()) < 0.15) {
                    $light *= 0.3;
                }
            } else {
                $light = mt_rand(0, 50) / 10.0;
            }
            $light = max(0.0, min(2000.0, $light));

            // ── Soil Moisture: decays, watered 2x ──
            if ($hour >= 8.0 && $hour <= 16.0) {
                $decay = mt_rand(15, 35) / 100.0;
            } else {
                $decay = mt_rand(2, 8) / 100.0;
            }
            $soil -= $decay;

            // Watering at 07:00 and 17:00
            if (abs($hour - 7.0) < 0.17 && $t->format('i') == '00') {
                $soil = min(80.0, $soil + (mt_rand(250, 400) / 10.0));
            }
            if (abs($hour - 17.0) < 0.17 && $t->format('i') == '00') {
                $soil = min(75.0, $soil + (mt_rand(200, 350) / 10.0));
            }

            // Rain event (random afternoon)
            if ($hour >= 13.0 && $hour <= 16.0 && ((mt_rand() / mt_getrandmax()) < 0.05)) {
                $soil = min(85.0, $soil + (mt_rand(150, 300) / 10.0));
                $hum = min(95.0, $hum + 10.0);
            }

            $soil = max(5.0, min(90.0, $soil));
            $status = $this->determineStatus($soil);

            $records[] = [
                'soil_moisture'   => round($soil, 1),
                'temperature'     => round($temp, 1),
                'humidity'        => round($hum, 1),
                'light_intensity' => round($light, 1),
                'status'          => $status,
                'source'          => 'dummy',
                'created_at'      => $t->toDateTimeString(),
                'updated_at'      => $t->toDateTimeString(),
            ];
        }

        // Insert records in chunks
        $chunks = array_chunk($records, 500);
        foreach ($chunks as $chunk) {
            SensorData::insert($chunk);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil menghasilkan " . count($records) . " data dummy untuk " . $days . " hari terakhir.",
            'count' => count($records)
        ]);
    }

    /**
     * Helper to generate normally distributed random variables.
     */
    private function randomGauss(float $mean, float $stdDev): float
    {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        if ($x == 0) $x = 0.00001;
        return $mean + $stdDev * sqrt(-2.0 * log($x)) * cos(2.0 * pi() * $y);
    }
}