<div class="table-responsive">
    <table class="table table-gas align-middle" id="historyGasMonitoringTable">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 14%;">Well Pad</th>
                <th class="text-center" style="width: 10%;">CO (ppm)</th>
                <th class="text-center" style="width: 10%;">O₂ (%)</th>
                <th class="text-center" style="width: 10%;">H₂S (ppm)</th>
                <th class="text-center" style="width: 10%;">LEL (%)</th>
                <th class="text-center" style="width: 11%;">Pressure (Psi)</th>
                <th class="text-center" style="width: 10%;">Status</th>
                <th class="text-center" style="width: 10%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($historyTableRecords as $index => $row)
                @php
                    $coExceeded = ($row->co_level !== null && $row->co_level > 25.0);
                    $o2Exceeded = ($row->o2_level !== null && ($row->o2_level < 19.5 || $row->o2_level > 23.5));
                    $h2sExceeded = ($row->h2s_level !== null && $row->h2s_level > 10.0);
                    $lelExceeded = ($row->lel_level !== null && $row->lel_level > 5.0);
                    $isRowExceeded = $coExceeded || $o2Exceeded || $h2sExceeded || $lelExceeded;
                    $hasAnyMeasured = ($row->co_level !== null || $row->o2_level !== null || $row->h2s_level !== null || $row->lel_level !== null);
                @endphp
                <tr style="{{ $isRowExceeded ? 'background-color: #fef2f2;' : '' }}">
                    <td class="text-center text-muted fw-semibold">{{ $historyTableRecords->firstItem() + $index }}</td>
                    <td class="text-nowrap text-dark">{{ $row->measurement_date ? $row->measurement_date->format('d/m/Y') : '-' }}</td>
                    <td class="fw-bold text-dark">{{ $row->well_pad }}</td>
                    <td class="text-center fw-semibold {{ $coExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->co_level !== null ? rtrim(rtrim(number_format($row->co_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                    <td class="text-center fw-semibold {{ $o2Exceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->o2_level !== null ? rtrim(rtrim(number_format($row->o2_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                    <td class="text-center fw-semibold {{ $h2sExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->h2s_level !== null ? rtrim(rtrim(number_format($row->h2s_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                    <td class="text-center fw-semibold {{ $lelExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->lel_level !== null ? rtrim(rtrim(number_format($row->lel_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                    <td class="text-center fw-semibold">{{ $row->pressure !== null ? rtrim(rtrim(number_format($row->pressure, 1, '.', ','), '0'), '.') : '-' }}</td>
                    <td class="text-center">
                        @if($isRowExceeded)
                            <span class="badge-tidaksesuai">Tidak Sesuai</span>
                        @elseif($hasAnyMeasured)
                            <span class="badge-sesuai">Sesuai</span>
                        @else
                            <span class="badge bg-light text-muted border">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="btn-tbl-action btn-tbl-view" onclick="viewMeasurementDetail({{ $row->id }})" title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            @adminManagement
                                <button type="button" class="btn-tbl-action btn-tbl-edit" onclick="editSingleMeasurement({{ $row->id }})" title="Edit Data">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn-tbl-action btn-tbl-del" onclick="deleteMeasurementRecord({{ $row->id }}, '{{ $row->well_pad }}')" title="Hapus Data">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endadminManagement
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                        Tidak ada data riwayat Gas Monitoring sesuai filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($historyTableRecords->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
        <div class="small text-muted">
            Menampilkan {{ $historyTableRecords->firstItem() }} sampai {{ $historyTableRecords->lastItem() }} dari {{ $historyTableRecords->total() }} data
        </div>
        <div>
            {{ $historyTableRecords->links() }}
        </div>
    </div>
@endif
