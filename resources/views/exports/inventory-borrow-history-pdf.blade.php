@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 11%;">Kode Peminjaman</th>
            <th style="width: 16%;">Peminjam & Unit/Divisi</th>
            <th style="width: 17%;">Barang & Lokasi</th>
            <th style="width: 8%;" class="text-right">Jumlah Dipinjam</th>
            <th style="width: 8%;" class="text-right">Jumlah Dikembalikan</th>
            <th style="width: 13%;" class="text-center">Tanggal Peminjaman & Pengembalian</th>
            <th style="width: 23%;">Riwayat Pengembalian Bertahap</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $bh)
            @php
                $satuan = $bh->item->satuan ?? 'Box';
                $lastReturn = $bh->last_return_date;
                $timeline = $bh->chronological_timeline;
                $returnSteps = array_slice($timeline, 1);
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $bh->borrow_code }}</td>
                <td>
                    <div class="text-bold">{{ $bh->borrower_name }}</div>
                    <div style="font-size: 7pt; color: #475569;">{{ $bh->department ?: '-' }}</div>
                </td>
                <td>
                    <div class="text-bold">{{ $bh->item->nama_barang ?? $bh->item->item_name ?? 'Barang Dihapus' }}</div>
                    <div style="font-size: 7pt; color: #64748b;">
                        <span style="font-family: monospace;">{{ $bh->item->kode_barang ?? '-' }}</span> &bull; {{ $bh->location ?: 'Warehouse' }}
                    </div>
                </td>
                <td class="text-right">{{ number_format($bh->quantity_borrowed) }} {{ $satuan }}</td>
                <td class="text-right text-bold">{{ number_format($bh->quantity_returned) }} {{ $satuan }}</td>
                <td class="text-center">
                    <div><strong>Pinjam:</strong> {{ $bh->borrow_date ? $bh->borrow_date->format('d/m/Y') : '-' }}</div>
                    <div><strong>Selesai:</strong> {{ $lastReturn ? $lastReturn->format('d/m/Y H:i') : '-' }}</div>
                </td>
                <td>
                    @if(count($returnSteps) > 0)
                        @foreach($returnSteps as $step)
                            <div style="font-size: 7pt; margin-bottom: 2px;">
                                <strong>{{ $step['keterangan'] }}:</strong> {{ $step['jumlah'] }} {{ $satuan }}
                                ({{ $step['tanggal'] }} &bull; {{ $step['lokasi'] ?? 'Warehouse' }})
                            </div>
                        @endforeach
                    @else
                        <span style="font-size: 7pt; color: #64748b;">Dikembalikan penuh ({{ $bh->quantity_returned }} {{ $satuan }})</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada riwayat peminjaman selesai yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

