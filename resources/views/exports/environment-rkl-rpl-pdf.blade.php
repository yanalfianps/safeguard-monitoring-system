@extends('exports.layout', ['title' => 'Laporan Pemantauan Lingkungan RKL–RPL'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 20%;">Parameter Pemantauan</th>
            <th style="width: 20%;">Lokasi Pengamatan</th>
            <th style="width: 12%;" class="text-center">Tgl Sampling</th>
            <th style="width: 13%;" class="text-center">Hasil / Nilai</th>
            <th style="width: 10%;">Satuan</th>
            <th style="width: 20%;">Catatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->parameter_label }}</td>
                <td>{{ $item->location_label }}</td>
                <td class="text-center">{{ $item->sampling_date ? $item->sampling_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center text-bold">{{ $item->measurement_value }}</td>
                <td>{{ $item->unit_label ?: '-' }}</td>
                <td>{{ $item->description ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pemantauan RKL–RPL.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

