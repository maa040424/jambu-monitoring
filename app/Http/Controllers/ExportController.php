<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Export data sensor ke CSV.
     *
     * Query params:
     *   - from_date (nullable, format date)
     *   - to_date   (nullable, format date, >= from_date)
     *   - mode      (nullable, 'dummy' atau 'real')
     *
     * Jika tanggal tidak diisi, export SEMUA data.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
            'mode'      => 'nullable|in:dummy,real',
        ]);

        $from = $validated['from_date'] ?? null;
        $to   = $validated['to_date']   ?? null;
        $mode = $validated['mode'] ?? null;

        $query = SensorData::orderBy('created_at', 'asc');

        // Filter tanggal hanya jika user mengisinya
        if ($from && $to) {
            $query->whereBetween('created_at', [
                "{$from} 00:00:00",
                "{$to} 23:59:59",
            ]);
        } elseif ($from) {
            $query->where('created_at', '>=', "{$from} 00:00:00");
        } elseif ($to) {
            $query->where('created_at', '<=', "{$to} 23:59:59");
        }

        // Filter mode
        if ($mode && in_array($mode, ['dummy', 'real'])) {
            $query->where('source', $mode);
        }

        $data = $query->get();

        $filename = $mode ? "sensor_data_{$mode}.csv" : 'sensor_data.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 support
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Waktu',
                'Kelembapan Tanah (%)',
                'Suhu (°C)',
                'Kelembapan Udara (%)',
                'Intensitas Cahaya (lux)',
                'Status',
                'Source'
            ], ';');

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->created_at,
                    $row->soil_moisture,
                    $row->temperature,
                    $row->humidity,
                    $row->light_intensity,
                    $row->status,
                    $row->source
                ], ';');
            }

            fclose($handle);
        }, $filename, $headers);
    }
}
