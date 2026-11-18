@extends('exports.layout', ['title' => 'Laporan Rekapitulasi Inspeksi Lingkungan'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 12%;" class="text-center">Tanggal</th>
            <th style="width: 18%;">Jenis Inspeksi</th>
            <th style="width: 18%;">Lokasi</th>
            <th style="width: 32%;">Kondisi Hasil Inspeksi</th>
            <th style="width: 15%;" class="text-center">Lampiran</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $item->formatted_date }}</td>
                <td class="text-bold">{{ $item->inspection_type_label }}</td>
                <td>{{ $item->location_label }}</td>
                <td>{{ $item->condition_text }}</td>
                <td class="text-center">{{ count($item->attachment_list) }} Dokumen</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data catatan inspeksi lingkungan.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

