<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    // Status Kelembapan Tanah
    public const STATUS_KRITIS = 'Kritis';
    public const STATUS_PERLU_PENYIRAMAN = 'Perlu Penyiraman';
    public const STATUS_NORMAL = 'Normal';

    // Ambang Batas Sistem (Thresholds)
    public const THRESHOLD_SOIL_KRITIS = 20.0;
    public const THRESHOLD_SOIL_PERLU = 30.0;
    public const THRESHOLD_TEMP_WARNING = 35.0;
    public const THRESHOLD_LIGHT_BRIGHT = 250.0;

    protected $fillable = [
        'soil_moisture',
        'temperature',
        'humidity',
        'light_intensity',
        'status',
        'source',
        'recorded_at',
        'created_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    /**
     * Tentukan status kelembapan tanah berdasarkan nilai numerik.
     * Aturan prioritas:
     * - < 20% : Kritis
     * - < 30% : Perlu Penyiraman (20% s/d <30%)
     * - >= 30%: Normal
     */
    public static function determineStatus(float $soilMoisture): string
    {
        if ($soilMoisture < self::THRESHOLD_SOIL_KRITIS) {
            return self::STATUS_KRITIS;
        }

        if ($soilMoisture < self::THRESHOLD_SOIL_PERLU) {
            return self::STATUS_PERLU_PENYIRAMAN;
        }

        return self::STATUS_NORMAL;
    }
}
