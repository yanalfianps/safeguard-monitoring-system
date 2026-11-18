@extends('exports.layout', ['title' => "Laporan Pemakaian Energi - " . strtoupper($type) . " ({$year})"])

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%;" class="text-center">No</th>
            <th style="width: 25%;">Bulan</th>
            <th style="width: 15%;">Tahun</th>
            <th style="width: 20%;" class="text-right">Jumlah Pemakaian</th>
            <th style="width: 12%;">Satuan</th>
            <th style="width: 20%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">{{ $item->month_name }}</td>
                <td>{{ $item->year }}</td>
                <td class="text-right text-bold">{{ number_format($item->usage_amount, 2, ',', '.') }}</td>
                <td>{{ $item->unit }}</td>
                <td>{{ $item->description ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pemakaian energi untuk periode ini.
                </td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="text-right text-bold">Total Pemakaian:</td>
            <td class="text-right text-bold" style="color: #087c68;">{{ number_format($totalUsage, 2, ',', '.') }}</td>
            <td class="text-bold">{{ $type === 'bbm' ? 'Liter' : 'kWh' }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endsection

