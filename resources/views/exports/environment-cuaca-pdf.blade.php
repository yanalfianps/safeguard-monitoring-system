@extends('exports.layout', ['title' => 'Laporan Rekapitulasi Parameter Cuaca Lingkungan'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 12%;">Hari</th>
            <th style="width: 18%;" class="text-center">Waktu &amp; Tanggal</th>
            <th style="width: 14%;" class="text-right">Angin (m/s)</th>
            <th style="width: 14%;" class="text-right">Hujan (mm/hr)</th>
            <th style="width: 10%;" class="text-right">Suhu (°C)</th>
            <th style="width: 10%;" class="text-right">Humidity (%)</th>
            <th style="width: 17%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->day_name }}</td>
                <td class="text-center">{{ $item->formatted_date }}<br><small style="color: #64748b;">{{ $item->formatted_time }} WIB</small></td>
                <td class="text-right">{{ number_format($item->wind_speed, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->rainfall, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->temperature, 1, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->humidity, 1, ',', '.') }}</td>
                <td>{{ $item->notes ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pencatatan parameter cuaca.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

