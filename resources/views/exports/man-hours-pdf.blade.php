@extends('exports.layout')

@section('content')
@php
    $headers = !empty($companyHeaders) && is_array($companyHeaders)
        ? $companyHeaders
        : ['GDE (Only)', 'KODIPA', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'TDP', 'PJSE', 'Rentokil', 'PMC', 'Bhumi Phala'];
@endphp
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 3%;" class="text-center">No</th>
            <th style="width: 8%;" class="text-center">Tanggal</th>
            @foreach($headers as $cHeader)
                <th class="text-center">{{ strtoupper($cHeader) }}</th>
            @endforeach
            <th style="width: 8%;" class="text-right">Total Pekerja</th>
            <th style="width: 7%;" class="text-right">Jam Kerja</th>
            <th style="width: 10%;" class="text-right">Total Man Hours</th>
        </tr>
    </thead>
    <tbody>
        @forelse($historyRows as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center text-bold">{{ $row['date_formatted'] }}</td>
                @foreach($headers as $cHeader)
                    <td class="text-center">{{ $row['units'][$cHeader] ?? 0 }}</td>
                @endforeach
                <td class="text-right text-bold">{{ number_format($row['total_workers']) }} Org</td>
                <td class="text-right">{{ number_format($row['hours_per_worker'], 1) }} Jam</td>
                <td class="text-right text-bold" style="color: #087c68;">{{ number_format($row['total_man_hours']) }} Jam</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headers) + 5 }}" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data Man Hours &amp; Manpower yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
    @if(count($historyRows ?? []) > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="{{ count($headers) + 2 }}" class="text-right" style="padding: 6px;">TOTAL KESELURUHAN (FILTERED):</td>
                <td class="text-right" style="padding: 6px;">{{ number_format($totalWorkers) }} Org</td>
                <td class="text-right" style="padding: 6px;">-</td>
                <td class="text-right" style="padding: 6px; color: #087c68;">{{ number_format($totalManHours) }} Jam</td>
            </tr>
        </tfoot>
    @endif
</table>
@endsection
