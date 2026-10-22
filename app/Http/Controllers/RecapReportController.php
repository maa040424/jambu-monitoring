<?php

namespace App\Http\Controllers;

use App\Services\RecapReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RecapReportController extends Controller
{
    protected RecapReportService $recapService;

    public function __construct(RecapReportService $recapService)
    {
        $this->recapService = $recapService;
    }

    /**
     * Tampilkan halaman Laporan Rekap Kondisi Lingkungan.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'mode'       => 'nullable|in:dummy,real',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh mendahului tanggal mulai.',
        ]);

        $mode = $validated['mode'] ?? $request->session()->get('data_mode', 'dummy');
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $recap = $this->recapService->generateRecap($mode, $startDate, $endDate);

        return view('recap.index', compact('recap', 'mode', 'startDate', 'endDate'));
    }

    /**
     * Ekspor Laporan Rekap dalam format CSV Hasil Agregasi (Bukan Data Raw).
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'mode'       => 'nullable|in:dummy,real',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh mendahului tanggal mulai.',
        ]);

        $mode = $validated['mode'] ?? $request->session()->get('data_mode', 'dummy');
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $recap = $this->recapService->generateRecap($mode, $startDate, $endDate);

        $filename = "rekap_kondisi_lingkungan_{$mode}_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($recap, $mode) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header Dokumen
            fputcsv($handle, ['LAPORAN REKAPITULASI KONDISI LINGKUNGAN KEBUN JAMBU KRISTAL'], ';');
            fputcsv($handle, ['Mode Data', $mode === 'real' ? 'Real (Sensor IoT ESP32)' : 'Dummy (Pengujian Simulasi)'], ';');
            fputcsv($handle, ['Periode Laporan', ($recap['period']['start_date'] ?? '-') . ' s/d ' . ($recap['period']['end_date'] ?? '-')], ';');
            fputcsv($handle, ['Waktu Dokumen Dibuat', now()->format('d/m/Y H:i:s') . ' WITA'], ';');
            fputcsv($handle, ['Total Data Dianalisis', $recap['period']['total_records'] . ' record'], ';');
            fputcsv($handle, [], ';');

            if (!$recap['has_data']) {
                fputcsv($handle, ['STATUS', 'Tidak ada data sensor pada periode ini'], ';');
                fclose($handle);
                return;
            }

            // BAGIAN 1: RINGKASAN STATISTIK PARAMETER
            fputcsv($handle, ['=== 1. RINGKASAN STATISTIK SENSOR ==='], ';');
            fputcsv($handle, ['Parameter Sensor', 'Satuan', 'Data Valid', 'Minimum', 'Maksimum', 'Rata-rata'], ';');

            foreach (['soil_moisture', 'temperature', 'humidity'] as $sensorKey) {
                $stat = $recap['sensor_stats'][$sensorKey];
                fputcsv($handle, [
                    $stat['name'],
                    $stat['unit'],
                    $stat['valid_count'],
                    $stat['min'],
                    $stat['max'],
                    $stat['avg'],
                ], ';');
            }

            // Sensor Cahaya
            $light = $recap['sensor_stats']['light'];
            fputcsv($handle, [
                $light['name'],
                $light['unit'],
                $light['valid_count'],
                "Terang: {$light['terang_count']} ({$light['terang_pct']}%)",
                "Gelap: {$light['gelap_count']} ({$light['gelap_pct']}%)",
                'Sensor Diskrit LM393',
            ], ';');

            fputcsv($handle, [], ';');

            // BAGIAN 2: REKAP STATUS KELEMBAPAN TANAH
            $soil = $recap['soil_recap'];
            fputcsv($handle, ['=== 2. REKAP STATUS KELEMBAPAN TANAH ==='], ';');
            fputcsv($handle, ['Kategori Status', 'Ambang Batas', 'Jumlah Pembacaan', 'Persentase (%)'], ';');
            fputcsv($handle, ['Kritis', '< 20.0%', $soil['kritis_count'], $soil['kritis_pct'] . '%'], ';');
            fputcsv($handle, ['Perlu Penyiraman', '20.0% s/d < 30.0%', $soil['perlu_penyiraman_count'], $soil['perlu_penyiraman_pct'] . '%'], ';');
            fputcsv($handle, ['Normal', '>= 30.0%', $soil['normal_count'], $soil['normal_pct'] . '%'], ';');
            fputcsv($handle, ['Waktu Pembacaan Terakhir', $soil['last_reading_time'] ?? '-', "Status Terakhir: " . ($soil['last_status'] ?? '-'), ''], ';');

            fputcsv($handle, [], ';');

            // BAGIAN 3: REKAP PERINGATAN SUHU
            $temp = $recap['temp_recap'];
            fputcsv($handle, ['=== 3. REKAP PERINGATAN SUHU UDARA ==='], ';');
            fputcsv($handle, ['Indikator', 'Kriteria', 'Nilai / Jumlah', 'Keterangan'], ';');
            fputcsv($handle, ['Peringatan Suhu Tinggi', '> 35.0°C', $temp['high_temp_count'] . ' kali', $temp['high_temp_pct'] . '% dari total pembacaan'], ';');
            fputcsv($handle, ['Suhu Puncak Tercatat', '-', ($temp['max_temp'] ?? '-') . '°C', 'Waktu: ' . ($temp['max_temp_time'] ?? '-')], ';');

            fputcsv($handle, [], ';');

            // BAGIAN 4: AGREGASI TREN HARIAN
            fputcsv($handle, ['=== 4. TREN RATA-RATA HARIAN ==='], ';');
            fputcsv($handle, ['Tanggal', 'Rata-rata Kel. Tanah (%)', 'Rata-rata Suhu (°C)', 'Rata-rata Kel. Udara (%)', 'Jumlah Data'], ';');
            foreach ($recap['daily_trends'] as $trend) {
                fputcsv($handle, [
                    $trend['date'],
                    $trend['soil_avg'],
                    $trend['temp_avg'],
                    $trend['hum_avg'],
                    $trend['count'],
                ], ';');
            }

            fputcsv($handle, [], ';');

            // BAGIAN 5: RINGKASAN & KEPUTUSAN BUDIDAYA
            $recom = $recap['recommendations'];
            fputcsv($handle, ['=== 5. KESIMPULAN & REKOMENDASI KEPUTUSAN BUDIDAYA ==='], ';');
            fputcsv($handle, ['Ringkasan Umum', $recom['summary']], ';');

            foreach ($recom['facts'] as $idx => $fact) {
                fputcsv($handle, ['Fakta Lapangan #' . ($idx + 1), $fact], ';');
            }

            foreach ($recom['actions'] as $idx => $action) {
                fputcsv($handle, ['Saran Tindakan #' . ($idx + 1), $action], ';');
            }

            fputcsv($handle, ['Batasan Laporan', $recom['disclaimer']], ';');

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Ekspor Laporan Rekap dalam format PDF Resmi.
     */
    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'mode'       => 'nullable|in:dummy,real',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh mendahului tanggal mulai.',
        ]);

        $mode = $validated['mode'] ?? $request->session()->get('data_mode', 'dummy');
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $mlUrl = config('services.ml.url', 'http://127.0.0.1:5001');

        // Coba via ML Service jika aktif
        try {
            $response = Http::connectTimeout(3)
                ->timeout(30)
                ->get("{$mlUrl}/recap/pdf", [
                    'source'     => $mode,
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ]);

            if ($response->successful()) {
                $filename = "laporan_rekap_{$mode}_" . now()->format('Ymd_His') . ".pdf";
                return response($response->body())
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
            }
        } catch (\Exception $e) {
            Log::info("Flask ML Service not reachable for recap PDF, using direct Python script fallback: " . $e->getMessage());
        }

        // Fallback: Generate langsung lewat Python ReportLab lokal
        return $this->generatePdfDirectly($mode, $startDate, $endDate);
    }

    /**
     * Fallback generator PDF menggunakan script Python ReportLab lokal.
     */
    protected function generatePdfDirectly(string $mode, ?string $startDate, ?string $endDate)
    {
        $recap = $this->recapService->generateRecap($mode, $startDate, $endDate);

        $tempJsonPath = storage_path('app/temp_recap_' . uniqid() . '.json');
        $tempPdfPath  = storage_path('app/temp_recap_' . uniqid() . '.pdf');

        file_put_contents($tempJsonPath, json_encode($recap));

        $scriptPath = base_path('ml/generate_recap_pdf.py');
        $pythonBin = 'python';

        $process = Process::run([$pythonBin, $scriptPath, $tempJsonPath, $tempPdfPath]);

        if (file_exists($tempJsonPath)) {
            @unlink($tempJsonPath);
        }

        if (!$process->successful() || !file_exists($tempPdfPath)) {
            Log::error('Direct PDF Generation Error: ' . $process->errorOutput());
            return redirect()->route('recap.index', [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'mode'       => $mode,
            ])->with('error', 'Gagal membuat file PDF laporan: ' . ($process->errorOutput() ?: 'Script Python tidak merespons.'));
        }

        $filename = "laporan_rekap_{$mode}_" . now()->format('Ymd_His') . ".pdf";

        return response()->download($tempPdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }
}
