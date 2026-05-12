<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $fillable = [
        'soil_moisture',
        'temperature',
        'humidity',
        'light_intensity',
        'status',
        'source',
    ];
}
