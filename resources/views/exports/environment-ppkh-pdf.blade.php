@extends('exports.layout', ['title' => 'Laporan Pemantauan Lingkungan PPKH'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 20%;">Monitoring Lokasi</th>
            <th style="width: 45%;">Aktivitas</th>
            <th style="width: 15%;" class="text-center">Tanggal</th>
            <th style="width: 15%;" class="text-center">Lampiran</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->monitoring_lokasi }}</td>
                <td>{{ $item->activity_text }}</td>
                <td class="text-center">{{ $item->created_at ? $item->created_at->format('d/m/Y') : ($item->sampling_date ? $item->sampling_date->format('d/m/Y') : '-') }}</td>
                <td class="text-center">{{ count($item->attachment_list) }} Dokumen</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pemantauan PPKH.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

