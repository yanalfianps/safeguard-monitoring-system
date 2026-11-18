@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 11%;">Kode Transaksi</th>
            <th style="width: 10%;" class="text-center">Tanggal</th>
            <th style="width: 20%;">Nama & Kode Barang</th>
            <th style="width: 12%;">Kategori</th>
            <th style="width: 9%;" class="text-center">Jenis</th>
            <th style="width: 10%;">Lokasi</th>
            <th style="width: 6%;" class="text-center">Satuan</th>
            <th style="width: 6%;" class="text-right">Kuantitas</th>
            <th style="width: 10%;">PIC / Petugas</th>
            <th style="width: 12%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $t)
            @php
                $jenis = $t->transaction_type ?? $t->jenis_transaksi ?? '-';
                $badgeClass = match(strtolower($jenis)) {
                    'masuk' => 'badge-success',
                    'keluar' => 'badge-warning',
                    default => 'badge-secondary',
                };
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace;">{{ $t->transaction_code ?? ('TRX-' . $t->id) }}</td>
                <td class="text-center">{{ $t->tanggal ? $t->tanggal->format('d/m/Y') : ($t->transaction_date ? $t->transaction_date->format('d/m/Y') : '-') }}</td>
                <td class="text-bold">
                    {{ $t->inventory?->nama_barang ?? $t->inventory?->item_name ?? 'Barang Dihapus' }}
                    <div style="font-family: monospace; font-size: 7pt; color: #64748b;">{{ $t->inventory?->kode_barang ?? $t->inventory?->item_code ?? '-' }}</div>
                </td>
                <td>{{ $t->inventory?->kategori ?? $t->inventory?->category ?? '-' }}</td>
                <td class="text-center"><span class="badge {{ $badgeClass }}">{{ ucfirst($jenis) }}</span></td>
                <td>{{ $t->location ?? 'Warehouse' }}</td>
                <td class="text-center">{{ $t->satuan ?? ($t->inventory?->satuan ?? 'Box') }}</td>
                <td class="text-right text-bold">{{ number_format($t->jumlah ?? $t->quantity ?? 0) }}</td>
                <td>{{ $t->petugas ?? $t->person_in_charge ?? '-' }}</td>
                <td>
                    {{ $t->keterangan ?? $t->notes ?? '-' }}
                    @if($t->expired_date)
                        <div style="font-size: 7pt; color: #b45309;">Exp: {{ $t->expired_date->format('d/m/Y') }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada riwayat transaksi barang yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
