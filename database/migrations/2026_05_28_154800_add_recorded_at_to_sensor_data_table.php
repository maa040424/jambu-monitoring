<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambah kolom recorded_at untuk menyimpan waktu data direkam oleh ESP32.
     * Berbeda dengan created_at yang mencatat waktu data masuk ke server.
     * Penting untuk sistem prediksi agar timestamp akurat meski data dikirim terlambat (cache).
     */
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->timestamp('recorded_at')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->dropColumn('recorded_at');
        });
    }
};
