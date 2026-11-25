@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 9%;" class="text-center">Tanggal</th>
            <th style="width: 12%;">Well Pad</th>
            <th style="width: 9%;" class="text-right">CO (ppm)</th>
            <th style="width: 9%;" class="text-right">O₂ (%)</th>
            <th style="width: 9%;" class="text-right">H₂S (ppm)</th>
            <th style="width: 9%;" class="text-right">LEL (%)</th>
            <th style="width: 9%;" class="text-right">Press (Psi)</th>
            <th style="width: 10%;" class="text-center">Status</th>
            <th style="width: 10%;">Petugas</th>
            <th style="width: 10%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $item->measurement_date ? $item->measurement_date->format('d/m/Y') : '-' }}</td>
                <td class="text-bold">{{ $item->well_pad }}</td>
                <td class="text-right">{{ $item->co_level !== null ? rtrim(rtrim(number_format($item->co_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                <td class="text-right">{{ $item->o2_level !== null ? rtrim(rtrim(number_format($item->o2_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                <td class="text-right">{{ $item->h2s_level !== null ? rtrim(rtrim(number_format($item->h2s_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                <td class="text-right">{{ $item->lel_level !== null ? rtrim(rtrim(number_format($item->lel_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                <td class="text-right">{{ $item->pressure !== null ? rtrim(rtrim(number_format($item->pressure, 1, '.', ','), '0'), '.') : '-' }}</td>
                <td class="text-center">
                    @if(($item->overall_status ?? '') === 'Tidak Sesuai')
                        <span class="badge badge-danger">Tidak Sesuai</span>
                    @elseif(($item->overall_status ?? '') === 'Sesuai')
                        <span class="badge badge-success">Sesuai</span>
                    @else
                        <span class="badge badge-secondary">-</span>
                    @endif
                </td>
                <td>{{ $item->recorded_by ?? '-' }}</td>
                <td>{{ $item->formatted_notes ?? ($item->notes ?? '-') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data pemantauan gas yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection