@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 11%;">Kode Peminjaman</th>
            <th style="width: 18%;">Peminjam & Unit/Divisi</th>
            <th style="width: 18%;">Nama Barang</th>
            <th style="width: 9%;">Lokasi</th>
            <th style="width: 7%;" class="text-right">Dipinjam</th>
            <th style="width: 8%;" class="text-right">Dikembalikan</th>
            <th style="width: 8%;" class="text-right">Masih Dipinjam</th>
            <th style="width: 9%;" class="text-center">Rencana Kembali</th>
            <th style="width: 8%;" class="text-center">Status Tenggat</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $b)
            @php
                $satuan = $b->item->satuan ?? 'Box';
                $status = $b->display_status;
                $badgeClass = match($status) {
                    'Melewati Tenggat' => 'badge-danger',
                    'Sudah Dikembalikan' => 'badge-success',
                    default => 'badge-info',
                };
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $b->borrow_code }}</td>
                <td>
                    <div class="text-bold">{{ $b->borrower_name }}</div>
                    <div style="font-size: 7pt; color: #475569;">{{ $b->department ?: '-' }}</div>
                </td>
                <td>
                    <div class="text-bold">{{ $b->item->nama_barang ?? $b->item->item_name ?? 'Barang Dihapus' }}</div>
                    <div style="font-family: monospace; font-size: 7pt; color: #64748b;">{{ $b->item->kode_barang ?? '-' }}</div>
                </td>
                <td>{{ $b->location ?: 'Warehouse' }}</td>
                <td class="text-right">{{ number_format($b->quantity_borrowed) }} {{ $satuan }}</td>
                <td class="text-right">{{ number_format($b->quantity_returned) }} {{ $satuan }}</td>
                <td class="text-right text-bold">{{ number_format($b->remaining_quantity) }} {{ $satuan }}</td>
                <td class="text-center">
                    {{ $b->expected_return_date ? $b->expected_return_date->format('d/m/Y') : '-' }}
                    <div style="font-size: 6.5pt; color: #64748b;">Pinjam: {{ $b->borrow_date ? $b->borrow_date->format('d/m/Y') : '-' }}</div>
                </td>
                <td class="text-center">
                    <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data barang terpinjam yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

