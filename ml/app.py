"""
Jambu Kristal Monitoring — ML Forecasting Service
ARIMA time series forecasting for all 4 sensors.
"""

import os
import warnings
import pymysql
import pandas as pd
import numpy as np
from datetime import datetime, timedelta, timezone
from flask import Flask, jsonify, request
from dotenv import load_dotenv
from statsmodels.tsa.arima.model import ARIMA
from sklearn.model_selection import TimeSeriesSplit
from sklearn.metrics import mean_absolute_error, mean_squared_error

# Suppress convergence warnings from statsmodels
warnings.filterwarnings('ignore')

load_dotenv()

app = Flask(__name__)

# ── Database Config ───────────────────────────────────────
DB_CONFIG = {
    'host':     os.getenv('DB_HOST', '127.0.0.1'),
    'port':     int(os.getenv('DB_PORT', 3306)),
    'user':     os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'db_jambu_monitoring'),
    'charset':  'utf8mb4',
}

SENSORS = ['soil_moisture', 'temperature', 'humidity', 'light_intensity']
FORECAST_STEPS = 24  # predict 24 steps ahead

# Timezone: WITA (UTC+8) — Asia/Makassar
WITA = timezone(timedelta(hours=8))


def get_sensor_data(source='dummy', limit=1000):
    """Fetch sensor data from MySQL — ambil data TERBARU."""
    conn = pymysql.connect(**DB_CONFIG)
    try:
        # Subquery: ambil N data terbaru, lalu urutkan ASC untuk ARIMA
        query = """
            SELECT * FROM (
                SELECT created_at, soil_moisture, temperature, humidity, light_intensity
                FROM sensor_data
                WHERE source = %s
                ORDER BY created_at DESC
                LIMIT %s
            ) AS recent
            ORDER BY created_at ASC
        """
        df = pd.read_sql(query, conn, params=[source, limit])

        # Koreksi timezone: jika server pakai UTC, konversi ke WITA
        if not df.empty:
            df['created_at'] = pd.to_datetime(df['created_at'])
            now_local = datetime.now()
            now_utc = datetime.now(timezone.utc)
            server_offset_hours = round((now_local - now_utc.replace(tzinfo=None)).total_seconds() / 3600)

            # Jika server berjalan di UTC (offset 0), data perlu digeser ke WITA (+8)
            if server_offset_hours == 0:
                df['created_at'] = df['created_at'] + timedelta(hours=8)

        return df
    finally:
        conn.close()


def fit_arima_forecast(series, steps=FORECAST_STEPS, order=(2, 1, 2)):
    """
    Fit ARIMA model and forecast.
    Returns list of forecasted values.
    """
    try:
        # Clean the series
        series = series.dropna().astype(float)

        if len(series) < 10:
            if len(series) > 0:
                last_val = float(series.iloc[-1])
                return [last_val] * steps, "Data < 10. Fallback: Repeated last value"
            else:
                return None, "Series kosong"

        # Fit ARIMA
        model = ARIMA(series, order=order)
        fitted = model.fit()

        # Forecast
        forecast = fitted.forecast(steps=steps)

        return forecast.tolist(), None

    except Exception as e:
        # Fallback: return last value repeated
        if len(series) > 0:
            last_val = float(series.iloc[-1])
            return [last_val] * steps, f"Fallback (error: {str(e)[:100]})"
        return None, str(e)


def cross_validate_arima(series, n_splits=5, order=(2, 1, 2)):
    """
    Perform 5-Fold Time Series Cross-Validation on ARIMA.
    Uses sklearn's TimeSeriesSplit to preserve temporal ordering.

    Returns dict with per-fold and average metrics (MAE, RMSE, MAPE).
    """
    series = series.dropna().astype(float).reset_index(drop=True)

    if len(series) < 30:
        return {
            'avg_mae': None, 'avg_rmse': None, 'avg_mape': None,
            'folds': [],
            'error': f'Data terlalu sedikit untuk cross-validation ({len(series)} < 30)'
        }

    tscv = TimeSeriesSplit(n_splits=n_splits)
    fold_results = []

    for fold_idx, (train_idx, test_idx) in enumerate(tscv.split(series), start=1):
        train = series.iloc[train_idx]
        test = series.iloc[test_idx]

        try:
            model = ARIMA(train, order=order)
            fitted = model.fit()
            predictions = fitted.forecast(steps=len(test))

            # Compute metrics
            mae = mean_absolute_error(test, predictions)
            rmse = np.sqrt(mean_squared_error(test, predictions))

            # MAPE — avoid division by zero
            non_zero_mask = test != 0
            if non_zero_mask.sum() > 0:
                mape = np.mean(
                    np.abs((test[non_zero_mask] - predictions[non_zero_mask.values])
                           / test[non_zero_mask])
                ) * 100
            else:
                mape = 0.0

            fold_results.append({
                'fold': fold_idx,
                'train_size': len(train),
                'test_size': len(test),
                'mae': round(float(mae), 4),
                'rmse': round(float(rmse), 4),
                'mape': round(float(mape), 2),
            })

        except Exception as e:
            fold_results.append({
                'fold': fold_idx,
                'train_size': len(train),
                'test_size': len(test),
                'mae': None, 'rmse': None, 'mape': None,
                'error': str(e)[:100],
            })

    # Calculate average metrics (only from successful folds)
    successful = [f for f in fold_results if f['mae'] is not None]

    if successful:
        avg_mae = round(np.mean([f['mae'] for f in successful]), 4)
        avg_rmse = round(np.mean([f['rmse'] for f in successful]), 4)
        avg_mape = round(np.mean([f['mape'] for f in successful]), 2)
    else:
        avg_mae = avg_rmse = avg_mape = None

    return {
        'n_folds': n_splits,
        'successful_folds': len(successful),
        'avg_mae': avg_mae,
        'avg_rmse': avg_rmse,
        'avg_mape': avg_mape,
        'folds': fold_results,
    }


# ── Routes ────────────────────────────────────────────────

@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint."""
    return jsonify({
        'status': 'ok',
        'service': 'jambu-ml-forecasting',
        'model': 'ARIMA',
        'timestamp': datetime.now().isoformat()
    })


@app.route('/predict', methods=['GET'])
def predict():
    """
    Predict sensor values using ARIMA.

    Query params:
        source: 'dummy' or 'real' (default: 'dummy')
        hours:  number of hours to forecast ahead (default: 4)
        steps:  number of forecast steps (legacy, overridden by hours if provided)
        limit:  max data points to use (default: 1000)
    """
    source = request.args.get('source', 'dummy')
    hours = request.args.get('hours', None)
    steps = int(request.args.get('steps', FORECAST_STEPS))
    limit = int(request.args.get('limit', 1000))

    # Fetch data
    df = get_sensor_data(source=source, limit=limit)

    if df.empty:
        return jsonify({
            'status': 'error',
            'message': f'Data tidak ditemukan untuk source="{source}".'
        }), 400

    # Determine time interval from data
    df['created_at'] = pd.to_datetime(df['created_at'])
    if len(df) > 1:
        avg_interval = (df['created_at'].diff().dropna().mean())
    else:
        avg_interval = timedelta(minutes=10)

    # If hours is provided, calculate steps based on actual data interval
    if hours is not None:
        hours = int(hours)
        target_duration = timedelta(hours=hours)
        # Calculate how many steps needed to cover the target duration
        interval_seconds = max(avg_interval.total_seconds(), 1)  # avoid division by zero
        steps = max(6, int(target_duration.total_seconds() / interval_seconds))
        # Cap at 360 steps (reasonable limit)
        steps = min(steps, 360)

    last_time = df['created_at'].iloc[-1]

    # Run ARIMA for each sensor + cross-validation
    results = {}
    warnings_list = []
    validation_metrics = {}

    for sensor in SENSORS:
        series = df[sensor]
        forecast, warn = fit_arima_forecast(series, steps=steps)

        if forecast is None:
            return jsonify({
                'status': 'error',
                'message': f'Gagal prediksi {sensor}: {warn}'
            }), 500

        if warn:
            warnings_list.append(f'{sensor}: {warn}')

        # Clamp values to reasonable ranges
        clamped = []
        for val in forecast:
            if sensor == 'soil_moisture':
                val = max(0, min(100, val))
            elif sensor == 'temperature':
                val = max(0, min(60, val))
            elif sensor == 'humidity':
                val = max(0, min(100, val))
            elif sensor == 'light_intensity':
                val = max(0, min(10000, val))
            clamped.append(round(val, 1))

        results[sensor] = clamped

        # 5-Fold Time Series Cross-Validation
        cv_result = cross_validate_arima(series, n_splits=5)
        validation_metrics[sensor] = cv_result

    # Build forecast timestamps
    forecast_times = []
    for i in range(1, steps + 1):
        t = last_time + (avg_interval * i)
        forecast_times.append(t.isoformat())

    # Build actual data (last 1000 points for the chart)
    actual_data = []
    recent = df.tail(1000)
    for _, row in recent.iterrows():
        actual_data.append({
            'time': row['created_at'].isoformat(),
            'soil_moisture': round(float(row['soil_moisture']), 1),
            'temperature': round(float(row['temperature']), 1),
            'humidity': round(float(row['humidity']), 1),
            'light_intensity': round(float(row['light_intensity']), 1),
        })

    # Build forecast data
    forecast_data = []
    for i in range(steps):
        forecast_data.append({
            'time': forecast_times[i],
            'soil_moisture': results['soil_moisture'][i],
            'temperature': results['temperature'][i],
            'humidity': results['humidity'][i],
            'light_intensity': results['light_intensity'][i],
        })

    return jsonify({
        'status': 'success',
        'model': 'ARIMA(2,1,2)',
        'source': source,
        'data_points_used': len(df),
        'forecast_steps': steps,
        'actual': actual_data,
        'forecast': forecast_data,
        'warnings': warnings_list if warnings_list else None,
        'cross_validation': {
            'method': '5-Fold Time Series Split',
            'n_folds': 5,
            'metrics': validation_metrics,
        },
    })


@app.route('/predict/pdf', methods=['GET'])
def predict_pdf():
    """
    Generate PDF report with ARIMA forecast charts.
    Returns a PDF file with:
    - Header with project info
    - 4 charts (one per sensor) showing actual vs predicted
    - Summary table of forecast values
    """
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    import matplotlib.dates as mdates
    from io import BytesIO
    from reportlab.lib.pagesizes import A4
    from reportlab.lib import colors
    from reportlab.lib.units import cm, mm
    from reportlab.platypus import (
        SimpleDocTemplate, Paragraph, Spacer, Image, Table, TableStyle,
        PageBreak
    )
    from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
    from reportlab.lib.enums import TA_CENTER, TA_LEFT

    source = request.args.get('source', 'dummy')
    steps = int(request.args.get('steps', FORECAST_STEPS))
    limit = int(request.args.get('limit', 1000))

    # Fetch data
    df = get_sensor_data(source=source, limit=limit)
    if df.empty:
        return jsonify({'status': 'error', 'message': 'Data tidak ditemukan'}), 400

    df['created_at'] = pd.to_datetime(df['created_at'])

    if len(df) > 1:
        avg_interval = df['created_at'].diff().dropna().mean()
    else:
        avg_interval = timedelta(minutes=10)

    last_time = df['created_at'].iloc[-1]

    # Run ARIMA predictions + cross-validation
    all_forecasts = {}
    all_cv_results = {}
    for sensor in SENSORS:
        series = df[sensor]
        forecast, warn = fit_arima_forecast(series, steps=steps)
        if forecast is None:
            forecast = [float(series.iloc[-1])] * steps
        # Clamp
        clamped = []
        for val in forecast:
            if sensor == 'soil_moisture':
                val = max(0, min(100, val))
            elif sensor == 'temperature':
                val = max(0, min(60, val))
            elif sensor == 'humidity':
                val = max(0, min(100, val))
            elif sensor == 'light_intensity':
                val = max(0, min(10000, val))
            clamped.append(round(val, 1))
        all_forecasts[sensor] = clamped

        # 5-Fold Cross-Validation for PDF
        cv_result = cross_validate_arima(series, n_splits=5)
        all_cv_results[sensor] = cv_result

    # Build forecast timestamps
    forecast_times = [last_time + avg_interval * (i + 1) for i in range(steps)]

    # Recent actual data for charts
    recent = df.tail(1000)

    # ── Generate charts with matplotlib ──
    sensor_labels = {
        'soil_moisture': ('Kelembapan Tanah (%)', '#26a69a'),
        'temperature': ('Suhu Udara (°C)', '#ef5350'),
        'humidity': ('Kelembapan Udara (%)', '#42a5f5'),
        'light_intensity': ('Intensitas Cahaya (lux)', '#ffa726'),
    }

    chart_images = {}
    for sensor, (label, color) in sensor_labels.items():
        fig, ax = plt.subplots(figsize=(7.5, 3))
        fig.patch.set_facecolor('#f8f9fa')
        ax.set_facecolor('#ffffff')

        # Actual data
        ax.plot(
            recent['created_at'], recent[sensor],
            color=color, linewidth=1.5, label='Aktual', marker='', zorder=2
        )

        # Forecast data (connect from last actual)
        fc_times = [recent['created_at'].iloc[-1]] + forecast_times
        fc_vals = [float(recent[sensor].iloc[-1])] + all_forecasts[sensor]
        ax.plot(
            fc_times, fc_vals,
            color=color, linewidth=1.5, linestyle='--', label='Prediksi ARIMA',
            marker='', zorder=2
        )

        # Vertical line at forecast start
        ax.axvline(
            x=recent['created_at'].iloc[-1],
            color='#888888', linewidth=0.8, linestyle=':', alpha=0.7
        )

        ax.set_title(label, fontsize=11, fontweight='bold', pad=8)
        ax.legend(fontsize=8, loc='upper right')
        ax.tick_params(axis='both', labelsize=7)
        ax.xaxis.set_major_formatter(mdates.DateFormatter('%d/%m\n%H:%M'))
        ax.grid(True, alpha=0.3)

        plt.tight_layout()

        buf = BytesIO()
        fig.savefig(buf, format='png', dpi=150, bbox_inches='tight')
        buf.seek(0)
        chart_images[sensor] = buf
        plt.close(fig)

    # ── Build PDF ──
    pdf_buffer = BytesIO()
    doc = SimpleDocTemplate(
        pdf_buffer, pagesize=A4,
        leftMargin=2*cm, rightMargin=2*cm,
        topMargin=2*cm, bottomMargin=2*cm
    )

    styles = getSampleStyleSheet()
    title_style = ParagraphStyle(
        'CustomTitle', parent=styles['Title'],
        fontSize=18, textColor=colors.HexColor('#1b5e20'),
        spaceAfter=6
    )
    subtitle_style = ParagraphStyle(
        'CustomSubtitle', parent=styles['Normal'],
        fontSize=10, textColor=colors.HexColor('#666666'),
        alignment=TA_CENTER, spaceAfter=20
    )
    heading_style = ParagraphStyle(
        'CustomHeading', parent=styles['Heading2'],
        fontSize=13, textColor=colors.HexColor('#2e7d32'),
        spaceBefore=16, spaceAfter=8
    )
    body_style = ParagraphStyle(
        'CustomBody', parent=styles['Normal'],
        fontSize=9, textColor=colors.HexColor('#333333'),
        spaceAfter=6
    )

    elements = []

    # Title
    elements.append(Paragraph("🌿 Laporan Prediksi ARIMA", title_style))
    elements.append(Paragraph("Monitoring Kebun Jambu Kristal", subtitle_style))

    # Info section
    mode_label = "Dummy (Pengujian)" if source == "dummy" else "Real (Sensor IoT)"
    generated_at = datetime.now().strftime('%d %B %Y, %H:%M WIB')
    info_text = f"""
    <b>Tanggal Cetak:</b> {generated_at}<br/>
    <b>Mode Data:</b> {mode_label}<br/>
    <b>Model:</b> ARIMA(2,1,2)<br/>
    <b>Jumlah Data Historis:</b> {len(df)} record<br/>
    <b>Langkah Prediksi:</b> {steps} langkah ke depan
    """
    elements.append(Paragraph(info_text, body_style))
    elements.append(Spacer(1, 12))

    # ── ARIMA Methodology Section (Simplified) ──
    elements.append(Paragraph("Apa Itu ARIMA?", heading_style))

    method_body = ParagraphStyle(
        'MethodBody', parent=body_style,
        fontSize=9, leading=14, spaceAfter=8,
        textColor=colors.HexColor('#333333')
    )
    method_bold = ParagraphStyle(
        'MethodBold', parent=body_style,
        fontSize=9, leading=14, spaceAfter=4,
        textColor=colors.HexColor('#1b5e20')
    )
    analogy_style = ParagraphStyle(
        'AnalogyStyle', parent=body_style,
        fontSize=9, leading=14, spaceAfter=6,
        textColor=colors.HexColor('#4a4a4a'),
        leftIndent=15, rightIndent=15,
        borderPadding=8,
        backColor=colors.HexColor('#f0f7f0'),
    )
    step_style = ParagraphStyle(
        'StepStyle', parent=body_style,
        fontSize=8, leading=12, spaceAfter=5,
        textColor=colors.HexColor('#555555'),
        leftIndent=15
    )

    elements.append(Paragraph(
        "<b>ARIMA</b> adalah singkatan dari <b>AutoRegressive Integrated Moving Average</b>, "
        "sebuah metode statistik yang digunakan untuk <b>memprediksi nilai di masa depan</b> "
        "berdasarkan pola data di masa lalu. Metode ini sangat populer digunakan dalam analisis "
        "<i>time series</i> (data yang berurutan berdasarkan waktu), seperti data sensor pada "
        "sistem monitoring ini.",
        method_body
    ))

    elements.append(Paragraph(
        "ARIMA bekerja dengan prinsip sederhana: <b>apa yang terjadi di masa lalu cenderung "
        "membentuk pola yang akan berulang di masa depan</b>. Dengan mempelajari pola naik-turun "
        "data sensor sebelumnya, ARIMA dapat memperkirakan bagaimana kondisi kebun akan berubah "
        "dalam beberapa jam ke depan.",
        method_body
    ))

    elements.append(Spacer(1, 6))

    # Analogy section
    elements.append(Paragraph("<b>Analogi Sederhana:</b>", method_bold))

    elements.append(Paragraph(
        "Bayangkan Anda adalah seorang petani yang setiap hari mencatat suhu kebun di buku catatan. "
        "Setelah berminggu-minggu, Anda mulai menyadari pola: setiap pagi suhu sekitar 24°C, siang naik ke 33°C, "
        "dan malam turun lagi ke 25°C. Anda juga tahu bahwa kalau kemarin hujan, hari ini biasanya lebih sejuk.",
        analogy_style
    ))
    elements.append(Paragraph(
        "Tanpa sadar, otak Anda sudah melakukan \"ARIMA\" secara alami! "
        "Anda menggunakan <b>data masa lalu</b> (catatan suhu kemarin dan hari sebelumnya) "
        "serta <b>koreksi dari kesalahan</b> (\"kemarin saya kira 33°C ternyata cuma 31°C, "
        "berarti hari ini mungkin juga agak lebih rendah\") untuk membuat tebakan yang lebih akurat.",
        analogy_style
    ))
    elements.append(Paragraph(
        "ARIMA melakukan hal yang sama, tapi secara <b>matematis dan otomatis</b> — "
        "sehingga hasilnya lebih konsisten dan bisa memproses ribuan data sekaligus.",
        analogy_style
    ))

    elements.append(Spacer(1, 10))

    # Components — simplified
    elements.append(Paragraph("<b>3 Komponen Utama ARIMA:</b>", method_bold))

    table_analogi_style = ParagraphStyle(
        'TableAnalogi', parent=body_style,
        fontSize=7.5, leading=10, textColor=colors.HexColor('#333333')
    )

    comp_data = [
        ['Komponen', 'Arti Singkat', 'Analogi'],
        ['AR\n(Auto Regressive)',
         'Melihat data\nsebelumnya',
         Paragraph('\"Kalau kemarin suhu 33°C dan lusa 32°C, maka hari ini kemungkinan sekitar 33°C juga.\" — Menggunakan pola masa lalu.', table_analogi_style)],
        ['I\n(Integrated)',
         'Menghilangkan\ntren',
         Paragraph('\"Suhu terus naik karena musim kemarau, tapi ARIMA bisa memisahkan tren naik ini agar prediksi tetap akurat.\"', table_analogi_style)],
        ['MA\n(Moving Average)',
         'Belajar dari\nkesalahan',
         Paragraph('\"Kemarin saya prediksi 33°C tapi ternyata 31°C. Selisih 2°C ini saya gunakan untuk memperbaiki tebakan hari ini.\"', table_analogi_style)],
    ]

    comp_table = Table(comp_data, colWidths=[2.8*cm, 2.8*cm, 10.8*cm])
    comp_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#2e7d32')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTSIZE', (0, 0), (-1, 0), 8),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 1), (-1, -1), 7.5),
        ('FONTNAME', (0, 1), (1, -1), 'Helvetica-Bold'),
        ('ALIGN', (0, 0), (1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#cccccc')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [
            colors.HexColor('#ffffff'), colors.HexColor('#f0f7f0')
        ]),
        ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
        ('LEFTPADDING', (2, 1), (2, -1), 8),
    ]))
    elements.append(comp_table)
    elements.append(Spacer(1, 10))

    # Model used
    elements.append(Paragraph(
        "Pada sistem ini digunakan model <b>ARIMA(2,1,2)</b>, artinya: "
        "model melihat <b>2 data terakhir</b> untuk membaca pola, "
        "melakukan <b>1 kali penyesuaian tren</b>, dan mempelajari "
        "<b>2 kesalahan prediksi terakhir</b> untuk memperbaiki hasil.",
        method_body
    ))

    elements.append(Spacer(1, 8))

    # How it works in this system
    elements.append(Paragraph("<b>Bagaimana Sistem Ini Bekerja:</b>", method_bold))
    workflow_items = [
        "1. Sensor IoT mengumpulkan data (suhu, kelembapan, cahaya) setiap beberapa menit.",
        "2. Data historis dikirim ke model ARIMA untuk dipelajari polanya.",
        "3. ARIMA memprediksi " + str(steps) + " langkah ke depan berdasarkan pola yang ditemukan.",
        "4. Hasil prediksi ditampilkan dalam grafik dan tabel pada laporan ini.",
        "5. Petani dapat menggunakan prediksi ini untuk merencanakan penyiraman, pemupukan, atau perlindungan tanaman.",
    ]
    for item in workflow_items:
        elements.append(Paragraph(item, step_style))

    elements.append(Spacer(1, 16))

    # ── 5-Fold Cross-Validation Evaluation Section ──
    elements.append(Paragraph("Evaluasi Model — 5-Fold Cross-Validation", heading_style))

    elements.append(Paragraph(
        "Untuk memastikan model ARIMA memberikan prediksi yang akurat, dilakukan evaluasi "
        "menggunakan metode <b>5-Fold Time Series Cross-Validation</b>. Data historis dibagi "
        "menjadi 5 bagian secara berurutan (menjaga urutan waktu). Pada setiap fold, model "
        "dilatih pada data sebelumnya dan diuji pada data berikutnya.",
        method_body
    ))
    elements.append(Spacer(1, 8))

    # Metric explanation
    elements.append(Paragraph("<b>Penjelasan Metric:</b>", method_bold))
    metric_explanations = [
        "• <b>MAE</b> (Mean Absolute Error): Rata-rata selisih absolut antara prediksi dan aktual. Semakin kecil semakin baik.",
        "• <b>RMSE</b> (Root Mean Squared Error): Akar rata-rata kuadrat error. Lebih sensitif terhadap error besar.",
        "• <b>MAPE</b> (Mean Absolute Percentage Error): Error dalam persen. Di bawah 10% = sangat baik, 10-20% = baik.",
    ]
    for exp in metric_explanations:
        elements.append(Paragraph(exp, step_style))
    elements.append(Spacer(1, 10))

    # Summary table of CV results
    sensor_display_names = {
        'soil_moisture': 'Kel. Tanah (%)',
        'temperature': 'Suhu (°C)',
        'humidity': 'Kel. Udara (%)',
        'light_intensity': 'Cahaya (lux)',
    }

    cv_summary_data = [['Sensor', 'MAE', 'RMSE', 'MAPE (%)', 'Fold Berhasil']]
    for sensor in SENSORS:
        cv = all_cv_results.get(sensor, {})
        cv_summary_data.append([
            sensor_display_names.get(sensor, sensor),
            str(cv.get('avg_mae', 'N/A')),
            str(cv.get('avg_rmse', 'N/A')),
            str(cv.get('avg_mape', 'N/A')),
            f"{cv.get('successful_folds', 0)}/{cv.get('n_folds', 5)}",
        ])

    cv_table = Table(cv_summary_data, colWidths=[4*cm, 2.8*cm, 2.8*cm, 2.8*cm, 3*cm])
    cv_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#1565c0')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTSIZE', (0, 0), (-1, 0), 8),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 1), (-1, -1), 8),
        ('FONTNAME', (0, 1), (0, -1), 'Helvetica-Bold'),
        ('ALIGN', (1, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#cccccc')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [
            colors.HexColor('#ffffff'), colors.HexColor('#e8f0fe')
        ]),
        ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
    ]))
    elements.append(cv_table)
    elements.append(Spacer(1, 12))

    # Detail per fold table
    elements.append(Paragraph("<b>Detail Per Fold:</b>", method_bold))
    elements.append(Spacer(1, 4))

    for sensor in SENSORS:
        cv = all_cv_results.get(sensor, {})
        folds = cv.get('folds', [])
        if not folds:
            continue

        elements.append(Paragraph(
            f"<b>{sensor_display_names.get(sensor, sensor)}</b>",
            ParagraphStyle('FoldSensor', parent=body_style, fontSize=8,
                          textColor=colors.HexColor('#1565c0'), spaceAfter=4)
        ))

        fold_data = [['Fold', 'Train', 'Test', 'MAE', 'RMSE', 'MAPE (%)']]
        for f in folds:
            fold_data.append([
                str(f.get('fold', '')),
                str(f.get('train_size', '')),
                str(f.get('test_size', '')),
                str(f.get('mae', 'Error')),
                str(f.get('rmse', 'Error')),
                str(f.get('mape', 'Error')),
            ])

        fold_table = Table(fold_data, colWidths=[1.5*cm, 2.2*cm, 2.2*cm, 3*cm, 3*cm, 3*cm])
        fold_table.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#455a64')),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
            ('FONTSIZE', (0, 0), (-1, -1), 7),
            ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
            ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor('#dddddd')),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [
                colors.HexColor('#ffffff'), colors.HexColor('#f5f5f5')
            ]),
            ('TOPPADDING', (0, 0), (-1, -1), 4),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ]))
        elements.append(fold_table)
        elements.append(Spacer(1, 8))

    # Interpretation helper
    elements.append(Spacer(1, 6))
    elements.append(Paragraph(
        "<b>Interpretasi MAPE:</b> &lt; 10% = Sangat Baik | 10–20% = Baik | "
        "20–50% = Cukup | &gt; 50% = Perlu Perbaikan Model",
        ParagraphStyle('MapeGuide', parent=body_style, fontSize=8,
                      textColor=colors.HexColor('#666666'),
                      backColor=colors.HexColor('#fff3e0'),
                      borderPadding=6, spaceAfter=16)
    ))

    # Charts
    elements.append(Paragraph("Grafik Aktual vs Prediksi", heading_style))

    for sensor in SENSORS:
        buf = chart_images[sensor]
        img = Image(buf, width=16.5*cm, height=6.6*cm)
        elements.append(img)
        elements.append(Spacer(1, 8))

    elements.append(PageBreak())

    # Forecast table
    elements.append(Paragraph("Tabel Hasil Prediksi", heading_style))

    table_data = [['Waktu', 'Kel. Tanah (%)', 'Suhu (°C)', 'Kel. Udara (%)', 'Cahaya (lux)']]
    for i, ft in enumerate(forecast_times):
        table_data.append([
            ft.strftime('%d/%m/%Y %H:%M'),
            str(all_forecasts['soil_moisture'][i]),
            str(all_forecasts['temperature'][i]),
            str(all_forecasts['humidity'][i]),
            str(all_forecasts['light_intensity'][i]),
        ])

    table = Table(table_data, repeatRows=1)
    table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#2e7d32')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTSIZE', (0, 0), (-1, 0), 8),
        ('FONTSIZE', (0, 1), (-1, -1), 7),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('ALIGN', (1, 0), (-1, -1), 'CENTER'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#cccccc')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [
            colors.HexColor('#ffffff'), colors.HexColor('#f5f5f5')
        ]),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
    ]))
    elements.append(table)
    elements.append(Spacer(1, 20))

    # Footer note
    elements.append(Paragraph(
        "<i>Catatan: Prediksi menggunakan model ARIMA(2,1,2) berdasarkan data historis. "
        "Hasil prediksi bersifat estimasi dan dapat berbeda dengan kondisi aktual.</i>",
        ParagraphStyle('FooterNote', parent=body_style, fontSize=8, textColor=colors.HexColor('#999999'))
    ))

    doc.build(elements)
    pdf_buffer.seek(0)

    from flask import send_file
    filename = f"prediksi_arima_{source}_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf"
    return send_file(
        pdf_buffer,
        mimetype='application/pdf',
        as_attachment=True,
        download_name=filename
    )


if __name__ == '__main__':
    print("[INFO] Jambu ML Forecasting Service")
    print(f"[INFO] Model: ARIMA | Sensors: {', '.join(SENSORS)}")
    host = os.getenv('FLASK_HOST', '0.0.0.0')
    port = int(os.getenv('FLASK_PORT', 5001))
    debug = os.getenv('FLASK_DEBUG', 'false').lower() == 'true'
    print(f"[INFO] Running on http://{host}:{port}")
    app.run(host=host, port=port, debug=debug)


