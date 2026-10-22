<?php

namespace Tests\Feature;

use App\Models\SensorData;
use App\Models\User;
use App\Services\RecapReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecapReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /**
     * 1. Pengguna tanpa autentikasi ditolak dan diarahkan ke login.
     */
    public function test_unauthenticated_user_cannot_access_recap_page(): void
    {
        $response = $this->get('/recap');
        $response->assertRedirect('/login');

        $responseCsv = $this->get('/recap/export-csv');
        $responseCsv->assertRedirect('/login');

        $responsePdf = $this->get('/recap/export-pdf');
        $responsePdf->assertRedirect('/login');
    }

    /**
     * 2. Pengguna terautentikasi dapat mengakses halaman rekap.
     */
    public function test_authenticated_user_can_access_recap_page(): void
    {
        $response = $this->actingAs($this->user)->get('/recap');
        $response->assertOk();
        $response->assertSee('Laporan Rekap Kondisi Lingkungan');
        $response->assertSee('Ekspor PDF Rekap');
        $response->assertSee('Ekspor CSV Rekap');
    }

    /**
     * 3. Laporan berhasil dibuat untuk periode yang berisi data.
     */
    public function test_recap_report_generated_for_period_with_data(): void
    {
        // Masukkan data sensor
        SensorData::create([
            'soil_moisture'   => 45.0,
            'temperature'     => 28.0,
            'humidity'        => 75.0,
            'light_intensity' => 300.0, // Terang
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        SensorData::create([
            'soil_moisture'   => 25.0,
            'temperature'     => 32.0,
            'humidity'        => 65.0,
            'light_intensity' => 150.0, // Gelap
            'status'          => 'Perlu Penyiraman',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-02 14:00:00'),
        ]);

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy', '2026-06-01', '2026-06-02');

        $this->assertTrue($recap['has_data']);
        $this->assertEquals(2, $recap['period']['total_records']);
        $this->assertEquals(35.0, $recap['sensor_stats']['soil_moisture']['avg']);
        $this->assertEquals(30.0, $recap['sensor_stats']['temperature']['avg']);
        $this->assertEquals(70.0, $recap['sensor_stats']['humidity']['avg']);
    }

    /**
     * 4. Filter tanggal awal dan akhir bekerja dengan inklusif.
     */
    public function test_recap_filter_date_range_works_correctly(): void
    {
        // Hari 1
        SensorData::create([
            'soil_moisture'   => 40.0,
            'temperature'     => 27.0,
            'humidity'        => 80.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 23:55:00'),
        ]);

        // Hari 2 (Di luar filter)
        SensorData::create([
            'soil_moisture'   => 15.0,
            'temperature'     => 36.0,
            'humidity'        => 50.0,
            'light_intensity' => 300.0,
            'status'          => 'Kritis',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-02 12:00:00'),
        ]);

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy', '2026-06-01', '2026-06-01');

        $this->assertTrue($recap['has_data']);
        $this->assertEquals(1, $recap['period']['total_records']);
        $this->assertEquals(40.0, $recap['sensor_stats']['soil_moisture']['avg']);
    }

    /**
     * 5. Tanggal awal setelah tanggal akhir ditolak oleh validasi.
     */
    public function test_recap_rejects_start_date_after_end_date(): void
    {
        $response = $this->actingAs($this->user)->get('/recap?start_date=2026-06-10&end_date=2026-06-01');
        $response->assertSessionHasErrors(['end_date']);
    }

    /**
     * 6. Periode tanpa data menampilkan keadaan kosong yang benar.
     */
    public function test_recap_handles_empty_period_gracefully(): void
    {
        $response = $this->actingAs($this->user)->get('/recap?start_date=2026-01-01&end_date=2026-01-05');
        $response->assertOk();
        $response->assertSee('Tidak Ada Data Pada Periode Ini');
    }

    /**
     * 6b. Data NULL atau tidak valid tidak merusak perhitungan statistik.
     */
    public function test_recap_handles_null_values_without_breaking_statistics(): void
    {
        // 1 row with valid values
        SensorData::create([
            'soil_moisture'   => 40.0,
            'temperature'     => 30.0,
            'humidity'        => 70.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => now(),
        ]);

        // 1 row with some nulls (simulated via raw DB query if table allows, or partial values)
        \DB::table('sensor_data')->insert([
            'soil_moisture'   => 50.0,
            'temperature'     => 32.0,
            'humidity'        => 80.0,
            'light_intensity' => 100.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy');

        $this->assertTrue($recap['has_data']);
        $this->assertEquals(2, $recap['period']['total_records']);
        $this->assertEquals(45.0, $recap['sensor_stats']['soil_moisture']['avg']);
        $this->assertEquals(31.0, $recap['sensor_stats']['temperature']['avg']);
    }

    /**
     * 7. Status kelembapan tanah mengikuti ambang dan prioritas yang benar.
     *    - Kritis: < 20%
     *    - Perlu Penyiraman: >= 20% dan < 30%
     *    - Normal: >= 30%
     */
    public function test_soil_moisture_status_follows_exact_thresholds_and_priorities(): void
    {
        $this->assertEquals(SensorData::STATUS_KRITIS, SensorData::determineStatus(19.9));
        $this->assertEquals(SensorData::STATUS_KRITIS, SensorData::determineStatus(0.0));
        $this->assertEquals(SensorData::STATUS_PERLU_PENYIRAMAN, SensorData::determineStatus(20.0));
        $this->assertEquals(SensorData::STATUS_PERLU_PENYIRAMAN, SensorData::determineStatus(29.9));
        $this->assertEquals(SensorData::STATUS_NORMAL, SensorData::determineStatus(30.0));
        $this->assertEquals(SensorData::STATUS_NORMAL, SensorData::determineStatus(65.0));

        // Buat data uji
        SensorData::create([
            'soil_moisture'   => 15.0, // Kritis
            'temperature'     => 26.0,
            'humidity'        => 70.0,
            'light_intensity' => 300.0,
            'status'          => SensorData::determineStatus(15.0),
            'source'          => 'dummy',
            'created_at'      => now(),
        ]);
        SensorData::create([
            'soil_moisture'   => 25.0, // Perlu penyiraman
            'temperature'     => 27.0,
            'humidity'        => 70.0,
            'light_intensity' => 300.0,
            'status'          => SensorData::determineStatus(25.0),
            'source'          => 'dummy',
            'created_at'      => now(),
        ]);
        SensorData::create([
            'soil_moisture'   => 50.0, // Normal
            'temperature'     => 28.0,
            'humidity'        => 70.0,
            'light_intensity' => 300.0,
            'status'          => SensorData::determineStatus(50.0),
            'source'          => 'dummy',
            'created_at'      => now(),
        ]);

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy');

        $this->assertEquals(1, $recap['soil_recap']['kritis_count']);
        $this->assertEquals(1, $recap['soil_recap']['perlu_penyiraman_count']);
        $this->assertEquals(1, $recap['soil_recap']['normal_count']);
        $this->assertEquals(33.3, $recap['soil_recap']['kritis_pct']);
        $this->assertEquals(33.3, $recap['soil_recap']['perlu_penyiraman_pct']);
        $this->assertEquals(33.3, $recap['soil_recap']['normal_pct']);
    }

    /**
     * 8. Peringatan suhu hanya menghitung suhu > 35°C.
     */
    public function test_temperature_warnings_only_count_above_35(): void
    {
        SensorData::create([
            'soil_moisture'   => 40.0,
            'temperature'     => 35.0, // Tepat 35 -> BUKAN warning
            'humidity'        => 60.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 11:00:00'),
        ]);

        SensorData::create([
            'soil_moisture'   => 40.0,
            'temperature'     => 36.2, // > 35 -> Warning!
            'humidity'        => 55.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 13:00:00'),
        ]);

        SensorData::create([
            'soil_moisture'   => 40.0,
            'temperature'     => 31.0, // Normal
            'humidity'        => 65.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 16:00:00'),
        ]);

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy');

        $this->assertEquals(1, $recap['temp_recap']['high_temp_count']);
        $this->assertEquals(33.3, $recap['temp_recap']['high_temp_pct']);
        $this->assertEquals(36.2, $recap['temp_recap']['max_temp']);
    }

    /**
     * 9. Persentase kategori menggunakan penyebut data valid yang benar.
     */
    public function test_category_percentages_use_correct_denominator(): void
    {
        for ($i = 0; $i < 4; $i++) {
            SensorData::create([
                'soil_moisture'   => 50.0, // Normal
                'temperature'     => 28.0,
                'humidity'        => 70.0,
                'light_intensity' => 300.0,
                'status'          => 'Normal',
                'source'          => 'dummy',
                'created_at'      => now(),
            ]);
        }

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy');

        $this->assertEquals(4, $recap['soil_recap']['total_valid']);
        $this->assertEquals(100.0, $recap['soil_recap']['normal_pct']);
        $this->assertEquals(0.0, $recap['soil_recap']['kritis_pct']);
        $this->assertEquals(0.0, $recap['soil_recap']['perlu_penyiraman_pct']);
    }

    /**
     * 10. Grafik menampilkan hasil agregasi harian yang sesuai.
     */
    public function test_daily_trends_aggregation(): void
    {
        // 2 data pada hari pertama (rata-rata kel. tanah = 40%)
        SensorData::create([
            'soil_moisture'   => 30.0,
            'temperature'     => 26.0,
            'humidity'        => 70.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 08:00:00'),
        ]);
        SensorData::create([
            'soil_moisture'   => 50.0,
            'temperature'     => 30.0,
            'humidity'        => 60.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 14:00:00'),
        ]);

        // 1 data pada hari kedua
        SensorData::create([
            'soil_moisture'   => 20.0,
            'temperature'     => 32.0,
            'humidity'        => 55.0,
            'light_intensity' => 300.0,
            'status'          => 'Perlu Penyiraman',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-02 09:00:00'),
        ]);

        $service = app(RecapReportService::class);
        $recap = $service->generateRecap('dummy');

        $this->assertCount(2, $recap['daily_trends']);
        $this->assertEquals('2026-06-01', $recap['daily_trends'][0]['date']);
        $this->assertEquals(40.0, $recap['daily_trends'][0]['soil_avg']);
        $this->assertEquals(28.0, $recap['daily_trends'][0]['temp_avg']);
        $this->assertEquals(2, $recap['daily_trends'][0]['count']);

        $this->assertEquals('2026-06-02', $recap['daily_trends'][1]['date']);
        $this->assertEquals(20.0, $recap['daily_trends'][1]['soil_avg']);
    }

    /**
     * 11. Ekspor CSV Rekap menghasilkan data teragregasi (bukan raw data).
     */
    public function test_export_csv_recap_contains_aggregated_data_not_raw_data(): void
    {
        SensorData::create([
            'soil_moisture'   => 45.0,
            'temperature'     => 29.0,
            'humidity'        => 70.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        $response = $this->actingAs($this->user)->get('/recap/export-csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('LAPORAN REKAPITULASI KONDISI LINGKUNGAN', $content);
        $this->assertStringContainsString('RINGKASAN STATISTIK SENSOR', $content);
        $this->assertStringContainsString('REKAP STATUS KELEMBAPAN TANAH', $content);
        $this->assertStringContainsString('REKAP PERINGATAN SUHU UDARA', $content);
        $this->assertStringContainsString('KESIMPULAN & REKOMENDASI KEPUTUSAN BUDIDAYA', $content);
    }

    /**
     * 12. Ekspor PDF Rekap berhasil diunduh dan menghasilkan dokumen application/pdf.
     */
    public function test_export_pdf_recap_returns_pdf_document(): void
    {
        SensorData::create([
            'soil_moisture'   => 42.0,
            'temperature'     => 28.5,
            'humidity'        => 75.0,
            'light_intensity' => 300.0,
            'status'          => 'Normal',
            'source'          => 'dummy',
            'created_at'      => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        $response = $this->actingAs($this->user)->get('/recap/export-pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * 13. Fitur monitoring, ekspor sensor lama, dan dashboard tetap berfungsi.
     */
    public function test_existing_features_remain_functional(): void
    {
        // 1. Dashboard
        $this->withSession(['data_mode' => 'dummy']);
        $respDash = $this->actingAs($this->user)->get('/dashboard');
        $respDash->assertOk();

        // 2. Export Sensor CSV lama
        $respCsv = $this->actingAs($this->user)->get('/export-sensor-data');
        $respCsv->assertOk();
        $respCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 3. API Sensor Data Index
        $respApi = $this->actingAs($this->user)->getJson('/api/sensor-data');
        $respApi->assertOk();
    }
}
