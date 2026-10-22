#!/usr/bin/env python3
"""
Generator PDF Laporan Rekap Kondisi Lingkungan per Periode.
Menggunakan ReportLab dan Matplotlib untuk menghasilkan dokumen PDF resmi.
Dapat dijalankan via CLI maupun diimpor sebagai fungsi Python.
"""

import sys
import os
import json
from datetime import datetime
from io import BytesIO

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.dates as mdates

from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import cm, mm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Image, Table, TableStyle, PageBreak, KeepTogether
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT, TA_JUSTIFY


def generate_trend_chart_image(daily_trends):
    """
    Menghasilkan gambar grafik tren harian menggunakan Matplotlib.
    Mengembalikan BytesIO buffer PNG.
    """
    if not daily_trends or len(daily_trends) == 0:
        return None

    dates = [d['label'] for d in daily_trends]
    soil = [d['soil_avg'] for d in daily_trends]
    temp = [d['temp_avg'] for d in daily_trends]
    hum = [d['hum_avg'] for d in daily_trends]

    fig, ax1 = plt.subplots(figsize=(7.5, 2.8), dpi=150)
    fig.patch.set_facecolor('#ffffff')
    ax1.set_facecolor('#fafafa')

    # Line 1: Kelembapan Tanah (Hijau)
    line1 = ax1.plot(dates, soil, color='#2e7d32', linewidth=2.0, marker='o', markersize=4, label='Kel. Tanah (%)')
    # Line 2: Kelembapan Udara (Biru)
    line2 = ax1.plot(dates, hum, color='#0288d1', linewidth=1.8, linestyle='-.', marker='s', markersize=3, label='Kel. Udara (%)')
    ax1.set_ylabel('Kelembapan (%)', fontsize=8, color='#333333')
    ax1.tick_params(axis='y', labelsize=7)
    ax1.set_ylim(0, 100)

    # Line 3: Suhu Udara (Merah) di Sumbu Kanan
    ax2 = ax1.twinx()
    line3 = ax2.plot(dates, temp, color='#d32f2f', linewidth=2.0, linestyle='--', marker='^', markersize=4, label='Suhu (°C)')
    ax2.set_ylabel('Suhu (°C)', fontsize=8, color='#d32f2f')
    ax2.tick_params(axis='y', labelcolor='#d32f2f', labelsize=7)
    ax2.set_ylim(15, 45)

    # Threshold line 35°C
    ax2.axhline(y=35, color='#ef5350', linestyle=':', linewidth=1.0, alpha=0.7)

    # Formatting x-axis
    ax1.tick_params(axis='x', labelsize=7, rotation=30)
    ax1.grid(True, linestyle=':', alpha=0.5)

    # Legend kombinasi
    lines = line1 + line2 + line3
    labels = [l.get_label() for l in lines]
    ax1.legend(lines, labels, loc='upper right', fontsize=7, framealpha=0.85)

    plt.title('Tren Agregasi Rata-Rata Harian Parameter Lingkungan', fontsize=9, fontweight='bold', pad=8)
    plt.tight_layout()

    buf = BytesIO()
    fig.savefig(buf, format='png', dpi=150, bbox_inches='tight')
    buf.seek(0)
    plt.close(fig)
    return buf


def build_recap_pdf(recap, output_destination):
    """
    Membangun dokumen PDF dari dictionary recap.
    output_destination dapat berupa path file (string) atau BytesIO buffer.
    """
    doc = SimpleDocTemplate(
        output_destination,
        pagesize=A4,
        leftMargin=1.8 * cm,
        rightMargin=1.8 * cm,
        topMargin=1.8 * cm,
        bottomMargin=1.8 * cm
    )

    styles = getSampleStyleSheet()

    title_style = ParagraphStyle(
        'RecapTitle',
        parent=styles['Title'],
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=18,
        textColor=colors.HexColor('#1b5e20'),
        alignment=TA_CENTER,
        spaceAfter=3
    )

    subtitle_style = ParagraphStyle(
        'RecapSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=12,
        textColor=colors.HexColor('#555555'),
        alignment=TA_CENTER,
        spaceAfter=14
    )

    section_heading = ParagraphStyle(
        'SectionHeading',
        parent=styles['Heading2'],
        fontName='Helvetica-Bold',
        fontSize=11,
        leading=14,
        textColor=colors.HexColor('#1b5e20'),
        spaceBefore=12,
        spaceAfter=6
    )

    body_style = ParagraphStyle(
        'RecapBody',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=12,
        textColor=colors.HexColor('#222222')
    )

    body_bold = ParagraphStyle(
        'RecapBodyBold',
        parent=body_style,
        fontName='Helvetica-Bold'
    )

    bullet_style = ParagraphStyle(
        'RecapBullet',
        parent=body_style,
        leftIndent=12,
        spaceAfter=3
    )

    disclaimer_style = ParagraphStyle(
        'RecapDisclaimer',
        parent=body_style,
        fontName='Helvetica-Oblique',
        fontSize=7.5,
        leading=10,
        textColor=colors.HexColor('#666666'),
        alignment=TA_JUSTIFY
    )

    elements = []

    # 1. KOP LAPORAN
    elements.append(Paragraph("🌿 SISTEM MONITORING KEBUN JAMBU KRISTAL", title_style))
    elements.append(Paragraph("Laporan Rekapitulasi Kondisi Lingkungan &amp; Dukungan Keputusan Budidaya", subtitle_style))

    # Garis pemisah kop
    divider_table = Table([['']], colWidths=[17.4 * cm], rowHeights=[1.5])
    divider_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#2e7d32')),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ]))
    elements.append(divider_table)
    elements.append(Spacer(1, 8))

    # Meta Informasi Laporan
    mode_text = "Real (Sensor IoT ESP32)" if recap.get('mode') == 'real' else "Dummy (Pengujian Simulasi)"
    period_info = recap.get('period', {})
    start_d = period_info.get('start_date') or '-'
    end_d = period_info.get('end_date') or '-'
    total_rec = period_info.get('total_records', 0)
    created_now = datetime.now().strftime('%d/%m/%Y %H:%M WIB')

    meta_data = [
        [
            Paragraph(f"<b>Periode Rekap:</b> {start_d} s/d {end_d}", body_style),
            Paragraph(f"<b>Tanggal Cetak:</b> {created_now}", body_style),
        ],
        [
            Paragraph(f"<b>Mode Data:</b> {mode_text}", body_style),
            Paragraph(f"<b>Total Data Dianalisis:</b> {total_rec} pembacaan", body_style),
        ],
    ]
    meta_table = Table(meta_data, colWidths=[9.0 * cm, 8.4 * cm])
    meta_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 2),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 2),
        ('LEFTPADDING', (0, 0), (-1, -1), 0),
        ('RIGHTPADDING', (0, 0), (-1, -1), 0),
    ]))
    elements.append(meta_table)
    elements.append(Spacer(1, 8))

    if not recap.get('has_data'):
        elements.append(Spacer(1, 20))
        elements.append(Paragraph("<b>STATUS:</b> Tidak ditemukan rekaman data sensor pada rentang tanggal yang dipilih.", body_style))
        doc.build(elements)
        return

    # 2. TABEL RINGKASAN STATISTIK SENSOR
    elements.append(Paragraph("1. Ringkasan Statistik Sensor", section_heading))
    stats = recap.get('sensor_stats', {})
    soil_stat = stats.get('soil_moisture', {})
    temp_stat = stats.get('temperature', {})
    hum_stat = stats.get('humidity', {})
    light_stat = stats.get('light', {})

    stats_data = [
        ['Parameter Sensor', 'Satuan', 'Data Valid', 'Min', 'Max', 'Rata-Rata'],
        [soil_stat.get('name', 'Kelembapan Tanah'), soil_stat.get('unit', '%'), str(soil_stat.get('valid_count', 0)), f"{soil_stat.get('min', '-')}%", f"{soil_stat.get('max', '-')}%", f"{soil_stat.get('avg', '-')}%"],
        [temp_stat.get('name', 'Suhu Udara'), temp_stat.get('unit', '°C'), str(temp_stat.get('valid_count', 0)), f"{temp_stat.get('min', '-')}°C", f"{temp_stat.get('max', '-')}°C", f"{temp_stat.get('avg', '-')}°C"],
        [hum_stat.get('name', 'Kelembapan Udara'), hum_stat.get('unit', '%'), str(hum_stat.get('valid_count', 0)), f"{hum_stat.get('min', '-')}%", f"{hum_stat.get('max', '-')}%", f"{hum_stat.get('avg', '-')}%"],
        [light_stat.get('name', 'Kondisi Cahaya (LM393)'), 'Diskrit', str(light_stat.get('valid_count', 0)), f"Terang: {light_stat.get('terang_count', 0)} ({light_stat.get('terang_pct', 0)}%)", f"Gelap: {light_stat.get('gelap_count', 0)} ({light_stat.get('gelap_pct', 0)}%)", 'Sensor Digital'],
    ]

    t_stats = Table(stats_data, colWidths=[5.4 * cm, 2.0 * cm, 2.2 * cm, 2.6 * cm, 2.6 * cm, 2.6 * cm])
    t_stats.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#2e7d32')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, -1), 8),
        ('ALIGN', (1, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#cccccc')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#f9fbf9')]),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
    ]))
    elements.append(t_stats)
    elements.append(Spacer(1, 8))

    # 3. REKAP STATUS KELEMBAPAN TANAH & PERINGATAN SUHU (2 Kolom berdampingan)
    elements.append(Paragraph("2. Evaluasi Status Kelembapan Tanah &amp; Peringatan Suhu", section_heading))
    soil_recap = recap.get('soil_recap', {})
    temp_recap = recap.get('temp_recap', {})

    soil_table_data = [
        ['Status Kelembapan', 'Ambang Batas', 'Jumlah', 'Proporsi'],
        ['Normal', '>= 30.0%', str(soil_recap.get('normal_count', 0)), f"{soil_recap.get('normal_pct', 0)}%"],
        ['Perlu Penyiraman', '20.0% s/d <30.0%', str(soil_recap.get('perlu_penyiraman_count', 0)), f"{soil_recap.get('perlu_penyiraman_pct', 0)}%"],
        ['Kritis', '< 20.0%', str(soil_recap.get('kritis_count', 0)), f"{soil_recap.get('kritis_pct', 0)}%"],
    ]
    t_soil = Table(soil_table_data, colWidths=[2.8 * cm, 2.8 * cm, 1.5 * cm, 1.5 * cm])
    t_soil.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#37474f')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, -1), 7.5),
        ('ALIGN', (1, 0), (-1, -1), 'CENTER'),
        ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor('#dddddd')),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
    ]))

    temp_table_data = [
        ['Indikator Suhu', 'Kriteria / Nilai'],
        ['Ambang Peringatan', '> 35.0 °C'],
        ['Pembacaan Suhu Tinggi', f"{temp_recap.get('high_temp_count', 0)} kali ({temp_recap.get('high_temp_pct', 0)}%)"],
        ['Suhu Tertinggi', f"{temp_recap.get('max_temp', '-')} °C"],
        ['Waktu Suhu Puncak', str(temp_recap.get('max_temp_time') or '-')],
    ]
    t_temp = Table(temp_table_data, colWidths=[4.2 * cm, 4.0 * cm])
    t_temp.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#c62828')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, -1), 7.5),
        ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor('#dddddd')),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
    ]))

    two_col_table = Table([[t_soil, '', t_temp]], colWidths=[8.6 * cm, 0.4 * cm, 8.4 * cm])
    two_col_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('LEFTPADDING', (0, 0), (-1, -1), 0),
        ('RIGHTPADDING', (0, 0), (-1, -1), 0),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ]))
    elements.append(two_col_table)
    elements.append(Spacer(1, 8))

    # 4. GRAFIK TREN HARIAN (Matplotlib)
    daily_trends = recap.get('daily_trends', [])
    if daily_trends and len(daily_trends) > 0:
        elements.append(Paragraph("3. Grafik Tren Agregasi Rata-Rata Harian", section_heading))
        chart_buf = generate_trend_chart_image(daily_trends)
        if chart_buf:
            img = Image(chart_buf, width=17.4 * cm, height=6.5 * cm)
            elements.append(img)
            elements.append(Spacer(1, 8))

    # 5. KESIMPULAN & REKOMENDASI KEPUTUSAN BUDIDAYA
    elements.append(Paragraph("4. Kesimpulan dan Rekomendasi Keputusan Budidaya", section_heading))

    recom = recap.get('recommendations', {})
    summary_text = recom.get('summary', '')
    elements.append(Paragraph(f"<b>Ringkasan Kondisi:</b> {summary_text}", body_style))
    elements.append(Spacer(1, 5))

    elements.append(Paragraph("<b>Fakta Terukur Berdasarkan Data Sensor:</b>", body_bold))
    for f in recom.get('facts', []):
        elements.append(Paragraph(f"• {f}", bullet_style))
    elements.append(Spacer(1, 5))

    elements.append(Paragraph("<b>Rekomendasi Tindakan Budidaya:</b>", body_bold))
    for a in recom.get('actions', []):
        elements.append(Paragraph(f"✔ {a}", bullet_style))
    elements.append(Spacer(1, 8))

    # Catatan Metodologi & Batasan Laporan
    disclaimer_text = recom.get('disclaimer', '')
    elements.append(Paragraph(f"<i>{disclaimer_text}</i>", disclaimer_style))

    doc.build(elements)


if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Penggunaan: python generate_recap_pdf.py <input_json_path> <output_pdf_path>")
        sys.exit(1)

    json_path = sys.argv[1]
    pdf_path = sys.argv[2]

    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        build_recap_pdf(data, pdf_path)
        print(f"PDF berhasil dibuat: {pdf_path}")
        sys.exit(0)
    except Exception as e:
        print(f"Error saat membuat PDF: {e}", file=sys.stderr)
        sys.exit(1)
