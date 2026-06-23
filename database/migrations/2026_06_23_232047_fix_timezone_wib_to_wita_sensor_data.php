<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Koreksi timezone data sensor dari WIB (UTC+7) ke WITA (UTC+8).
     *
     * Latar belakang:
     * - Laravel sebelumnya dikonfigurasi dengan timezone 'Asia/Jakarta' (WIB, UTC+7)
     * - Lokasi kebun sebenarnya di zona WITA (UTC+8)
     * - Semua timestamp (created_at, updated_at, recorded_at) tercatat 1 jam lebih lambat
     * - Perlu digeser +1 jam agar sesuai WITA
     *
     * Catatan: Operasi ini AMAN karena:
     * - Hanya menggeser timestamp, nilai sensor tidak berubah
     * - Jarak antar data tetap sama (tidak mempengaruhi ARIMA)
     * - Berlaku untuk SEMUA data (dummy & real) agar konsisten
     */
    public function up(): void
    {
        DB::statement('
            UPDATE sensor_data
            SET created_at  = DATE_ADD(created_at, INTERVAL 1 HOUR),
                updated_at  = DATE_ADD(updated_at, INTERVAL 1 HOUR),
                recorded_at = DATE_ADD(recorded_at, INTERVAL 1 HOUR)
        ');
    }

    /**
     * Rollback: geser kembali -1 jam (WITA → WIB).
     */
    public function down(): void
    {
        DB::statement('
            UPDATE sensor_data
            SET created_at  = DATE_SUB(created_at, INTERVAL 1 HOUR),
                updated_at  = DATE_SUB(updated_at, INTERVAL 1 HOUR),
                recorded_at = DATE_SUB(recorded_at, INTERVAL 1 HOUR)
        ');
    }
};
