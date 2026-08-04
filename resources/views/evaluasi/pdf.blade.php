<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Evaluasi - {{ $report->gerai->kode_gerai }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: '{{ $fontLoaded ? 'Arimo' : 'DejaVu Sans' }}', sans-serif; font-size: 11px; color: #333; margin: 0; }
        h1 { font-size: 18px; font-weight: 700; margin: 0 0 16px 0; text-align: center; }
        h2 { font-size: 14px; font-weight: 700; margin: 16px 0 10px 0; }
        .info-table { width: auto; margin-bottom: 16px; border-collapse: collapse; }
        .info-table td { padding: 2px 8px 2px 0; vertical-align: top; }
        .info-label { color: #666; white-space: nowrap; }
        .box { border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
        .box-label { font-weight: bold; font-size: 10px; color: #666; margin-bottom: 6px; text-transform: uppercase; }
        .box-content { font-size: 11px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 10px; }
        table.data th { background: #f3f4f6; font-weight: 600; color: #374151; }
        .row-label { background: #f3f4f6; font-weight: 600; color: #374151; }
        .chart-bar { display: flex; align-items: center; margin-bottom: 6px; }
        .chart-label { width: 60px; font-size: 9px; text-align: right; padding-right: 8px; color: #666; white-space: nowrap; }
        .chart-track { flex: 1; height: 18px; background: #f3f4f6; border-radius: 4px; position: relative; overflow: hidden; }
        .chart-fill { height: 100%; border-radius: 4px; }
        .chart-value { font-size: 9px; padding-left: 6px; width: 35px; color: #374151; font-weight: 600; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>Laporan Evaluasi Hasil Monitoring</h1>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Gerai</td>
            <td>: {{ $report->gerai->nama_gerai }} ({{ $report->gerai->kode_gerai }})</td>
        </tr>
        <tr>
            <td class="info-label">Franchisee</td>
            <td>: {{ $report->gerai->franchisee ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Opening</td>
            <td>: {{ $report->gerai->opening_at ? $report->gerai->opening_at->locale('id')->isoFormat('DD MMMM YYYY') : '-' }}</td>
        </tr>
    </table>

    {{-- Tabel Riwayat Nilai --}}
    @if ($historyData->isNotEmpty())
    <h2>Riwayat Nilai</h2>
    @php $colCount = count($historyData); @endphp
    <table class="data" style="table-layout: fixed; width: 100%;">
        <colgroup>
            <col style="width: 140px;">
            @foreach ($historyData as $h)
            <col>
            @endforeach
        </colgroup>
        <tbody>
            <tr>
                <td class="row-label">Tahun</td>
                @foreach ($historyData as $h)
                <td style="text-align: center;">{{ $h['year'] ?? '-' }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-label">Periode</td>
                @foreach ($historyData as $h)
                <td style="text-align: center;">{{ $h['periode_short'] ?? '-' }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-label" style="white-space: nowrap; font-size: 10px;">Standar Kinerja</td>
                @foreach ($historyData as $h)
                <td style="text-align: center;">975</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-label">Poin Kinerja</td>
                @foreach ($historyData as $h)
                @php $pn = $h['nilai'] !== null ? round((float) $h['nilai']) : null; @endphp
                <td style="text-align: center; {{ $pn !== null ? ($pn >= 975 ? 'background: #DBEAFE; color: #1D4ED8; font-weight: 600;' : 'background: #FEE2E2; color: #B91C1C; font-weight: 600;') : '' }}">{{ $pn ?? '-' }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-label">Peringkat</td>
                @foreach ($historyData as $h)
                <td style="text-align: center;">
                    @if ($h['type'] === 're-monitoring')
                        <span style="font-weight: 600;">REMON</span>
                    @elseif ($h['rank'] && $h['total'])
                        {{ $h['rank'] }} - {{ $h['total'] }}
                    @else
                        -
                    @endif
                </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    {{-- Grafik --}}
    @php
        $points = $historyData->filter(fn($h) => $h['nilai'] !== null)->values();
        $count = $points->count();

        $scale = 2;
        $chartW = 700; $chartH = 340;
        $svgW = $chartW * $scale; $svgH = $chartH * $scale;
        $padL = 55 * $scale; $padR = 25 * $scale; $padT = 45 * $scale; $padB = 70 * $scale;
        $plotW = $svgW - $padL - $padR;
        $plotH = $svgH - $padT - $padB;

        $yMin = 0; $yMax = 1050; $yStep = 150;
        $stdVal = 975;

        $toY = function($v) use ($padT, $plotH, $yMin, $yMax) {
            return round($padT + $plotH - (($v - $yMin) / ($yMax - $yMin)) * $plotH);
        };
        $toX = function($i) use ($count, $padL, $plotW) {
            return $count > 1 ? round($padL + $i * ($plotW / ($count - 1))) : round($padL + $plotW / 2);
        };

        $img = imagecreatetruecolor($svgW, $svgH);
        imageantialias($img, true);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);

        $gridColor = imagecolorallocatealpha($img, 200, 200, 200, 20);
        for ($v = $yMin; $v <= $yMax; $v += $yStep) {
            $y = $toY($v);
            for ($dx = 0; $dx < $scale; $dx++) {
                imagesetthickness($img, 1);
                imageline($img, $padL, $y + $dx, $padL + $plotW, $y + $dx, $gridColor);
            }
        }

        $axisColor = imagecolorallocate($img, 180, 180, 180);
        imagesetthickness($img, $scale);
        imageline($img, $padL, $padT, $padL, $padT + $plotH, $axisColor);
        imageline($img, $padL, $padT + $plotH, $padL + $plotW, $padT + $plotH, $axisColor);

        $fontRegular = storage_path('fonts/Arimo-Regular.ttf');
        $fontBold = storage_path('fonts/Arimo-Bold.ttf');
        $hasFont = file_exists($fontRegular) && filesize($fontRegular) > 1000;
        $gray = imagecolorallocate($img, 130, 130, 130);
        $darkGray = imagecolorallocate($img, 80, 80, 80);

        for ($v = $yMin; $v <= $yMax; $v += $yStep) {
            $y = $toY($v);
            $label = (string)$v;
            $fs = 9 * $scale;
            if ($hasFont) {
                $bb = imagettfbbox($fs, 0, $fontRegular, $label);
                $tw = $bb[2] - $bb[0];
                imagettftext($img, $fs, 0, $padL - 10 * $scale - $tw, $y + 4 * $scale, $gray, $fontRegular, $label);
            }
        }

        $xLabels = [];
        foreach ($points as $i => $h) {
            $x = $toX($i);
            $parts = explode('-', $h['periode_short'] ?? '');
            $abbr = '';
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') $abbr .= mb_substr($p, 0, 1);
                if ($p !== end($parts)) $abbr .= '-';
            }
            $xlabel = $abbr . ' ' . $h['year'];
            $xLabels[] = $xlabel;
            $fs = 9 * $scale;
            if ($hasFont) {
                $bb = imagettfbbox($fs, 0, $fontRegular, $xlabel);
                $tw = $bb[2] - $bb[0];
                imagettftext($img, $fs, 0, $x - $tw / 2, $padT + $plotH + 16 * $scale, $gray, $fontRegular, $xlabel);
            }
        }

        $blueColor = imagecolorallocate($img, 37, 99, 235);
        $blueFill = imagecolorallocatealpha($img, 37, 99, 235, 40);
        $redColor = imagecolorallocate($img, 239, 68, 68);

        $stdY = $toY($stdVal);

        imagesetthickness($img, $scale);
        for ($i = 1; $i < $count; $i++) {
            $x1 = $toX($i - 1); $y1 = $toY($points[$i - 1]['nilai']);
            $x2 = $toX($i); $y2 = $toY($points[$i]['nilai']);
            imageline($img, $x1, $y1, $x2, $y2, $blueColor);
        }

        $polyPoints = [];
        foreach ($points as $i => $h) {
            $polyPoints[] = $toX($i);
            $polyPoints[] = $toY($h['nilai']);
        }
        if ($count > 1) {
            $fillPoly = array_merge($polyPoints, [$toX($count - 1), $padT + $plotH, $toX(0), $padT + $plotH]);
            imagefilledpolygon($img, $fillPoly, $count + 2, $blueFill);
        }

        imagesetthickness($img, $scale);
        $dashLen = 8 * $scale; $gapLen = 4 * $scale;
        $dashOn = true; $dashCount = 0;
        for ($dx = $padL; $dx <= $padL + $plotW; $dx++) {
            $dashCount++;
            if ($dashOn) {
                imagesetpixel($img, $dx, $stdY, $redColor);
            }
            if ($dashCount >= ($dashOn ? $dashLen : $gapLen)) {
                $dashCount = 0;
                $dashOn = !$dashOn;
            }
        }

        foreach ($points as $i => $h) {
            $x = $toX($i); $y = $toY($h['nilai']);
            $r = 6 * $scale;
            imagefilledellipse($img, $x, $y, $r * 2 + 4, $r * 2 + 4, imagecolorallocate($img, 255, 255, 255));
            imagefilledellipse($img, $x, $y, $r * 2, $r * 2, $blueColor);
            $valLabel = (string)round((float)$h['nilai']);
            $vfs = 8 * $scale;
            if ($hasFont) {
                $bb = imagettfbbox($vfs, 0, $fontBold, $valLabel);
                $tw = $bb[2] - $bb[0]; $th = $bb[1] - $bb[3];
                imagettftext($img, $vfs, 0, $x - $tw / 2, $y + $th / 2 + 1, imagecolorallocate($img, 255, 255, 255), $fontBold, $valLabel);
            }

            $rStd = 5 * $scale;
            imagefilledellipse($img, $x, $stdY, $rStd * 2 + 4, $rStd * 2 + 4, imagecolorallocate($img, 255, 255, 255));
            imagefilledellipse($img, $x, $stdY, $rStd * 2, $rStd * 2, $redColor);
            $stdLabel = '975';
            $sfs = 7 * $scale;
            if ($hasFont) {
                $bb = imagettfbbox($sfs, 0, $fontBold, $stdLabel);
                $tw = $bb[2] - $bb[0]; $th = $bb[1] - $bb[3];
                imagettftext($img, $sfs, 0, $x - $tw / 2, $stdY + $th / 2 + 1, imagecolorallocate($img, 255, 255, 255), $fontBold, $stdLabel);
            }
        }

        $titleText = 'GRAFIK KINERJA PSSO WARALABA BIRU';
        $tfs = 13 * $scale;
        if ($hasFont) {
            $bb = imagettfbbox($tfs, 0, $fontBold, $titleText);
            $tw = $bb[2] - $bb[0];
            imagettftext($img, $tfs, 0, $padL + ($plotW - $tw) / 2, 24 * $scale, $darkGray, $fontBold, $titleText);
        }

        $legY = $padT + $plotH + 42 * $scale;
        $legCenterX = $padL + $plotW / 2;
        $legX1 = $legCenterX - 110 * $scale;
        $legX2 = $legCenterX + 20 * $scale;

        imagesetthickness($img, 2 * $scale);
        imageline($img, $legX1, $legY, $legX1 + 24 * $scale, $legY, $blueColor);
        imagefilledellipse($img, $legX1 + 12 * $scale, $legY, 8 * $scale, 8 * $scale, $blueColor);
        $lfs = 10 * $scale;
        if ($hasFont) {
            imagettftext($img, $lfs, 0, $legX1 + 30 * $scale, $legY + 4 * $scale, $darkGray, $fontRegular, 'Poin Kinerja');
        }

        $dashCount2 = 0; $dashOn2 = true;
        for ($dx = $legX2; $dx <= $legX2 + 24 * $scale; $dx++) {
            $dashCount2++;
            if ($dashOn2) imagesetpixel($img, $dx, $legY, $redColor);
            if ($dashCount2 >= 4 * $scale) { $dashCount2 = 0; $dashOn2 = !$dashOn2; }
        }
        imagefilledellipse($img, $legX2 + 12 * $scale, $legY, 8 * $scale, 8 * $scale, $redColor);
        if ($hasFont) {
            imagettftext($img, $lfs, 0, $legX2 + 30 * $scale, $legY + 4 * $scale, $darkGray, $fontRegular, 'Standar Kinerja');
        }

        $thumbW = $chartW; $thumbH = $chartH;
        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumbW, $thumbH, $svgW, $svgH);
        imagedestroy($img);

        ob_start();
        imagepng($thumb);
        $chartData = ob_get_clean();
        imagedestroy($thumb);
        $chartBase64 = base64_encode($chartData);
    @endphp
    <div style="margin-bottom: 12px;">
        <img src="data:image/png;base64,{{ $chartBase64 }}" style="width: 100%; max-width: {{ $chartW }}px;" />
    </div>
    @endif

    {{-- Catatan & Keterangan --}}
    @if ($report->catatan || $report->keterangan)
    <div style="margin-top: 16px;">
    @if ($report->catatan)
        <div class="box-label">Catatan</div>
        <div class="box-content" style="margin-bottom: 10px;">{!! nl2br(e($report->catatan)) !!}</div>
    @endif
    @if ($report->keterangan)
        <div class="box-label">Keterangan</div>
        <div class="box-content">{!! nl2br(e($report->keterangan)) !!}</div>
    @endif
    </div>
    @endif

    {{-- Page 2 --}}
    @if ($lastReport && ($lastReport->major || $lastReport->minor))
    <div class="page-break"></div>

    <h1>Temuan Kategori Peringatan Awal</h1>

    <table class="info-table">
        <tr>
            <td class="info-label">Gerai</td>
            <td>: {{ $report->gerai->nama_gerai }} ({{ $report->gerai->kode_gerai }})</td>
        </tr>
        <tr>
            <td class="info-label">Monitoring</td>
            <td>: {{ $lastReport->checkin_at->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">Petugas</td>
            <td>: {{ $lastReport->user?->name ?? '-' }}</td>
        </tr>
    </table>

    @php $f = $lastReport; @endphp
    <div class="box">
        <div class="box-label">Peringatan Awal</div>
        <div class="box-content">
            @if ($f->pengawas)<div>{!! nl2br(e(wordwrap($f->pengawas, 200, "\n", true))) !!}</div>@endif
            @if ($f->rata_rata_aj)<div>Rerata AJ ± {{ $f->rata_rata_aj }} gln/hr</div>@endif
            @if ($f->tds)<div>TDS: {{ str_replace('/', ' ppm/', $f->tds) }}{{ str_contains($f->tds, '/') ? '°C' : '' }}</div>@endif
            @if ($f->mesin_ozon)<div>MO: {{ $f->mesin_ozon }}</div>@endif
            @if ($f->peringatan_awal)
                <div style="margin-top: 8px;">
                    @foreach(explode("\n", $f->peringatan_awal) as $line)
                        @if(trim($line) !== '')
                            <div>{!! nl2br(e(wordwrap($line, 200, "\n", true))) !!}</div>
                        @endif
                    @endforeach
                </div>
            @endif
            @if ($f->note)<div style="margin-top: 8px;">Note: {!! nl2br(e(wordwrap($f->note, 200, "\n", true))) !!}</div>@endif
            @if ($f->kondisi_cat || $f->kondisi_awning || $f->kondisi_vinyl || $f->kondisi_stiker_kaca)
                <div style="margin-top: 8px;">
                    Checklist tampilan gerai:<br>
                    Kondisi cat: {{ $f->kondisi_cat ?: 'Baik' }}<br>
                    Kondisi awning: {{ $f->kondisi_awning ?: 'Baik' }}<br>
                    Kondisi vinyl reklame dinding/jalan: {{ $f->kondisi_vinyl ?: 'Baik' }}<br>
                    Kondisi stiker kaca: {{ $f->kondisi_stiker_kaca ?: 'Baik' }}
                </div>
            @endif
        </div>
    </div>
    @endif
</body>
</html>
