@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 10%;">Kode Barang</th>
            <th style="width: 20%;">Nama Barang</th>
            <th style="width: 15%;">Kategori</th>
            <th style="width: 7%;" class="text-center">Satuan</th>
            <th style="width: 9%;" class="text-right">Stok Warehouse</th>
            <th style="width: 9%;" class="text-right">Stok Gudang HSSE</th>
            <th style="width: 8%;" class="text-right">Total Stok</th>
            <th style="width: 10%;" class="text-center">Tanggal Expired</th>
            <th style="width: 8%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            @php
                $wh = (int) $item->stock_warehouse;
                $hsse = (int) $item->stock_hsse;
                $total = $wh + $hsse;
                $batchExpiries = $item->relationLoaded('transactions') && $item->transactions
                    ? $item->transactions->pluck('expired_date')->filter()->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m/Y'))->unique()->values()
                    : collect();
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $item->kode_barang ?? $item->item_code ?? '-' }}</td>
                <td class="text-bold">{{ $item->nama_barang ?? $item->item_name ?? '-' }}</td>
                <td>{{ $item->kategori ?? $item->category ?? '-' }}</td>
                <td class="text-center">{{ $item->satuan ?? $item->unit_measure ?? 'Box' }}</td>
                <td class="text-right">{{ number_format($wh) }}</td>
                <td class="text-right">{{ number_format($hsse) }}</td>
                <td class="text-right text-bold">{{ number_format($total) }}</td>
                <td class="text-center">
                    @if($item->expired_date)
                        {{ $item->expired_date->format('d/m/Y') }}
                    @elseif($batchExpiries->isNotEmpty())
                        {{ $batchExpiries->first() }}
                    @else
                        -
                    @endif
                    @if($batchExpiries->count() > 1)
                        <div style="font-size: 6.5pt; color: #64748b;">Batch: {{ $batchExpiries->implode(', ') }}</div>
                    @endif
                </td>
                <td>{{ $item->keterangan ?? $item->description ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data master barang yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

