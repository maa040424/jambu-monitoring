<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    /**
     * Download backup data real ke file SQL INSERT.
     * Hanya admin yang bisa akses.
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $from = $validated['from_date'] ?? null;
        $to   = $validated['to_date']   ?? null;

        $query = SensorData::where('source', 'real')->orderBy('created_at', 'asc');

        if ($from && $to) {
            $query->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);
        } elseif ($from) {
            $query->where('created_at', '>=', "{$from} 00:00:00");
        } elseif ($to) {
            $query->where('created_at', '<=', "{$to} 23:59:59");
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('backup_error', 'Tidak ada data real untuk di-backup.');
        }

        $count    = $data->count();
        $now      = now()->format('Y-m-d_H-i-s');
        $filename = "backup_sensor_real_{$now}.sql";

        $sql  = "-- ============================================================\n";
        $sql .= "-- Backup Data Sensor Real - Jambu Monitoring\n";
        $sql .= "-- Dibuat: " . now()->format('d/m/Y H:i:s') . " WIB\n";
        $sql .= "-- Total record: {$count}\n";
        if ($from || $to) {
            $sql .= "-- Periode: " . ($from ?? '...') . " s/d " . ($to ?? '...') . "\n";
        }
        $sql .= "-- ============================================================\n\n";
        $sql .= "-- CARA RESTORE:\n";
        $sql .= "-- 1. Buka phpMyAdmin atau MySQL client\n";
        $sql .= "-- 2. Pilih database: db_jambu_monitoring\n";
        $sql .= "-- 3. Import file ini\n";
        $sql .= "-- ATAU jalankan melalui fitur Restore di halaman Dashboard\n\n";
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $sql .= "INSERT INTO `sensor_data` (`id`, `soil_moisture`, `temperature`, `humidity`, `light_intensity`, `status`, `source`, `recorded_at`, `created_at`, `updated_at`) VALUES\n";

        $rows = [];
        foreach ($data as $row) {
            $id            = (int) $row->id;
            $soil          = (float) $row->soil_moisture;
            $temp          = (float) $row->temperature;
            $hum           = (float) $row->humidity;
            $light         = (float) $row->light_intensity;
            $status        = addslashes($row->status ?? '');
            $source        = addslashes($row->source ?? 'real');
            $recorded_at   = $row->recorded_at ?? $row->created_at;
            $created_at    = $row->created_at;
            $updated_at    = $row->updated_at;

            $rows[] = "({$id}, {$soil}, {$temp}, {$hum}, {$light}, '{$status}', '{$source}', '{$recorded_at}', '{$created_at}', '{$updated_at}')";
        }

        $sql .= implode(",\n", $rows) . ";\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sql .= "-- ===== END OF BACKUP =====\n";

        return response($sql, 200, [
            'Content-Type'        => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Restore data real dari file SQL backup.
     * Menambahkan data tanpa menghapus data yang sudah ada (merge).
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|extensions:sql,txt|max:51200', // max 50 MB
        ]);

        $file    = $request->file('backup_file');
        $content = file_get_contents($file->getRealPath());

        // Keamanan: pastikan isinya file backup yang valid
        if (strpos($content, '-- Backup Data Sensor Real - Jambu Monitoring') === false) {
            return redirect()->back()->with('backup_error', 'File bukan backup yang valid. Pastikan file berasal dari fitur backup ini.');
        }

        // Coba deteksi format 10 kolom terlebih dahulu (termasuk recorded_at)
        preg_match_all(
            '/\((\d+),\s*([\d.]+),\s*([\d.]+),\s*([\d.]+),\s*([\d.]+),\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']+)\',\s*\'([^\']+)\'\)/',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $isTenColumns = !empty($matches);

        // Jika tidak cocok, coba format 9 kolom (tanpa recorded_at)
        if (!$isTenColumns) {
            preg_match_all(
                '/\((\d+),\s*([\d.]+),\s*([\d.]+),\s*([\d.]+),\s*([\d.]+),\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']+)\',\s*\'([^\']+)\'\)/',
                $content,
                $matches,
                PREG_SET_ORDER
            );
        }

        if (empty($matches)) {
            return redirect()->back()->with('backup_error', 'Tidak ada data valid yang ditemukan dalam file backup.');
        }

        $imported = 0;
        $skipped  = 0;

        foreach ($matches as $m) {
            // Cek apakah ID sudah ada — skip jika ada, untuk menghindari duplikasi
            $exists = SensorData::where('id', (int) $m[1])->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            if ($isTenColumns) {
                SensorData::insert([
                    'id'              => (int)    $m[1],
                    'soil_moisture'   => (float)  $m[2],
                    'temperature'     => (float)  $m[3],
                    'humidity'        => (float)  $m[4],
                    'light_intensity' => (float)  $m[5],
                    'status'          =>           $m[6],
                    'source'          =>           $m[7],
                    'recorded_at'     =>           $m[8],
                    'created_at'      =>           $m[9],
                    'updated_at'      =>           $m[10],
                ]);
            } else {
                SensorData::insert([
                    'id'              => (int)    $m[1],
                    'soil_moisture'   => (float)  $m[2],
                    'temperature'     => (float)  $m[3],
                    'humidity'        => (float)  $m[4],
                    'light_intensity' => (float)  $m[5],
                    'status'          =>           $m[6],
                    'source'          =>           $m[7],
                    'recorded_at'     =>           $m[8], // fallback ke created_at jika 9 kolom
                    'created_at'      =>           $m[8],
                    'updated_at'      =>           $m[9],
                ]);
            }
            $imported++;
        }

        $msg = "Restore berhasil: {$imported} record diimpor";
        if ($skipped > 0) {
            $msg .= ", {$skipped} record dilewati (ID sudah ada)";
        }
        $msg .= ".";

        return redirect()->back()->with('backup_success', $msg);
    }

    /**
     * Info ringkasan backup (jumlah data real, tanggal terlama & terbaru).
     * Digunakan oleh dashboard untuk menampilkan status.
     */
    public function info()
    {
        try {
            $count  = SensorData::where('source', 'real')->count();
            $oldest = SensorData::where('source', 'real')->oldest()->value('created_at');
            $newest = SensorData::where('source', 'real')->latest()->value('created_at');

            return response()->json([
                'count'  => $count,
                'oldest' => $oldest,
                'newest' => $newest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'Gagal mengambil info data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
