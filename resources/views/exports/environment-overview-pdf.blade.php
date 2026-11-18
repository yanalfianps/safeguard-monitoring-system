@extends('exports.layout', ['title' => "Laporan Ringkasan Eksekutif Lingkungan (Environment) {$year}"])

@section('content')
<div style="margin-bottom: 15px;">
    <h3 style="font-size: 11pt; color: #087c68; margin: 0 0 8px 0; border-bottom: 1.5px solid #087c68; padding-bottom: 4px; text-transform: uppercase;">
        1. Ringkasan Kinerja &amp; Indikator Lingkungan (Tahun {{ $year }})
    </h3>
    <table class="data-table" style="margin-top: 4px; margin-bottom: 14px;">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 30%;">Submenu / Program Lingkungan</th>
                <th style="width: 25%;" class="text-right">Total Akumulasi</th>
                <th style="width: 40%;">Keterangan &amp; Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-bold">Pengelolaan Limbah B3 (LB3)</td>
                <td class="text-right text-bold" style="color: #dc2626;">{{ number_format($totalLb3Weight, 2, ',', '.') }} ({{ $totalLb3Count }} Catatan)</td>
                <td>Pencatatan limbah bahan berbahaya dan beracun yang masuk TPS LB3</td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td class="text-bold">Pengelolaan Limbah Non-B3</td>
                <td class="text-right text-bold" style="color: #16a34a;">{{ number_format($totalNonLb3Weight, 2, ',', '.') }} ({{ $totalNonLb3Count }} Catatan)</td>
                <td>Pencatatan limbah padat non-B3 dan status pengangkutan</td>
            </tr>
            <tr>
                <td class="text-center">3</td>
                <td class="text-bold">Pemantauan Lingkungan RKL–RPL</td>
                <td class="text-right text-bold">{{ $totalRklRplCount }} Pengukuran</td>
                <td>Mencakup {{ $parametersCount }} parameter pemantauan berkala</td>
            </tr>
            <tr>
                <td class="text-center">4</td>
                <td class="text-bold">Inspeksi Lingkungan</td>
                <td class="text-right text-bold" style="color: #0f766e;">{{ $totalInspeksiCount }} Catatan</td>
                <td>Pemantauan kondisi Spill Kit, Tempat Sampah, TPS LB3, dan PPKH/Lahan</td>
            </tr>
            <tr>
                <td class="text-center">5</td>
                <td class="text-bold">Konsumsi Energi BBM</td>
                <td class="text-right text-bold" style="color: #0f766e;">{{ number_format($totalBbmYear, 2, ',', '.') }} Liter</td>
                <td>Total konsumsi BBM selama tahun {{ $year }}</td>
            </tr>
            <tr>
                <td class="text-center">6</td>
                <td class="text-bold">Konsumsi Energi Listrik</td>
                <td class="text-right text-bold" style="color: #0284c7;">{{ number_format($totalListrikYear, 2, ',', '.') }} kWh</td>
                <td>Total konsumsi daya listrik selama tahun {{ $year }}</td>
            </tr>
            <tr>
                <td class="text-center">7</td>
                <td class="text-bold">Early Warning System (EWS)</td>
                <td class="text-right text-bold">{{ $totalEwsCount }} Log Pencatatan</td>
                <td>Log kesiapsiagaan dan inspeksi sensor / titik peringatan dini</td>
            </tr>
            <tr>
                <td class="text-center">8</td>
                <td class="text-bold">Pencatatan Parameter Cuaca</td>
                <td class="text-right text-bold" style="color: #0284c7;">{{ $totalWeatherCount }} Data Pengukuran</td>
                <td>Monitoring kecepatan angin (m/s), curah hujan (mm/hr), temperatur (°C), humidity (%)</td>
            </tr>
        </tbody>
    </table>
</div>

<div style="margin-bottom: 15px;">
    <h3 style="font-size: 11pt; color: #087c68; margin: 0 0 8px 0; border-bottom: 1.5px solid #087c68; padding-bottom: 4px; text-transform: uppercase;">
        2. Rekapitulasi Pemakaian Energi Bulanan (Tahun {{ $year }})
    </h3>
    <table class="data-table" style="margin-top: 4px; margin-bottom: 14px;">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">Bulan</th>
                <th style="width: 32%;">Nama Periode</th>
                <th style="width: 30%;" class="text-right">Pemakaian BBM (Liter)</th>
                <th style="width: 30%;" class="text-right">Pemakaian Listrik (kWh)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $monthNames = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
            @endphp
            @for($m = 1; $m <= 12; $m++)
                <tr>
                    <td class="text-center">{{ sprintf('%02d', $m) }}</td>
                    <td class="text-bold">{{ $monthNames[$m] }} {{ $year }}</td>
                    <td class="text-right">{{ number_format($bbmMonthly[$m] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($listrikMonthly[$m] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="2" class="text-right text-bold">Total Akumulasi {{ $year }}:</td>
                <td class="text-right text-bold" style="color: #0f766e;">{{ number_format($totalBbmYear, 2, ',', '.') }} Liter</td>
                <td class="text-right text-bold" style="color: #0284c7;">{{ number_format($totalListrikYear, 2, ',', '.') }} kWh</td>
            </tr>
        </tfoot>
    </table>
</div>

@if(isset($ewsByLocation) && $ewsByLocation->count() > 0)
<div>
    <h3 style="font-size: 11pt; color: #087c68; margin: 0 0 8px 0; border-bottom: 1.5px solid #087c68; padding-bottom: 4px; text-transform: uppercase;">
        3. Sebaran Pemantauan EWS Berdasarkan Lokasi
    </h3>
    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">No</th>
                <th style="width: 60%;">Lokasi / Titik Sensor EWS</th>
                <th style="width: 32%;" class="text-right">Frekuensi Pencatatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ewsByLocation as $idx => $loc)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-bold">{{ $loc->location_name ?: 'Tidak Disebutkan' }}</td>
                    <td class="text-right text-bold">{{ $loc->total }} Kali</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

