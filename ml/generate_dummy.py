"""
Generate realistic dummy sensor data (1 month)
for Jambu Kristal Monitoring.

Pattern:
- Siang (06:00-18:00): suhu tinggi, cahaya tinggi, soil moisture turun pelan
- Malam (18:00-06:00): suhu rendah, cahaya minimal, soil moisture stabil
- "Penyiraman" terjadi 2x sehari → soil moisture naik drastis
"""

import os
import math
import random
import pymysql
import numpy as np
from datetime import datetime, timedelta
from dotenv import load_dotenv

load_dotenv()

DB_CONFIG = {
    'host':     os.getenv('DB_HOST', '127.0.0.1'),
    'port':     int(os.getenv('DB_PORT', 3306)),
    'user':     os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'db_jambu_monitoring'),
    'charset':  'utf8mb4',
}

def determine_status(soil_moisture):
    if soil_moisture < 20:
        return 'Kritis'
    elif soil_moisture < 30:
        return 'Perlu Penyiraman'
    else:
        return 'Normal'


def generate_data():
    """Generate 30 days of sensor data, 1 reading per 10 minutes."""
    records = []

    # Start from 30 days ago
    start = datetime.now() - timedelta(days=30)
    interval = timedelta(minutes=10)
    total_points = int(30 * 24 * 60 / 10)  # ~4320 readings

    soil = 55.0  # initial soil moisture

    for i in range(total_points):
        t = start + (interval * i)
        hour = t.hour + t.minute / 60.0
        day_of_month = t.day

        # ── Temperature: 22-38°C, peaks at ~14:00 ──
        temp_base = 28 + 7 * math.sin(math.pi * (hour - 6) / 12)
        temp = temp_base + random.gauss(0, 1.5)
        temp = max(20, min(40, temp))

        # ── Humidity: inverse of temperature, 50-90% ──
        hum_base = 75 - 15 * math.sin(math.pi * (hour - 6) / 12)
        hum = hum_base + random.gauss(0, 3)
        hum = max(45, min(95, hum))

        # ── Light: 0 at night, peaks ~1200 at noon ──
        if 6 <= hour <= 18:
            light_base = 800 * math.sin(math.pi * (hour - 6) / 12)
            light = light_base + random.gauss(0, 80)
            # Cloudy days (random)
            if random.random() < 0.15:
                light *= 0.3
        else:
            light = random.uniform(0, 5)
        light = max(0, min(2000, light))

        # ── Soil Moisture: decays during day, watered 2x ──
        # Decay: faster during hot hours
        if 8 <= hour <= 16:
            decay = random.uniform(0.15, 0.35)
        else:
            decay = random.uniform(0.02, 0.08)
        soil -= decay

        # Watering at ~07:00 and ~17:00
        if abs(hour - 7.0) < 0.17 and t.minute == 0:
            soil = min(80, soil + random.uniform(25, 40))
        if abs(hour - 17.0) < 0.17 and t.minute == 0:
            soil = min(75, soil + random.uniform(20, 35))

        # Rain event (random, ~5% chance per day at afternoon)
        if 13 <= hour <= 16 and random.random() < 0.003:
            soil = min(85, soil + random.uniform(15, 30))
            hum = min(95, hum + 10)

        soil = max(5, min(90, soil))

        status = determine_status(soil)

        records.append({
            'soil_moisture': round(soil, 1),
            'temperature': round(temp, 1),
            'humidity': round(hum, 1),
            'light_intensity': round(light, 1),
            'status': status,
            'source': 'dummy',
            'created_at': t.strftime('%Y-%m-%d %H:%M:%S'),
            'updated_at': t.strftime('%Y-%m-%d %H:%M:%S'),
        })

    return records


def insert_data(records):
    """Insert records into MySQL in batches."""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()

    # Clear existing dummy data first
    cursor.execute("DELETE FROM sensor_data WHERE source = 'dummy'")
    print(f"[INFO] Cleared existing dummy data")

    sql = """
        INSERT INTO sensor_data
            (soil_moisture, temperature, humidity, light_intensity, status, source, created_at, updated_at)
        VALUES
            (%(soil_moisture)s, %(temperature)s, %(humidity)s, %(light_intensity)s,
             %(status)s, %(source)s, %(created_at)s, %(updated_at)s)
    """

    batch_size = 500
    for i in range(0, len(records), batch_size):
        batch = records[i:i + batch_size]
        cursor.executemany(sql, batch)
        conn.commit()
        print(f"  -> Inserted batch {i//batch_size + 1} ({len(batch)} records)")

    cursor.close()
    conn.close()


def main():
    print("[INFO] Generating dummy sensor data for Jambu Kristal...")
    print(f"[INFO] Period: 30 days, 1 reading per 10 minutes\n")

    records = generate_data()
    print(f"[INFO] Generated {len(records)} records\n")

    print("[INFO] Inserting into database...")
    insert_data(records)

    print(f"\n[INFO] Done! {len(records)} dummy records inserted.")
    print("   Soil moisture follows realistic day/night + watering patterns.")


if __name__ == '__main__':
    main()
