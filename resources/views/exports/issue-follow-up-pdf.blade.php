@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No.</th>
            <th style="width: 13%;">Fungsi</th>
            <th style="width: 22%;">Judul Temuan</th>
            <th style="width: 14%;">Lokasi</th>
            <th style="width: 10%;" class="text-center">Tanggal</th>
            <th style="width: 18%;">Deskripsi</th>
            <th style="width: 10%;">Sumber</th>
            <th style="width: 8%;" class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            @php
                $isClosed = in_array(strtolower((string) $item->status), ['closed', 'close'], true);
                $statClass = $isClosed ? 'badge-success' : 'badge-danger';
                $statText = $isClosed ? 'Closed' : 'Open';
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->division_label ?? $item->hsse_division ?? '-' }}</td>
                <td class="text-bold">{{ $item->title }}</td>
                <td>{{ $item->location ?: '-' }}</td>
                <td class="text-center">{{ $item->reported_date ? $item->reported_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->category ?: '-' }}</td>
                <td class="text-center">
                    <span class="badge {{ $statClass }}">{{ $statText }}</span>
                    @if($isClosed && $item->closed_date)
                        <div style="font-size: 8.5px; color: #475569; margin-top: 2px;">{{ $item->closed_date->format('d/m/Y') }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data Issue &amp; Follow Up yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
