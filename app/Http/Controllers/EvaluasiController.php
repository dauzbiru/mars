<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\EvaluasiReport;
use App\Models\MonitoringReport;
use App\Models\ReMonitoringReport;
use App\Models\SemesterPeriod;
use App\Models\Ranking;
use App\Models\KotaMap;
use Illuminate\Http\Request;
use App\Services\EvaluasiHistoryBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FontRegistration;

class EvaluasiController extends MonitoringController
{
    use FontRegistration;
    protected $type = 'evaluasi';

    protected function modelClass(): string
    {
        return EvaluasiReport::class;
    }

    public function excel($id, $outputDir = null)
    {
        $report = $this->modelClass()::findOrFail($id);
        $this->authorizeReport($report);
        set_time_limit(120);

        $tz = 'Asia/Jakarta';

        $lastMonitoring = MonitoringReport::where('gerai_id', $report->gerai_id)
            ->whereNotNull('checkin_at')->whereNotNull('submit_at')
            ->orderByDesc('checkin_at')->first();
        if (!$lastMonitoring) {
            $lastMonitoring = ReMonitoringReport::where('gerai_id', $report->gerai_id)
                ->whereNotNull('checkin_at')->whereNotNull('submit_at')
                ->orderByDesc('checkin_at')->first();
        }

        $headerReplacements = [
            '{nama_gerai}'       => strtoupper($report->gerai->nama_gerai),
            '{kode_gerai}'       => $report->gerai->kode_gerai,
            '{franchisee}'       => strtoupper($report->gerai->franchisee),
            '{lokasi}'           => $report->location ?? '-',
            '{tanggal}'          => $report->tanggal->format('d-m-Y'),
            '{tanggal_lengkap}'  => $report->tanggal->locale('id')->isoFormat('D MMMM YYYY'),
            '{submit}'           => '-',
            '{petugas}'          => strtoupper($report->user?->name ?? '-'),
            '{periode}'          => strtoupper($report->tanggal->locale('id')->isoFormat('MMMM YYYY')),
            '{type}'             => 'Evaluasi',
            '{nama_kota}'        => strtoupper(KotaMap::where('kode', substr($report->gerai->kode_gerai, 0, 3))->value('nama_kota') ?? $report->gerai->nama_kota ?? '-'),
            '{area}'             => $report->gerai->area ?? '-',
            '{opening_at}'       => $report->gerai->opening_at ? strtoupper($report->gerai->opening_at->locale('id')->isoFormat('D MMMM YYYY')) : '-',
            '{prev_checkin}'     => $lastMonitoring ? strtoupper($lastMonitoring->checkin_at->setTimezone($tz)->locale('id')->isoFormat('D MMMM YYYY')) : '-',
        ];

        return $this->buildExcel($report, $headerReplacements, $outputDir);
    }

    protected function buildExcel($report, array $headerReplacements, $outputDir = null)
    {
        $filename = "Evaluasi - {$report->gerai->kode_gerai} - " . $report->tanggal->locale('id')->isoFormat('MMMM YYYY') . '.xlsx';
        $outPath = $outputDir ? rtrim($outputDir, '\\/') . DIRECTORY_SEPARATOR . $filename : \Illuminate\Support\Facades\Storage::path($filename);

        // Call parent with a known output directory, then rename to our filename
        $parentResult = parent::buildExcel($report, $headerReplacements, dirname($outPath));
        if (is_string($parentResult) && $parentResult !== $outPath) {
            if (file_exists($outPath)) {
                unlink($outPath);
            }
            rename($parentResult, $outPath);
        } elseif (!is_string($parentResult)) {
            return $parentResult;
        }

        $geraiId = $report->gerai_id;

        $historyBuilder = new EvaluasiHistoryBuilder($geraiId);
        $historyData = $historyBuilder->mapHistoryData(function ($data, $r) {
            $data['year'] = (int) $data['year'];
            $data['standar'] = MonitoringReport::GRADE_B_THRESHOLD;
            unset($data['grade'], $data['finding']);
            return $data;
        })->reverse()->values();

        $zip = new \ZipArchive;
        if ($zip->open($outPath) === true) {
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $content = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($content) {
                $dom = new \DOMDocument;
                $dom->loadXML($content);
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('s', $ns);

                // Pre-fetch all cells in D8:M12 range
                $allCols = range('D', 'M');
                $cellsD8M12 = [];
                foreach ($allCols as $col) {
                    for ($row = 8; $row <= 12; $row++) {
                        $ref = $col . $row;
                        $found = $xpath->query("//s:c[@r='$ref']");
                        $cellsD8M12[$ref] = $found->length > 0 ? $found->item(0) : null;
                    }
                }

                // Paste history data into D8:M12 (clear + write in 1 pass)
                $filled = $historyData->toArray();
                $cols = array_reverse(range('D', 'M'));
                $mappedCols = [];
                foreach ($filled as $ci => $d) {
                    if ($ci >= count($cols)) break;
                    $col = $cols[$ci];
                    $mappedCols[$ci] = $col;
                    $isRemon = ($d['type'] ?? '') === 're-monitoring';
                    $rows = [
                        '8' => ['val' => $d['year'] ?? null,         'type' => 'num'],
                        '9' => ['val' => $d['periode_short'] ?? '',  'type' => 'str'],
                        '10' => ['val' => $d['standar'] ?? null,     'type' => 'num'],
                        '11' => ['val' => $d['nilai'] ?? null,       'type' => 'num'],
                        '12' => ['val' => $isRemon
                            ? 'REMON'
                            : (($d['rank'] ?? null) && ($d['total'] ?? null)
                                ? $d['rank'] . '-' . $d['total']
                                : '-'),
                            'type' => 'str'],
                    ];
                    foreach ($rows as $row => ['val' => $val, 'type' => $type]) {
                        $ref = $col . $row;
                        $cell = $cellsD8M12[$ref] ?? null;
                        if (!$cell) continue;
                        while ($cell->hasChildNodes()) {
                            $cell->removeChild($cell->firstChild);
                        }
                        $cell->removeAttribute('t');
                        if ($type === 'str') {
                            $cell->setAttribute('t', 'inlineStr');
                            $is = $dom->createElementNS($ns, 'is');
                            $t = $dom->createElementNS($ns, 't');
                            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                            $t->appendChild($dom->createTextNode((string) $val));
                            $is->appendChild($t);
                            $cell->appendChild($is);
                        } elseif ($val !== null && $val !== '') {
                            $v = $dom->createElementNS($ns, 'v');
                            $v->textContent = (string) $val;
                            $cell->appendChild($v);
                        }
                    }
                }

                // Build catatan + keterangan lines starting from B34
                $catLines = !empty($report->catatan) ? explode("\n", $report->catatan) : [];
                $ketLines = !empty($report->keterangan) ? explode("\n", $report->keterangan) : [];

                $lines = [];
                $lineStyles = [];
                $lines[] = 'Catatan:';
                $lineStyles[] = 'header';
                foreach ($catLines as $cl) {
                    $lines[] = $cl;
                    $lineStyles[] = 'normal';
                }
                $lines[] = '';
                $lineStyles[] = 'normal';
                $lines[] = 'Keterangan:';
                $lineStyles[] = 'header';
                foreach ($ketLines as $kl) {
                    $lines[] = $kl;
                    $lineStyles[] = 'normal';
                }

                $sheetData = $xpath->query("//s:sheetData")->item(0);
                $startRow = 34;
                foreach ($lines as $li => $text) {
                    $row = $startRow + $li;
                    $ref = "B{$row}";
                    $isHeader = ($lineStyles[$li] === 'header');

                    $rowEl = $xpath->query("//s:sheetData/s:row[@r='$row']")->item(0);
                    if (!$rowEl) {
                        $rowEl = $dom->createElementNS($ns, 'row');
                        $rowEl->setAttribute('r', $row);
                        $sheetData->appendChild($rowEl);
                    }

                    $cells = $xpath->query("//s:c[@r='$ref']", $rowEl);
                    if ($cells->length > 0) {
                        $cell = $cells->item(0);
                    } else {
                        $cell = $dom->createElementNS($ns, 'c');
                        $cell->setAttribute('r', $ref);
                        $inserted = false;
                        foreach ($rowEl->childNodes as $existing) {
                            if ($existing->nodeName === 'c') {
                                $existingRef = $existing->getAttribute('r');
                                $existingCol = preg_replace('/\d+/', '', $existingRef);
                                if (strcmp($existingCol, 'B') > 0) {
                                    $rowEl->insertBefore($cell, $existing);
                                    $inserted = true;
                                    break;
                                }
                            }
                        }
                        if (!$inserted) $rowEl->appendChild($cell);
                    }

                    foreach (['v', 'is', 'f'] as $tag) {
                        $ex = $cell->getElementsByTagNameNS($ns, $tag)->item(0);
                        if ($ex) $cell->removeChild($ex);
                    }
                    $cell->removeAttribute('s');

                    if ($text !== '') {
                        $cell->setAttribute('t', 'inlineStr');
                        $isEl = $dom->createElementNS($ns, 'is');
                        $rEl = $dom->createElementNS($ns, 'r');
                        $tEl = $dom->createElementNS($ns, 't');
                        $tEl->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                        $tEl->appendChild($dom->createTextNode($text));
                        $rEl->appendChild($tEl);
                        $isEl->appendChild($rEl);
                        $cell->appendChild($isEl);
                    } else {
                        $cell->removeAttribute('t');
                    }
                }

                $mergeRefs = [];
                $i = 0;
                $n = count($filled);
                while ($i < $n) {
                    $cur = $filled[$i];
                    $year = $cur['year'] ?? null;
                    $isRemon = ($cur['type'] ?? '') === 're-monitoring';
                    $end = $i;
                    $j = $i + 1;
                    while ($j < $n) {
                        $next = $filled[$j];
                        $nextRemon = ($next['type'] ?? '') === 're-monitoring';
                        if (($next['year'] ?? null) === $year && $nextRemon === $isRemon) {
                            $end = $j;
                            $j++;
                        } else {
                            break;
                        }
                    }
                    if ($end > $i) {
                        $from = min($mappedCols[$i], $mappedCols[$end]);
                        $to = max($mappedCols[$i], $mappedCols[$end]);
                        $mergeRefs[] = $from . '8:' . $to . '8';
                        // Clear year from all cells in range, write to leftmost
                        for ($k = $i; $k <= $end; $k++) {
                            $ref = $mappedCols[$k] . '8';
                            $cells = $xpath->query("//s:c[@r='$ref']");
                            if ($cells->length > 0) {
                                $cell = $cells->item(0);
                                foreach (['v', 'is', 'f'] as $tag) {
                                    $ex = $cell->getElementsByTagNameNS($ns, $tag)->item(0);
                                    if ($ex) $cell->removeChild($ex);
                                }
                                $cell->removeAttribute('t');
                            }
                        }
                        // Write year to leftmost cell
                        $leftRef = $from . '8';
                        $leftCells = $xpath->query("//s:c[@r='$leftRef']");
                        if ($leftCells->length > 0) {
                            $cell = $leftCells->item(0);
                            $v = $dom->createElementNS($ns, 'v');
                            $v->textContent = (string) $year;
                            $cell->appendChild($v);
                        }
                    }
                    $i = $j;
                }

                if ($mergeRefs) {
                    $existingMCs = $xpath->query('//s:mergeCells')->item(0);
                    if (!$existingMCs) {
                        $existingMCs = $dom->createElementNS($ns, 'mergeCells');
                        $existingMCs->setAttribute('count', '0');
                        $sheetData->parentNode->appendChild($existingMCs);
                    }
                    foreach ($mergeRefs as $ref) {
                        $mcEl = $dom->createElementNS($ns, 'mergeCell');
                        $mcEl->setAttribute('ref', $ref);
                        $existingMCs->appendChild($mcEl);
                    }
                    $existingMCs->setAttribute('count', (string)($existingMCs->childNodes->length));
                }
                $zip->addFromString('xl/worksheets/sheet1.xml', $dom->saveXML());
            }

            // --- Fill DATA CHART sheet (sheet3) with period data ---
            $sheet3Content = $zip->getFromName('xl/worksheets/sheet3.xml');
            if ($sheet3Content !== false) {
                $dom3 = new \DOMDocument;
                $dom3->loadXML($sheet3Content);
                $xpath3 = new \DOMXPath($dom3);
                $ns3 = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                $xpath3->registerNamespace('s', $ns3);

                $chartColumns = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
                $chartFilledCols = [];
                $chartData = array_reverse($historyData->values()->toArray());
                $sheetData3 = $xpath3->query('//s:sheetData')->item(0);

                foreach ($chartData as $colIdx => $d) {
                    if ($colIdx >= 10) break;
                    $col = $chartColumns[$colIdx];
                    $chartFilledCols[] = $col;

                    $year = $d['year'] ?? '';
                    $periodeShort = $d['periode_short'] ?? '';
                    $parts = array_map('trim', explode('-', $periodeShort));
                    $chartLabel = strtoupper(substr($parts[0], 0, 1)) . '-' . strtoupper(substr($parts[1] ?? $parts[0], 0, 1)) . ' ' . $year;

                    $periodScore = is_numeric($d['nilai'] ?? null) ? round((float) $d['nilai']) : 0;

                    foreach ([1 => $chartLabel, 2 => (string) $periodScore, 3 => (string) MonitoringReport::GRADE_B_THRESHOLD] as $rowNum => $cellValue) {
                        $ref = $col . $rowNum;
                        $cells = $xpath3->query("//s:c[@r='$ref']");

                        if ($cells->length > 0) {
                            $cell = $cells->item(0);
                        } else {
                            $rowEl = $xpath3->query("//s:sheetData/s:row[@r='$rowNum']")->item(0);
                            if (!$rowEl) {
                                $rowEl = $dom3->createElementNS($ns3, 'row');
                                $rowEl->setAttribute('r', $rowNum);
                                $sheetData3->appendChild($rowEl);
                            }
                            $cell = $dom3->createElementNS($ns3, 'c');
                            $cell->setAttribute('r', $ref);
                            $rowEl->appendChild($cell);
                        }

                        foreach (['v', 'is', 'f'] as $tag) {
                            $existing = $cell->getElementsByTagNameNS($ns3, $tag)->item(0);
                            if ($existing) $cell->removeChild($existing);
                        }

                        if ($rowNum === 1) {
                            $cell->setAttribute('t', 'inlineStr');
                            $is = $dom3->createElementNS($ns3, 'is');
                            $t = $dom3->createElementNS($ns3, 't');
                            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                            $t->textContent = $cellValue;
                            $is->appendChild($t);
                            $cell->appendChild($is);
                        } else {
                            $cell->removeAttribute('t');
                            $v = $dom3->createElementNS($ns3, 'v');
                            $v->textContent = $cellValue;
                            $cell->appendChild($v);
                        }
                    }
                }

                // Delete unused columns after last filled column
                $chartLastIdx = array_search(end($chartFilledCols), $chartColumns, true);
                if ($chartLastIdx !== false && $chartLastIdx < 9) {
                    $delCols = array_slice($chartColumns, $chartLastIdx + 1);
                    foreach ($delCols as $delCol) {
                        foreach ([1, 2, 3] as $delRow) {
                            $ref = $delCol . $delRow;
                            $cells = $xpath3->query("//s:c[@r='$ref']");
                            for ($i = $cells->length - 1; $i >= 0; $i--) {
                                $cells->item($i)->parentNode->removeChild($cells->item($i));
                            }
                        }
                    }
                }

                // Always update chart1.xml range references to match actual fill
                $chartLastCol = end($chartFilledCols);
                $chartContent = $zip->getFromName('xl/charts/chart1.xml');
                if ($chartContent !== false) {
                    $chartContent = str_replace(
                        ["'DATA CHART'!\$B\$1:\$J\$1", "'DATA CHART'!\$B\$2:\$J\$2", "'DATA CHART'!\$B\$3:\$J\$3"],
                        ["'DATA CHART'!\$B\$1:\${$chartLastCol}\$1", "'DATA CHART'!\$B\$2:\${$chartLastCol}\$2", "'DATA CHART'!\$B\$3:\${$chartLastCol}\$3"],
                        $chartContent
                    );
                    $zip->addFromString('xl/charts/chart1.xml', $chartContent);
                }

                $zip->addFromString('xl/worksheets/sheet3.xml', $dom3->saveXML());
            }

            // --- Fill sheet2 (TEMUAN KATEGORI PERINGATAN AWAL) ---
            $sheet2Content = $zip->getFromName('xl/worksheets/sheet2.xml');
            if ($sheet2Content !== false) {
                $dom2 = new \DOMDocument;
                $dom2->loadXML($sheet2Content);
                $xpath2 = new \DOMXPath($dom2);
                $ns2 = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                $xpath2->registerNamespace('s', $ns2);

                $lastReport = $historyBuilder->getLastReport();

                if ($lastReport && ($lastReport->major || $lastReport->minor || $lastReport->peringatan_awal)) {
                    // Parse peringatan_awal into numbered items (strip "1. " prefix)
                    $paItems = [];
                    if (!empty($lastReport->peringatan_awal)) {
                        $rawPALines = explode("\n", $lastReport->peringatan_awal);
                        $curItem = '';
                        foreach ($rawPALines as $rl) {
                            if (preg_match('/^\d+[\.\)]\s*/', $rl)) {
                                if ($curItem !== '') $paItems[] = $curItem;
                                $curItem = preg_replace('/^\d+[\.\)]\s*/', '', $rl);
                            } else {
                                $curItem .= ($curItem !== '' ? "\n" : '') . $rl;
                            }
                        }
                        if ($curItem !== '') $paItems[] = $curItem;
                    }

                    // Peringatan awal lines reversed (for bottom-up C fill)
                    $paLinesReversed = [];
                    foreach (array_reverse($paItems) as $item) {
                        $itemLines = explode("\n", $item);
                        foreach (array_reverse($itemLines) as $il) {
                            $paLinesReversed[] = $il;
                        }
                    }

                    $lines = [];

                    // Checklist kondisi (bottom up)
                    $lines[] = 'Kondisi stiker kaca: ' . ($lastReport->kondisi_stiker_kaca ?: 'Baik');
                    $lines[] = 'Kondisi vinyl reklame dinding/jalan: ' . ($lastReport->kondisi_vinyl ?: 'Baik');
                    $lines[] = 'Kondisi awning: ' . ($lastReport->kondisi_awning ?: 'Baik');
                    $lines[] = 'Kondisi cat: ' . ($lastReport->kondisi_cat ?: 'Baik');
                    $lines[] = 'Checklist Kondisi Gerai:';
                    $lines[] = ''; // space

                    // Note (bottom up)
                    if (!empty($lastReport->note)) {
                        $noteLines = array_reverse(explode("\n", $lastReport->note));
                        foreach ($noteLines as $nl) {
                            $lines[] = $nl;
                        }
                    }
                    $lines[] = 'Note:';
                    $lines[] = ''; // space

                    // Peringatan awal (bottom up, no header)
                    foreach ($paLinesReversed as $pl) {
                        $lines[] = $pl;
                    }

                    $sheetData2 = $xpath2->query('//s:sheetData')->item(0);
                    $mergeRefs2 = [];
                    $startRow = 69;

                    // Helper: write cell value
                    $writeCell = function($rowNum, $col, $text) use ($dom2, $ns2, $xpath2, $sheetData2) {
                        $ref = "{$col}{$rowNum}";
                        $cells = $xpath2->query("//s:c[@r='$ref']");
                        if ($cells->length > 0) {
                            $cell = $cells->item(0);
                        } else {
                            $rowEl = $xpath2->query("//s:sheetData/s:row[@r='$rowNum']")->item(0);
                            if (!$rowEl) {
                                $rowEl = $dom2->createElementNS($ns2, 'row');
                                $rowEl->setAttribute('r', $rowNum);
                                $sheetData2->appendChild($rowEl);
                            }
                            $cell = $dom2->createElementNS($ns2, 'c');
                            $cell->setAttribute('r', $ref);
                            $rowEl->appendChild($cell);
                        }
                        foreach (['v', 'is', 'f'] as $tag) {
                            $ex = $cell->getElementsByTagNameNS($ns2, $tag)->item(0);
                            if ($ex) $cell->removeChild($ex);
                        }
                        $cell->setAttribute('t', 'inlineStr');
                        $isEl = $dom2->createElementNS($ns2, 'is');
                        $tEl = $dom2->createElementNS($ns2, 't');
                        $tEl->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                        $tEl->appendChild($dom2->createTextNode((string) $text));
                        $isEl->appendChild($tEl);
                        $cell->appendChild($isEl);
                        return $cell;
                    };

                    // Fill C cells (bottom up)
                    foreach ($lines as $i => $text) {
                        $rowNum = $startRow - $i;
                        if ($rowNum < 8) break;
                        $writeCell($rowNum, 'C', $text);
                        $mergeRefs2[] = "C{$rowNum}:N{$rowNum}";
                    }

                    // B column numbering for peringatan awal
                    $totalLines = count($lines);
                    $paLinesCount = count($paLinesReversed);
                    $paTopRow = $startRow - ($totalLines - 1);

                    $bRow = $paTopRow;
                    $paNumber = 1;
                    foreach ($paItems as $item) {
                        $itemLineCount = count(explode("\n", $item));
                        $writeCell($bRow, 'B', $paNumber);
                        if ($itemLineCount > 1) {
                            $mergeRefs2[] = "B{$bRow}:B" . ($bRow + $itemLineCount - 1);
                        }
                        $bRow += $itemLineCount;
                        $paNumber++;
                    }

                    // Merge B{lastNumRow}:B69 for empty space below last number
                    if ($bRow <= $startRow) {
                        $mergeRefs2[] = "B{$bRow}:B{$startRow}";
                    }

                    // Clean unused rows below last filled
                    foreach ($xpath2->query("//s:sheetData/s:row[@r > $startRow]") as $row) {
                        $sheetData2->removeChild($row);
                    }

                    // Add merge cells via DOM
                    if ($mergeRefs2) {
                        $existingMCs2 = $xpath2->query('//s:mergeCells')->item(0);
                        if (!$existingMCs2) {
                            $existingMCs2 = $dom2->createElementNS($ns2, 'mergeCells');
                            $existingMCs2->setAttribute('count', '0');
                            $sheetData2->parentNode->appendChild($existingMCs2);
                        }
                        foreach ($mergeRefs2 as $ref) {
                            $mcEl = $dom2->createElementNS($ns2, 'mergeCell');
                            $mcEl->setAttribute('ref', $ref);
                            $existingMCs2->appendChild($mcEl);
                        }
                        $existingMCs2->setAttribute('count', (string)($existingMCs2->childNodes->length));
                    }

                    $zip->addFromString('xl/worksheets/sheet2.xml', $dom2->saveXML());
                }
            }

            $zip->close();
        }

        // Post-process: set fonts via xlwings
        $pyScript = base_path('scripts/evaluasi-font.py');
        exec("python " . escapeshellarg($pyScript) . " " . escapeshellarg($outPath) . " 2>&1", $output, $returnCode);

        if ($outputDir) {
            return $outPath;
        }
        return response()->download($outPath, $filename)->deleteFileAfterSend(true);
    }

    protected function pendingReport()
    {
        if (auth()->user()?->isAdmin()) {
            return null;
        }
        return EvaluasiReport::where('user_id', auth()->id())
            ->whereNull('tanggal')
            ->first();
    }

    public function checkinForm(Gerai $gerai)
    {
        $isAdmin = (bool) auth()->user()?->isAdmin();

        if (!$isAdmin) {
            $pending = EvaluasiReport::where('user_id', auth()->id())
                ->whereNull('tanggal')
                ->with('gerai')
                ->first();

            if ($pending) {
                return redirect("/{$this->prefix()}/{$pending->id}")
                    ->with('warning', 'Anda masih memiliki laporan evaluasi yang belum diselesaikan. Selesaikan atau batalkan dulu.');
            }
        }

        $existing = EvaluasiReport::where('gerai_id', $gerai->id)
            ->when(!$isAdmin, fn($q) => $q->where('user_id', auth()->id()))
            ->first();

        if ($existing) {
            return redirect("/{$this->prefix()}/{$existing->id}")
                ->with('warning', 'Data evaluasi untuk gerai ini sudah ada.');
        }

        $report = EvaluasiReport::create([
            'gerai_id' => $gerai->id,
            'user_id' => auth()->id(),
        ]);

        $historyBuilder = new \App\Services\EvaluasiHistoryBuilder($gerai->id);
        $historyData = $historyBuilder->mapHistoryData();
        $lastReportModel = $historyBuilder->getLastReport();

        $gradeLabel = 'Baik';
        $pimpinanText = 'Pimpinan Gerai mampu menerapkan & mengarahkan dengan baik standar pelayanan pelanggan kepada karyawan sesuai standar PSSO.';
        $periodeLabel = 'monitoring periode terbaru';
        $posisiText = "2. Gerai {$gerai->kode_gerai} mampu menempatkan posisi kinerja gerainya untuk berada di atas Standar Kinerja monitoring semua gerai BIRU.";
        $gradeLetter = 'B';
        $belowAvgCount = 0;

        if ($lastReportModel) {
            $gradeLetter = $lastReportModel->grade ?? 'B';
            $gradeLabel = ['A' => 'Sangat Baik', 'B' => 'Baik', 'C' => 'Cukup', 'D' => 'Kurang Baik', 'E' => 'Tidak Baik'][$gradeLetter] ?? 'Baik';

            if (in_array($gradeLetter, ['C', 'D', 'E'])) {
                $pimpinanText = 'Pimpinan Gerai belum mampu menerapkan & mengarahkan dengan baik standar pelayanan pelanggan kepada karyawan sesuai standar PSSO.';
                $posisiText = "2. Gerai {$gerai->kode_gerai} belum mampu menempatkan posisi kinerja gerainya untuk berada di atas Standar Kinerja monitoring semua gerai BIRU.";
            }

            $lastReportType = class_basename($lastReportModel) === 'ReMonitoringReport' ? 're-monitoring' : 'monitoring';
            if ($lastReportType === 're-monitoring') {
                $periodeLabel = 'Re-Monitoring ' . $lastReportModel->checkin_at->locale('id')->isoFormat('MMMM YYYY');
            }

            $belowAvgCount = $historyData->slice(0, -1)
                ->filter(fn($h) => $h['nilai'] !== null && round((float) $h['nilai']) < MonitoringReport::GRADE_B_THRESHOLD)
                ->count();
        }

        $catatan = "1. Kinerja operasional serta pemahaman untuk Pengawas & Karyawan {$gradeLabel}.\n2. Kebersihan di halaman gerai serta kelengkapan teknis di gerai {$gradeLabel}.\n3. {$pimpinanText}";

        $poin1 = "1. Poin kinerja di gerai {$gerai->kode_gerai} berada di atas Standar Kinerja pada {$periodeLabel} dan " . ($belowAvgCount > 0
            ? "pernah {$belowAvgCount}x berada di bawah Standar Kinerja pada monitoring periode sebelumnya."
            : 'belum pernah berada di bawah Standar Kinerja pada monitoring periode sebelumnya.');
        $keterangan = "{$poin1}\n{$posisiText}\n3. Gerai {$gerai->kode_gerai} masuk dalam Grade {$gradeLetter} dengan kategori {$gradeLabel}.";

        $report->update([
            'catatan' => $catatan,
            'keterangan' => $keterangan,
            'tanggal' => now()->toDateString(),
        ]);

        return redirect("/{$this->prefix()}/{$report->id}")->with('success', 'Laporan evaluasi berhasil dibuat.');
    }

    public function assessment($id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        $prefix = $this->prefix();
        $incomplete = [];

        $lastMonReport = MonitoringReport::where('gerai_id', $report->gerai_id)
            ->where('type', 'monitoring')
            ->whereNotNull('submit_at')
            ->latest('checkin_at')
            ->first();

        $lastRemonReport = ReMonitoringReport::where('gerai_id', $report->gerai_id)
            ->whereNotNull('submit_at')
            ->latest('checkin_at')
            ->first();

        $lastReport = $lastRemonReport && (!$lastMonReport || $lastRemonReport->checkin_at->gt($lastMonReport->checkin_at))
            ? $lastRemonReport : $lastMonReport;

        $lastReportType = null;
        if ($lastReport) {
            $lastReportType = class_basename($lastReport) === 'ReMonitoringReport' ? 're-monitoring' : 'monitoring';
        }

        $belowAverageCount = 0;
        if ($lastReport) {
            $prevReports = MonitoringReport::where('gerai_id', $report->gerai_id)
                ->where('type', 'monitoring')
                ->whereNotNull('submit_at')
                ->where('checkin_at', '<', $lastReport->checkin_at)
                ->orderByDesc('checkin_at')
                ->get();
            $belowAverageCount = $prevReports->filter(fn($r) => $r->nilai !== null && round((float) $r->nilai) < MonitoringReport::GRADE_B_THRESHOLD)->count();
        }

        return response()->view('evaluasi.assessment', compact('report', 'prefix', 'incomplete', 'lastReport', 'lastReportType', 'belowAverageCount'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function update(Request $request, $id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        $validated = $request->validate([
            'catatan' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $report->update($validated);

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect("/{$this->prefix()}/{$report->id}");
    }

    public function cancelAssessment(Request $request, $id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        return redirect("/{$this->prefix()}/{$report->id}")->with('success', 'Perubahan berhasil dibatalkan.');
    }

    public function destroy(Request $request, $id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        $report->delete();

        if ($request->input('_from') === 'assessment') {
            return redirect("/{$this->prefix()}")->with('success', 'Laporan evaluasi berhasil dihapus.');
        }

        return redirect("/report/evaluasi")->with('success', 'Laporan evaluasi berhasil dihapus.');
    }

    public function submit(Request $request, $id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        $validated = $request->validate([
            'catatan' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        if (!empty($validated['catatan']) || !empty($validated['keterangan'])) {
            $report->update($validated);
        }

        $report->update(['tanggal' => now()->toDateString()]);

        return redirect("/{$this->prefix()}/{$report->id}")->with('success', 'Laporan evaluasi berhasil disimpan.');
    }

    public function show($id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        $prefix = $this->prefix();
        $geraiId = $report->gerai_id;

        $historyBuilder = new EvaluasiHistoryBuilder($geraiId);
        $historyData = $historyBuilder->mapHistoryData();
        $lastReportModel = $historyBuilder->getLastReport();

        $lastReportType = null;
        $lastReportGrade = null;
        $lastReportMonth = null;
        $belowAverageCount = 0;

        if ($lastReportModel) {
            $lastReportType = class_basename($lastReportModel) === 'ReMonitoringReport' ? 're-monitoring' : 'monitoring';
            $lastReportGrade = $lastReportModel->grade;
            $lastReportMonth = $lastReportModel->checkin_at->locale('id')->isoFormat('MMMM YYYY');

            $belowAverageCount = $historyData->slice(0, -1)
                ->filter(fn($h) => $h['nilai'] !== null && round((float) $h['nilai']) < MonitoringReport::GRADE_B_THRESHOLD)
                ->count();
        }

        return view('evaluasi.show', compact('report', 'prefix', 'historyData', 'lastReportType', 'belowAverageCount', 'lastReportGrade', 'lastReportMonth'));
    }

    public function pdf($id)
    {
        $report = EvaluasiReport::findOrFail($id);
        $this->authorizeReport($report);

        $filename = "Evaluasi - {$report->gerai->kode_gerai} - " . $report->tanggal->locale('id')->isoFormat('MMMM YYYY');
        $tempDir = storage_path('app/temp-pdf');
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $excelPath = $this->excel($report->id, $tempDir);

        if ($excelPath && file_exists($excelPath)) {
            $pdfPath = $tempDir . '/' . $filename . '.pdf';
            $pyScript = base_path('scripts/xlwings-to-pdf.py');
            $cmd = 'python ' . escapeshellarg($pyScript) . ' ' . escapeshellarg($excelPath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
            exec($cmd, $output, $returnCode);
            @unlink($excelPath);

            if ($returnCode === 0 && file_exists($pdfPath)) {
                return response()->download($pdfPath, $filename . '.pdf')->deleteFileAfterSend(true);
            }
        }

        $geraiId = $report->gerai_id;
        $historyBuilder = new EvaluasiHistoryBuilder($geraiId);
        $historyData = $historyBuilder->mapHistoryData();
        $lastReport = $historyBuilder->getLastReport();
        $fontLoaded = $this->registerArimoFont();

        $pdf = Pdf::loadView('evaluasi.pdf', compact('report', 'historyData', 'lastReport', 'fontLoaded'));

        return $pdf->download($filename . '.pdf');
    }

    protected function getTargetPeriode($report): ?string
    {
        $allPeriods = MonitoringReport::where('gerai_id', $report->gerai_id)
            ->whereIn('type', ['monitoring', 'import'])
            ->whereNotNull('submit_at')
            ->selectRaw('periode_label, MAX(checkin_at) as last_checkin')
            ->groupBy('periode_label')
            ->orderByRaw('MAX(checkin_at) desc')
            ->pluck('periode_label');

        if ($allPeriods->count() >= 2) {
            return $allPeriods[1];
        } elseif ($allPeriods->count() === 1) {
            return $allPeriods[0];
        }
        return null;
    }

    protected function getReportDateForFilename($report, string $tz): string
    {
        return $report->tanggal->format('Y-m-d');
    }

    protected function useExcelPdf(): bool
    {
        return true;
    }
}
