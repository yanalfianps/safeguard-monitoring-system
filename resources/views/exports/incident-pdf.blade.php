@extends('exports.layout')

@section('content')
@if(!empty($filterInfo))
<table class="meta-table" style="margin-top: -6px; margin-bottom: 10px;">
    <tr>
        <td class="meta-label">Klasifikasi Area:</td>
        <td class="meta-value">{{ $filterInfo['Klasifikasi Area'] ?? 'Semua' }}</td>
        <td class="meta-label">Kategori Umum:</td>
        <td class="meta-value">{{ $filterInfo['Kategori Umum'] ?? 'Semua' }}</td>
    </tr>
    <tr>
        <td class="meta-label">Kategori EBTKE:</td>
        <td class="meta-value">{{ $filterInfo['Kategori EBTKE'] ?? 'Semua' }}</td>
        <td class="meta-label">Pencarian:</td>
        <td class="meta-value">{{ $filterInfo['Pencarian'] ?? '-' }}</td>
    </tr>
</table>
@endif

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 12%;">No. Laporan</th>
            <th style="width: 12%;" class="text-center">Tanggal/Waktu</th>
            <th style="width: 26%;">Judul Insiden</th>
            <th style="width: 15%;">Unit / Lokasi</th>
            <th style="width: 18%;">Kategori</th>
            <th style="width: 5%;" class="text-center">LWD</th>
            <th style="width: 8%;" class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            @php
                $rawStatus = $item->status_laporan ?? $item->status ?? 'Preliminary';
                $statClass = match($rawStatus) {
                    'Final', 'Selesai', 'Closed' => 'badge-success',
                    'Preliminary', 'On Progress', 'Dalam Investigasi', 'Investigasi' => 'badge-warning',
                    default => 'badge-danger',
                };
                $katUmum = is_array($item->kategori_umum) ? implode(', ', $item->kategori_umum) : ($item->kategori_umum ?? '');
                $katEbtke = is_array($item->kategori_ebtke) ? implode(', ', $item->kategori_ebtke) : ($item->kategori_ebtke ?? '');
                $katCombined = trim(implode(' | ', array_filter([$katUmum, $katEbtke]))) ?: '-';
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold; color: #b91c1c;">
                    {{ $item->nomor_insiden ?? $item->incident_code ?? '-' }}
                    <div style="font-family: sans-serif; font-size: 6.5pt; color: #64748b; font-weight: normal; margin-top: 2px;">
                        {{ $item->klasifikasi_area ?? 'Kecelakaan Panas Bumi' }}
                    </div>
                </td>
                <td class="text-center">
                    @if($item->hari)
                        <div style="font-size: 7pt; color: #475569; font-weight: bold;">{{ $item->hari }}</div>
                    @endif
                    {{ $item->tanggal_waktu ? $item->tanggal_waktu->format('d/m/Y H:i') : ($item->incident_date ? $item->incident_date->format('d/m/Y H:i') : '-') }}
                </td>
                <td>
                    <div class="text-bold">{{ $item->title ?? '-' }}</div>
                    @if(!empty($item->penanggung_jawab))
                        <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">PIC: {{ $item->penanggung_jawab }}</div>
                    @endif
                </td>
                <td>{{ $item->komponen_terkait ?? $item->unit ?? 'Unit Dieng' }} - {{ $item->lokasi_kejadian ?? $item->location ?? '-' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($katCombined, 45) }}</td>
                <td class="text-center text-bold">{{ $item->jumlah_hari_hilang ?? 0 }}</td>
                <td class="text-center"><span class="badge {{ $statClass }}">{{ $rawStatus }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data insiden yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
