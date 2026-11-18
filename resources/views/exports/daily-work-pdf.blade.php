@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 28%;">Nama Pekerjaan</th>
            <th style="width: 16%;">Perusahaan</th>
            <th style="width: 15%;">SIKA</th>
            <th style="width: 10%;" class="text-center">Tgl Mulai</th>
            <th style="width: 10%;" class="text-center">Tgl Selesai</th>
            <th style="width: 16%;">Lokasi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-bold">
                    {{ $item->name }}
                    @if(!empty($item->description))
                        <div style="font-weight: normal; font-size: 7.5pt; color: #64748b; margin-top: 2px;">
                            {{ $item->description }}
                        </div>
                    @endif
                </td>
                <td>
                    {{ $item->unit }}
                    @if($item->parent_company_name)
                        <div style="font-size: 7pt; color: #64748b; margin-top: 1px;">({{ $item->parent_company_name }})</div>
                    @endif
                </td>
                <td>{{ $item->sikka ?? '-' }}</td>
                <td class="text-center">{{ $item->start_date ? $item->start_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $item->location ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pekerjaan harian yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
