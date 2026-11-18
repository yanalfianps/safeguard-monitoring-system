@extends('exports.layout', ['title' => 'Laporan Early Warning System (EWS)'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 15%;">Lokasi</th>
            <th style="width: 15%;" class="text-center">Tgl Pencatatan</th>
            <th style="width: 35%;">Kondisi Aktual</th>
            <th style="width: 15%;">Keterangan</th>
            <th style="width: 15%;">Pencatat</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->location_label }}</td>
                <td class="text-center">{{ $item->recording_date ? $item->recording_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $item->condition }}</td>
                <td>{{ $item->description ?? '-' }}</td>
                <td>{{ $item->recorder?->name ?? 'Sistem' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pencatatan Early Warning System (EWS).
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

