@extends('exports.layout', ['title' => 'Laporan Pengelolaan Limbah Non-B3'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 22%;">Nama Limbah</th>
            <th style="width: 15%;">Sumber</th>
            <th style="width: 10%;" class="text-right">Jumlah</th>
            <th style="width: 8%;">Satuan</th>
            <th style="width: 10%;" class="text-center">Tgl Masuk</th>
            <th style="width: 10%;" class="text-center">Tgl Angkut</th>
            <th style="width: 10%;" class="text-center">Status</th>
            <th style="width: 10%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->waste_name }}</td>
                <td>{{ $item->source_label }}</td>
                <td class="text-right" style="font-weight: bold; color: #16a34a;">{{ number_format($item->amount, 2, ',', '.') }}</td>
                <td>{{ $item->unit_label }}</td>
                <td class="text-center">{{ $item->entry_date ? $item->entry_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $item->transport_date ? $item->transport_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $item->transport_date ? 'Diangkut' : 'Belum' }}</td>
                <td>{{ $item->description ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data limbah Non-B3.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

