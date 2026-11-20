@extends('exports.layout')

@section('content')
<style>
    .summary-box-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    .summary-box-table td {
        padding: 6px 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        text-align: center;
        vertical-align: middle;
    }
    .summary-num {
        font-size: 13pt;
        font-weight: bold;
        line-height: 1.1;
        margin-bottom: 2px;
    }
    .summary-label {
        font-size: 7.5pt;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .section-header {
        margin: 16px 0 8px 0;
        padding-bottom: 4px;
        border-bottom: 1.5px solid #087c68;
    }
    .section-title {
        font-size: 10.5pt;
        font-weight: bold;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .section-desc {
        font-size: 7.5pt;
        color: #64748b;
        margin-top: 2px;
    }
    .photo-grid-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 10px;
        margin-top: 4px;
    }
    .photo-grid-table td {
        width: 50%;
        vertical-align: top;
        padding: 0;
    }
    .photo-card {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        padding: 8px;
        page-break-inside: avoid;
    }
    .photo-box {
        width: 100%;
        height: 175px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        text-align: center;
        vertical-align: middle;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .photo-box img {
        max-width: 100%;
        max-height: 175px;
        display: block;
        margin: 0 auto;
    }
    .photo-placeholder {
        width: 100%;
        height: 100px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 4px;
        text-align: center;
        display: table;
        margin-bottom: 6px;
    }
    .photo-placeholder-inner {
        display: table-cell;
        vertical-align: middle;
        color: #94a3b8;
        font-size: 8pt;
        font-style: italic;
    }
    .photo-caption-title {
        font-size: 8.5pt;
        font-weight: bold;
        color: #0f172a;
        line-height: 1.25;
        margin-bottom: 3px;
    }
    .photo-caption-meta {
        font-size: 7.5pt;
        color: #64748b;
        line-height: 1.2;
    }
</style>

<!-- 1. EXECUTIVE SUMMARY STATS -->
<table class="summary-box-table">
    <tr>
        <td style="width: 25%;">
            <div class="summary-num" style="color: #0f172a;">{{ $totalRecords }}</div>
            <div class="summary-label">Total Kegiatan</div>
        </td>
        <td style="width: 25%;">
            <div class="summary-num" style="color: #166534;">{{ $selesaiCount }}</div>
            <div class="summary-label">Status Selesai</div>
        </td>
        <td style="width: 25%;">
            <div class="summary-num" style="color: #92400e;">{{ $berlangsungCount + $direncanakanCount }}</div>
            <div class="summary-label">Berlangsung / Rencana</div>
        </td>
        <td style="width: 25%;">
            <div class="summary-num" style="color: #087c68;">{{ $photosCount }} / {{ $totalDocsCount }}</div>
            <div class="summary-label">Foto / Total Dokumen</div>
        </td>
    </tr>
</table>

<!-- 2. TABEL DAFTAR KEGIATAN -->
<div class="section-header" style="margin-top: 8px;">
    <div class="section-title">I. DAFTAR KEGIATAN & EVENTS</div>
    <div class="section-desc">Ringkasan seluruh kegiatan HSSE sesuai filter yang diterapkan</div>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No.</th>
            <th style="width: 14%;">Fungsi</th>
            <th style="width: 26%;">Judul Kegiatan</th>
            <th style="width: 11%;" class="text-center">Tanggal</th>
            <th style="width: 17%;">Perusahaan/Instansi</th>
            <th style="width: 10%;" class="text-center">Status</th>
            <th style="width: 18%;">Dokumentasi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $item)
            @php
                $badgeClass = match($item->status) {
                    'Selesai' => 'badge-success',
                    'Berlangsung', 'Sedang Berlangsung' => 'badge-warning',
                    'Dibatalkan' => 'badge-danger',
                    default => 'badge-secondary',
                };
                $docCount = $item->documents_count ?? $item->documents->count();
                $hasPhoto = !empty($item->pdf_photo_data);
                $safeguard = $item->safeguard_function ?: 'Health & Safety';
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="color: #0f172a; font-weight: 500;">
                    {{ $safeguard }}
                </td>
                <td>
                    <div class="text-bold" style="color: #0f172a;">{{ $item->title }}</div>
                    @if($item->description)
                        <div style="font-size: 7pt; color: #64748b; margin-top: 1px;">
                            {{ \Illuminate\Support\Str::limit($item->description, 85) }}
                        </div>
                    @endif
                </td>
                <td class="text-center">
                    <div>{{ $item->formatted_date_range }}</div>
                </td>
                <td>{{ $item->company ?: $item->unit ?: '-' }}</td>
                <td class="text-center">
                    <span class="badge {{ $badgeClass }}">{{ $item->status }}</span>
                </td>
                <td>
                    @if($docCount > 0 || $hasPhoto)
                        <div style="font-weight: 600; color: #334155;">
                            {{ max($docCount, $hasPhoto ? 1 : 0) }} Berkas Lampiran
                        </div>
                        <div style="font-size: 7pt; color: {{ $hasPhoto ? '#166534' : '#64748b' }};">
                            {{ $hasPhoto ? '• Termasuk Foto Dokumentasi' : '• Dokumen Berkas' }}
                        </div>
                        @if($item->documents && $item->documents->isNotEmpty())
                            <div style="font-size: 6.5pt; color: #475569; margin-top: 2px;">
                                @foreach($item->documents->take(2) as $docItem)
                                    <div>- {{ \Illuminate\Support\Str::limit($docItem->file_name ?: $docItem->title, 28) }}</div>
                                @endforeach
                                @if($item->documents->count() > 2)
                                    <div>+{{ $item->documents->count() - 2 }} berkas lainnya</div>
                                @endif
                            </div>
                        @endif
                    @else
                        <span style="color: #94a3b8; font-size: 7.5pt; font-style: italic;">Tidak ada berkas</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 18px; color: #94a3b8;">
                    Tidak ada data kegiatan events yang sesuai dengan kriteria filter.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- 3. BAGIAN LAMPIRAN & DOKUMENTASI KEGIATAN -->
<div style="page-break-before: always;"></div>

<div class="section-header">
    <div class="section-title">II. LAMPIRAN & DOKUMENTASI KEGIATAN</div>
    <div class="section-desc">Lampiran berkas dan foto dokumentasi kegiatan HSSE</div>
</div>

@if($records->isEmpty())
    <div style="text-align: center; padding: 30px; color: #94a3b8; font-size: 8.5pt;">
        Tidak ada kegiatan yang ditampilkan pada periode ini.
    </div>
@else
    @php
        // Filter atau tampilkan seluruh kegiatan dalam grid foto (2 per baris)
        $chunkedRecords = $records->chunk(2);
    @endphp

    <table class="photo-grid-table">
        @foreach($chunkedRecords as $row)
            <tr>
                @foreach($row as $eventItem)
                    @php
                        $badgeClass = match($eventItem->status) {
                            'Selesai' => 'badge-success',
                            'Berlangsung', 'Sedang Berlangsung' => 'badge-warning',
                            'Dibatalkan' => 'badge-danger',
                            default => 'badge-secondary',
                        };
                        $safeguard = $eventItem->safeguard_function ?: 'Health & Safety';
                    @endphp
                    <td>
                        <div class="photo-card">
                            @if(!empty($eventItem->pdf_photo_data))
                                <div class="photo-box">
                                    <table style="width: 100%; height: 100%;">
                                        <tr>
                                            <td style="text-align: center; vertical-align: middle; border: none; padding: 0;">
                                                <img src="{{ $eventItem->pdf_photo_data }}" alt="{{ $eventItem->title }}">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @elseif($eventItem->documents && $eventItem->documents->isNotEmpty())
                                <div class="photo-placeholder">
                                    <div class="photo-placeholder-inner">
                                        <strong>Lampiran Berkas Dokumen ({{ $eventItem->documents->count() }} File):</strong><br>
                                        @foreach($eventItem->documents->take(3) as $d)
                                            <span>&bull; {{ $d->file_name ?: $d->title }}</span><br>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="photo-placeholder">
                                    <div class="photo-placeholder-inner">
                                        Dokumentasi belum tersedia
                                    </div>
                                </div>
                            @endif

                            <div class="photo-caption-title">
                                {{ $eventItem->title }}
                            </div>
                            <div class="photo-caption-meta">
                                <strong>Fungsi:</strong> {{ $safeguard }}
                                &bull;
                                <strong>Tanggal:</strong> {{ $eventItem->event_date ? $eventItem->event_date->format('d/m/Y') : '-' }}
                                &bull;
                                <strong>Perusahaan/Instansi:</strong> {{ $eventItem->company ?: $eventItem->unit ?: '-' }}
                                &bull;
                                <span class="badge {{ $badgeClass }}">{{ $eventItem->status }}</span>
                            </div>
                        </div>
                    </td>
                @endforeach

                {{-- Jika baris terakhir hanya memiliki 1 item, buat sel kosong penyeimbang --}}
                @if($row->count() === 1)
                    <td style="width: 50%;"></td>
                @endif
            </tr>
        @endforeach
    </table>
@endif

@endsection
