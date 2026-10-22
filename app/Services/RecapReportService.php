<?php

namespace App\Services;

use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecapReportService
{
    /**
     * Generate rekapitulasi data kondisi lingkungan untuk periode tertentu.
     *
     * @param string $mode 'dummy' atau 'real'
     * @param string|null $startDate Format YYYY-MM-DD
     * @param string|null $endDate Format YYYY-MM-DD
     * @return array
     */
    public function generateRecap(string $mode = 'dummy', ?string $startDate = null, ?string $endDate = null): array
    {
        // 1. Setup Query Dasar
        $query = SensorData::where('source', $mode);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                "{$startDate} 00:00:00",
                "{$endDate} 23:59:59"
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', "{$startDate} 00:00:00");
        } elseif ($endDate) {
            $query->where('created_at', '<=', "{$endDate} 23:59:59");
        }

        // 2. Query Agregasi Utama (SQL Efisien & Inklusif)
        $stats = (clone $query)
            ->selectRaw('
                COUNT(*) as total_count,
                MIN(created_at) as first_record,
                MAX(created_at) as last_record,
                COUNT(soil_moisture) as soil_count,
                MIN(soil_moisture) as soil_min,
                MAX(soil_moisture) as soil_max,
                AVG(soil_moisture) as soil_avg,
                COUNT(temperature) as temp_count,
                MIN(temperature) as temp_min,
                MAX(temperature) as temp_max,
                AVG(temperature) as temp_avg,
                COUNT(humidity) as hum_count,
                MIN(humidity) as hum_min,
                MAX(humidity) as hum_max,
                AVG(humidity) as hum_avg,
                COUNT(light_intensity) as light_count,
                SUM(CASE WHEN light_intensity >= ? THEN 1 ELSE 0 END) as light_terang_count,
                SUM(CASE WHEN light_intensity < ? THEN 1 ELSE 0 END) as light_gelap_count,
                SUM(CASE WHEN soil_moisture < ? THEN 1 ELSE 0 END) as soil_kritis_count,
                SUM(CASE WHEN soil_moisture >= ? AND soil_moisture < ? THEN 1 ELSE 0 END) as soil_perlu_count,
                SUM(CASE WHEN soil_moisture >= ? THEN 1 ELSE 0 END) as soil_normal_count,
                SUM(CASE WHEN temperature > ? THEN 1 ELSE 0 END) as temp_high_count
            ', [
                SensorData::THRESHOLD_LIGHT_BRIGHT,
                SensorData::THRESHOLD_LIGHT_BRIGHT,
                SensorData::THRESHOLD_SOIL_KRITIS,
                SensorData::THRESHOLD_SOIL_KRITIS,
                SensorData::THRESHOLD_SOIL_PERLU,
                SensorData::THRESHOLD_SOIL_PERLU,
                SensorData::THRESHOLD_TEMP_WARNING,
            ])
            ->first();

        $totalRecords = (int) ($stats->total_count ?? 0);

        // Jika tidak ada data pada periode yang dipilih
        if ($totalRecords === 0) {
            return [
                'has_data' => false,
                'mode' => $mode,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_records' => 0,
                    'first_record' => null,
                    'last_record' => null,
                ],
                'sensor_stats' => [],
                'soil_recap' => [
                    'total_valid' => 0,
                    'kritis_count' => 0,
                    'kritis_pct' => 0.0,
                    'perlu_penyiraman_count' => 0,
                    'perlu_penyiraman_pct' => 0.0,
                    'normal_count' => 0,
                    'normal_pct' => 0.0,
                    'last_reading' => null,
                ],
                'temp_recap' => [
                    'threshold' => SensorData::THRESHOLD_TEMP_WARNING,
                    'total_valid' => 0,
                    'high_temp_count' => 0,
                    'high_temp_pct' => 0.0,
                    'max_temp' => null,
                    'max_temp_time' => null,
                ],
                'daily_trends' => [],
                'recommendations' => [
                    'summary' => 'Tidak ada data sensor yang tercatat pada rentang periode ini.',
                    'facts' => [],
                    'actions' => ['Silakan pilih rentang tanggal lain atau pastikan data sensor tersedia di database.'],
                    'disclaimer' => 'Sistem menyajikan data historis riil yang tersimpan di database tanpa interpolasi buatan.',
                ],
            ];
        }

        // 3. Rekap Sensor Statistik
        $soilCount = (int) ($stats->soil_count ?? 0);
        $tempCount = (int) ($stats->temp_count ?? 0);
        $humCount  = (int) ($stats->hum_count ?? 0);
        $lightCount = (int) ($stats->light_count ?? 0);

        $sensorStats = [
            'soil_moisture' => [
                'name' => 'Kelembapan Tanah',
                'unit' => '%',
                'valid_count' => $soilCount,
                'min' => $stats->soil_min !== null ? round((float) $stats->soil_min, 1) : null,
                'max' => $stats->soil_max !== null ? round((float) $stats->soil_max, 1) : null,
                'avg' => $stats->soil_avg !== null ? round((float) $stats->soil_avg, 1) : null,
            ],
            'temperature' => [
                'name' => 'Suhu Udara',
                'unit' => '°C',
                'valid_count' => $tempCount,
                'min' => $stats->temp_min !== null ? round((float) $stats->temp_min, 1) : null,
                'max' => $stats->temp_max !== null ? round((float) $stats->temp_max, 1) : null,
                'avg' => $stats->temp_avg !== null ? round((float) $stats->temp_avg, 1) : null,
            ],
            'humidity' => [
                'name' => 'Kelembapan Udara',
                'unit' => '%',
                'valid_count' => $humCount,
                'min' => $stats->hum_min !== null ? round((float) $stats->hum_min, 1) : null,
                'max' => $stats->hum_max !== null ? round((float) $stats->hum_max, 1) : null,
                'avg' => $stats->hum_avg !== null ? round((float) $stats->hum_avg, 1) : null,
            ],
            'light' => [
                'name' => 'Kondisi Cahaya (LM393)',
                'unit' => 'Status Diskrit',
                'valid_count' => $lightCount,
                'is_digital' => true,
                'terang_count' => (int) ($stats->light_terang_count ?? 0),
                'terang_pct' => $lightCount > 0 ? round(((int) ($stats->light_terang_count ?? 0) / $lightCount) * 100, 1) : 0.0,
                'gelap_count' => (int) ($stats->light_gelap_count ?? 0),
                'gelap_pct' => $lightCount > 0 ? round(((int) ($stats->light_gelap_count ?? 0) / $lightCount) * 100, 1) : 0.0,
            ],
        ];

        // 4. Rekap Status Kelembapan Tanah
        $kritisCount = (int) ($stats->soil_kritis_count ?? 0);
        $perluCount  = (int) ($stats->soil_perlu_count ?? 0);
        $normalCount = (int) ($stats->soil_normal_count ?? 0);

        // Ambil data pembacaan terakhir
        $lastRecord = (clone $query)->orderByDesc('created_at')->first();

        $soilRecap = [
            'total_valid' => $soilCount,
            'kritis_count' => $kritisCount,
            'kritis_pct' => $soilCount > 0 ? round(($kritisCount / $soilCount) * 100, 1) : 0.0,
            'perlu_penyiraman_count' => $perluCount,
            'perlu_penyiraman_pct' => $soilCount > 0 ? round(($perluCount / $soilCount) * 100, 1) : 0.0,
            'normal_count' => $normalCount,
            'normal_pct' => $soilCount > 0 ? round(($normalCount / $soilCount) * 100, 1) : 0.0,
            'last_reading_time' => $lastRecord ? Carbon::parse($lastRecord->created_at)->format('d/m/Y H:i') : null,
            'last_soil_moisture' => $lastRecord ? round((float) $lastRecord->soil_moisture, 1) : null,
            'last_status' => $lastRecord ? $lastRecord->status : null,
        ];

        // 5. Rekap Peringatan Suhu (> 35°C)
        $highTempCount = (int) ($stats->temp_high_count ?? 0);
        $maxTempRecord = (clone $query)
            ->whereNotNull('temperature')
            ->orderByDesc('temperature')
            ->first(['temperature', 'created_at']);

        $tempRecap = [
            'threshold' => SensorData::THRESHOLD_TEMP_WARNING,
            'total_valid' => $tempCount,
            'high_temp_count' => $highTempCount,
            'high_temp_pct' => $tempCount > 0 ? round(($highTempCount / $tempCount) * 100, 1) : 0.0,
            'max_temp' => $maxTempRecord ? round((float) $maxTempRecord->temperature, 1) : null,
            'max_temp_time' => $maxTempRecord ? Carbon::parse($maxTempRecord->created_at)->format('d/m/Y H:i') : null,
        ];

        // 6. Agregasi Tren Harian
        $dailyTrends = (clone $query)
            ->selectRaw('
                DATE(created_at) as trend_date,
                ROUND(AVG(soil_moisture), 1) as soil_avg,
                ROUND(AVG(temperature), 1) as temp_avg,
                ROUND(AVG(humidity), 1) as hum_avg,
                COUNT(*) as count
            ')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->trend_date,
                    'label' => Carbon::parse($row->trend_date)->format('d M'),
                    'soil_avg' => (float) $row->soil_avg,
                    'temp_avg' => (float) $row->temp_avg,
                    'hum_avg' => (float) $row->hum_avg,
                    'count' => (int) $row->count,
                ];
            })
            ->toArray();

        // 7. Interpretasi dan Rekomendasi Keputusan Budidaya (Rule-Based Agronomic Summary)
        $recommendations = $this->generateAgronomicInterpretation(
            $sensorStats,
            $soilRecap,
            $tempRecap,
            $totalRecords,
            $startDate,
            $endDate
        );

        return [
            'has_data' => true,
            'mode' => $mode,
            'period' => [
                'start_date' => $startDate ?: Carbon::parse($stats->first_record)->format('Y-m-d'),
                'end_date' => $endDate ?: Carbon::parse($stats->last_record)->format('Y-m-d'),
                'total_records' => $totalRecords,
                'first_record' => Carbon::parse($stats->first_record)->format('d/m/Y H:i'),
                'last_record' => Carbon::parse($stats->last_record)->format('d/m/Y H:i'),
            ],
            'sensor_stats' => $sensorStats,
            'soil_recap' => $soilRecap,
            'temp_recap' => $tempRecap,
            'daily_trends' => $dailyTrends,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Membentuk ringkasan fakta dan rekomendasi budidaya berbasis aturan sistem secara konservatif.
     */
    private function generateAgronomicInterpretation(
        array $stats,
        array $soil,
        array $temp,
        int $totalRecords,
        ?string $startDate,
        ?string $endDate
    ): array {
        $facts = [];
        $actions = [];

        $soilAvg = $stats['soil_moisture']['avg'];
        $tempAvg = $stats['temperature']['avg'];
        $humAvg  = $stats['humidity']['avg'];

        // Fakta 1: Gambaran umum periode
        $facts[] = "Dianalisis sebanyak {$totalRecords} rekaman data sensor pada periode yang dipilih.";
        $facts[] = "Rata-rata kelembapan tanah tercatat {$soilAvg}%, suhu rata-rata {$tempAvg}°C, dan kelembapan udara {$humAvg}%.";

        // Fakta & Aturan 2: Kelembapan Tanah
        if ($soil['kritis_count'] > 0) {
            $facts[] = "Terdapat {$soil['kritis_count']} pembacaan ({$soil['kritis_pct']}%) dengan status Kritis (<20%).";
            $actions[] = "Terdapat indikasi dehidrasi tanah pada beberapa pembacaan. Disarankan pemeriksaan langsung kondisi fisik tanah di area perakaran dan percepatan jadwal penyiraman pada jam-jam tanah terdeteksi kering.";
        }

        if ($soil['perlu_penyiraman_count'] > 0) {
            $facts[] = "Sebanyak {$soil['perlu_penyiraman_count']} pembacaan ({$soil['perlu_penyiraman_pct']}%) berada pada kategori Perlu Penyiraman (20% - 29.9%).";
            if ($soil['kritis_count'] === 0) {
                $actions[] = "Kelembapan tanah sesekali turun ke batas waspada. Disarankan menjaga keteraturan volume irigasi agar kelembapan tidak tergelincir ke zona kritis.";
            }
        }

        if ($soil['normal_pct'] >= 75.0) {
            $facts[] = "Sebagian besar waktu ({$soil['normal_pct']}%), kelembapan tanah berada pada kondisi Normal (≥30%).";
            $actions[] = "Kondisi kelembapan perakaran tanaman jambu kristal secara umum stabil. Pertahankan pola irigasi saat ini sembari memantau tren cuaca.";
        }

        // Fakta & Aturan 3: Suhu Udara Ekstrem
        if ($temp['high_temp_count'] > 0) {
            $facts[] = "Terdeteksi {$temp['high_temp_count']} kali pembacaan suhu udara tinggi (>35°C), dengan suhu puncak mencapai {$temp['max_temp']}°C pada {$temp['max_temp_time']}.";
            $actions[] = "Suhu udara di atas 35°C mempercepat laju evapotranspirasi tanah dan transpirasi daun. Pertimbangkan penambahan mulsa organik di sekeliling pangkal batang untuk mengurangi penguapan air tanah di siang hari.";
        } else {
            $facts[] = "Suhu udara tidak pernah melampaui ambang peringatan 35°C (suhu tertinggi {$temp['max_temp']}°C).";
        }

        // Catatan cahaya diskrit
        $terangPct = $stats['light']['terang_pct'];
        $facts[] = "Kondisi pencahayaan sensor LM393 mencatat {$terangPct}% pembacaan dalam kondisi Terang.";

        $summary = "Berdasarkan {$totalRecords} rekaman data, kondisi kebun jambu kristal berada dalam status kelembapan rata-rata {$soilAvg}% dengan {$soil['normal_pct']}% pembacaan normal, {$soil['perlu_penyiraman_pct']}% perlu penyiraman, dan {$soil['kritis_pct']}% kritis.";

        $disclaimer = "Catatan: Rekapitulasi ini merupakan ringkasan statistik dari pembacaan sensor berkala pada testbed (bukan durasi jam kontinu). Sistem tidak mencatat data log aktuator/tindakan fisik penyiraman.";

        return [
            'summary' => $summary,
            'facts' => $facts,
            'actions' => $actions,
            'disclaimer' => $disclaimer,
        ];
    }
}
