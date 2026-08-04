<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $report->gerai->kode_gerai }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: '{{ $fontLoaded ? 'Arimo' : 'DejaVu Sans' }}', sans-serif; font-size: 11px; color: #333; margin: 0; }
        h1 { font-size: 20px; font-weight: 700; margin: 0 0 20px 0; text-align: center; }
        .header-table { width: auto; margin-bottom: 24px; border-collapse: collapse; }
        .header-table td { padding: 2px 8px 2px 0; vertical-align: top; }
        .header-label { color: #666; white-space: nowrap; }
        .box { border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
        .box-label { font-weight: bold; font-size: 10px; color: #666; margin-bottom: 6px; text-transform: uppercase; }
        .box-content { font-size: 11px; }
    </style>
</head>
<body>
    <h1>{{ $revisi ? 'Revisi - ' : '' }}Laporan Sementara Temuan {{ $prefix === 're-monitoring' ? 'Re-Monitoring' : ($prefix === 'pra-monitoring' ? 'Pra-Monitoring' : 'Monitoring') }}</h1>

    <table class="header-table">
        <tr>
            <td class="header-label">Nama Gerai</td>
            <td>: {{ $report->gerai->nama_gerai }} ({{ $report->gerai->kode_gerai }})</td>
        </tr>
        <tr>
            <td class="header-label">Tanggal dan Jam Kunjungan</td>
            <td>: {{ $report->checkin_at->format('d-m-Y') }} ({{ $report->checkin_at->format('H:i') }} - {{ $report->submit_at ? $report->submit_at->format('H:i') : '-' }})</td>
        </tr>
        <tr>
            <td class="header-label">Petugas</td>
            <td>: {{ $report->user?->name ?? '-' }}</td>
        </tr>
    </table>

    @if ($report->minor || $report->major)
    <div class="box">
        <div class="box-content">
            @if ($report->minor)
            <div style="margin-bottom: 8px;">
                <div class="box-label">Minor</div>
                {!! nl2br(e(wordwrap($report->minor, 200, "\n", true))) !!}
            </div>
            @endif
            @if ($report->major)
            <div>
                <div class="box-label">Major</div>
                {!! nl2br(e(wordwrap($report->major, 200, "\n", true))) !!}
            </div>
            @endif
        </div>
    </div>
    @endif

    @if ($report->pengawas || $report->rata_rata_aj || ($report->tds && $prefix !== 'pra-monitoring') || $report->mesin_ozon || $report->peringatan_awal || $report->note || $report->kondisi_cat || $report->kondisi_awning || $report->kondisi_vinyl || $report->kondisi_stiker_kaca)
    <div class="box">
        <div class="box-label">Peringatan Awal</div>
        <div class="box-content">
            @if ($report->pengawas)<div>{!! nl2br(e(wordwrap($report->pengawas, 200, "\n", true))) !!}</div>@endif
            @if ($report->rata_rata_aj)<div>Rerata AJ ± {{ $report->rata_rata_aj }} gln/hr</div>@endif
            @if ($report->tds && $prefix !== 'pra-monitoring')<div>TDS: {{ str_replace('/', ' ppm/', $report->tds) }}{{ str_contains($report->tds, '/') ? '°C' : '' }}</div>@endif
            @if ($report->mesin_ozon)<div>MO: {{ $report->mesin_ozon }}</div>@endif
            @if ($report->peringatan_awal)
                <div style="margin-top:8px; max-width:170mm;">
                    @foreach(explode("\n", $report->peringatan_awal) as $line)
                        @if(trim($line) !== '')
                            <div>{!! nl2br(e(wordwrap($line, 200, "\n", true))) !!}</div>
                        @endif
                    @endforeach
                </div>
            @endif
            @if ($report->note)<div style="margin-top: 12px;">Note: {!! nl2br(e(wordwrap($report->note, 200, "\n", true))) !!}</div>@endif
            @if ($report->kondisi_cat || $report->kondisi_awning || $report->kondisi_vinyl || $report->kondisi_stiker_kaca)
                <div style="margin-top: 12px;">
                    Checklist tampilan gerai:<br>
                    Kondisi cat: {{ $report->kondisi_cat ?: 'Baik' }}<br>
                    Kondisi awning: {{ $report->kondisi_awning ?: 'Baik' }}<br>
                    Kondisi vinyl reklame dinding/jalan: {{ $report->kondisi_vinyl ?: 'Baik' }}<br>
                    Kondisi stiker kaca: {{ $report->kondisi_stiker_kaca ?: 'Baik' }}
                </div>
            @endif
        </div>
    </div>
    @endif

    @if ($report->major || $report->minor || $report->peringatan_awal)
    <table style="width:100%; margin-bottom: 12px;">
        <tr>
            <td style="width:50%; padding: 10px; vertical-align: top;">
                <div class="box-label">TTD Petugas</div>
                @if ($ttdImages['ttd_petugas'])
                    <img src="{{ $ttdImages['ttd_petugas'] }}" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px;">
                @else
                    <div class="box-content" style="color: #999; font-style: italic;">Belum ada</div>
                @endif
            </td>
            <td style="width:50%; padding: 10px; vertical-align: top; text-align: right;">
                <div class="box-label" style="text-align: right;">TTD Pimpinan Gerai</div>
                @if ($ttdImages['ttd_pimpinan'])
                    <img src="{{ $ttdImages['ttd_pimpinan'] }}" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px;">
                @else
                    <div class="box-content" style="color: #999; font-style: italic;">Belum ada</div>
                @endif
            </td>
        </tr>
    </table>
    @endif
</body>
</html>
