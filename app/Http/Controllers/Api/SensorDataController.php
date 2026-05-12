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

        $message  = "{$emoji} <b>Status Kebun Berubah!</b>\n\n";
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
}