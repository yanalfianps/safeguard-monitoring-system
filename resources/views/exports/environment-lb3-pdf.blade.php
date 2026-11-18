@extends('exports.layout', ['title' => 'Laporan Pengelolaan Limbah B3 (LB3)'])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 25%;">Nama Limbah</th>
            <th style="width: 12%;" class="text-right">Jumlah</th>
            <th style="width: 10%;">Satuan</th>
            <th style="width: 12%;" class="text-center">Tgl Masuk</th>
            <th style="width: 20%;">Karakteristik</th>
            <th style="width: 16%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->waste_name }}</td>
                <td class="text-right" style="font-weight: bold; color: #dc2626;">{{ number_format($item->amount, 2, ',', '.') }}</td>
                <td>{{ $item->unit_label }}</td>
                <td class="text-center">{{ $item->entry_date ? $item->entry_date->format('d/m/Y') : '-' }}</td>
                <td>{{ implode(', ', $item->characteristics_list) ?: '-' }}</td>
                <td>{{ $item->description ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data limbah B3.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

