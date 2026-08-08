<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use App\Services\ExcelXmlHelpers;

class PraMonitoringController extends MonitoringController
{
    protected $type = 'pra-monitoring';

    protected function modelClass(): string
    {
        return PraMonitoringReport::class;
    }

    public function excel($id, $outputDir = null)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);
        set_time_limit(120);

        $tz = 'Asia/Jakarta';

        $headerReplacements = [
            '{nama_gerai}'       => $report->gerai->nama_gerai,
            '{kode_gerai}'       => $report->gerai->kode_gerai,
            '{franchisee}'       => strtoupper($report->gerai->franchisee),
            '{lokasi}'           => $report->location ?? '-',
            '{tanggal}'          => $report->checkin_at->setTimezone($tz)->format('d-m-Y'),
            '{tanggal_lengkap}'  => $report->checkin_at->setTimezone($tz)->locale('id')->isoFormat('D MMMM YYYY'),
            '{checkin}'          => $report->checkin_at->setTimezone($tz)->format('d-m-Y H:i:s'),
            '{submit}'           => $report->submit_at ? $report->submit_at->setTimezone($tz)->format('d-m-Y H:i:s') : '-',
            '{petugas}'          => $report->user?->name ?? '-',
            '{periode}'          => strtoupper($report->periode_label ?? $report->checkin_at->setTimezone($tz)->locale('id')->isoFormat('MMMM YYYY') ?? '-'),
            '{type}'             => 'Pra-Monitoring',
            '{nama_kota}'        => $report->gerai->nama_kota ?? '-',
            '{area}'             => $report->gerai->area ?? '-',
            '{opening_at}'       => $report->gerai->opening_at ? strtoupper($report->gerai->opening_at->locale('id')->isoFormat('D MMMM YYYY')) : '-',
            '{bulan_tahun}'      => strtoupper($report->checkin_at->setTimezone($tz)->locale('id')->isoFormat('MMMM YYYY')),
        ];

        return $this->buildExcel($report, $headerReplacements, $outputDir);
    }

    protected function fillSheet1Custom(DOMDocument $dom1, DOMXPath $xpath1, string $ns, float $totalScore, string $grade, string $kesimpulanText, int $wrapStyleIdx = 0): void
    {
        static::xmlSetNumber($xpath1, $dom1, $ns, 'E32', round($totalScore));
        static::xmlSetNumber($xpath1, $dom1, $ns, 'E33', \App\Models\MonitoringReport::GRADE_B_THRESHOLD);
        static::xmlSetInlineStr($xpath1, $dom1, $ns, 'E34', $grade);

        // A38: "Gerai masuk dalam Grade [X] dengan kategori:" (Grade bold)
        $cells38 = $xpath1->query("//s:c[@r='A38']");
        if ($cells38->length > 0) {
            $cell = $cells38->item(0);
            $cell->setAttribute('t', 'inlineStr');
            foreach (['v', 'is'] as $tag) {
                $existing = $cell->getElementsByTagNameNS($ns, $tag)->item(0);
                if ($existing) $cell->removeChild($existing);
            }
            $is = $dom1->createElementNS($ns, 'is');
            $is->appendChild(static::xmlMakeRun($dom1, $ns, 'Gerai masuk dalam '));
            $is->appendChild(static::xmlMakeRun($dom1, $ns, "Grade {$grade}", true));
            $is->appendChild(static::xmlMakeRun($dom1, $ns, ' dengan kategori:'));
            $cell->appendChild($is);
        }

        // A41: set wrapText+center via cell style
        $cells41 = $xpath1->query("//s:c[@r='A41']");
        if ($cells41->length > 0) {
            $cell = $cells41->item(0);
            if ($wrapStyleIdx > 0) $cell->setAttribute('s', (string) $wrapStyleIdx);
            $cell->setAttribute('t', 'inlineStr');
            foreach (['v', 'is'] as $tag) {
                $existing = $cell->getElementsByTagNameNS($ns, $tag)->item(0);
                if ($existing) $cell->removeChild($existing);
            }
            $is = $dom1->createElementNS($ns, 'is');
            $t = $dom1->createElementNS($ns, 't');
            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $t->appendChild($dom1->createTextNode($kesimpulanText));
            $is->appendChild($t);
            $cell->appendChild($is);
        }

        // Merge A41:L41
        $sheetData = $xpath1->query('//s:sheetData')->item(0);
        $mergeCells = $sheetData->nextSibling;
        while ($mergeCells && $mergeCells->localName !== 'mergeCells') {
            $mergeCells = $mergeCells->nextSibling;
        }
        if (!$mergeCells || $mergeCells->localName !== 'mergeCells') {
            $mergeCells = $dom1->createElementNS($ns, 'mergeCells');
            $sheetData->parentNode->insertBefore($mergeCells, $sheetData->nextSibling);
        }
        // Check if A41:L41 already exists
        $mergeExists = false;
        foreach ($xpath1->query('//s:mergeCells/s:mergeCell') as $mc) {
            if ($mc->getAttribute('ref') === 'A41:L41') { $mergeExists = true; break; }
        }
        if (!$mergeExists) {
            $mergeCell = $dom1->createElementNS($ns, 'mergeCell');
            $mergeCell->setAttribute('ref', 'A41:L41');
            $mergeCells->appendChild($mergeCell);
        }
        // Update count attribute
        $mergeCells->setAttribute('count', (string) $xpath1->query('//s:mergeCells/s:mergeCell')->length);

        // Row height 47 if >100 chars
        $rowHeight = mb_strlen($kesimpulanText) > 100 ? 47 : 15;
        $rows41 = $xpath1->query("//s:row[@r='41']");
        if ($rows41->length > 0) {
            $row = $rows41->item(0);
            $row->setAttribute('ht', (string) $rowHeight);
            $row->setAttribute('customHeight', '1');
        }
    }

    protected function fillSheet3Custom(DOMDocument $dom3, DOMXPath $xpath3, string $ns3, float $totalScore, string $tanggalLengkap): void
    {
        static::xmlSetInlineStr($xpath3, $dom3, $ns3, 'B1', $tanggalLengkap);
        static::xmlSetNumber($xpath3, $dom3, $ns3, 'B2', round($totalScore));
        static::xmlSetNumber($xpath3, $dom3, $ns3, 'B3', \App\Models\MonitoringReport::GRADE_B_THRESHOLD);
    }

    protected function onPhase3Cell(string $sheetName, int $ssIndex, array $ssIndexText, array $ssIndexScore, DOMElement $cell, DOMDocument $dom, array $items): void
    {
        if ($sheetName !== 'xl/worksheets/sheet2.xml') return;

        $placeholder = $ssIndexText[$ssIndex] ?? '';
        if (!preg_match('/\{item_score:(.*?)\}/', $placeholder, $m)) return;

        $norm = function($s) {
            $s = str_replace(["\xC2\xAB", "\xC2\xBB", "\xE2\x80\x98", "\xE2\x80\x99",
                "\xE2\x80\x9A", "\xE2\x80\x9B", "\xE2\x80\x9C", "\xE2\x80\x9D",
                "\xE2\x80\x9E", "\xE2\x80\x9F", "\xE2\x80\xB9", "\xE2\x80\xBA"], '"', $s);
            return trim(preg_replace('/\s+/', ' ', $s));
        };

        $normItems = [];
        foreach ($items as $name => $data) {
            $normItems[$norm($name)] = $data;
        }

        $itemKey = $norm(trim($m[1]));
        if (!isset($normItems[$itemKey])) return;

        $scoreVal = (float)$ssIndexScore[$ssIndex];
        $bobotVal = (float)$normItems[$itemKey]['bobot'];
        if ($scoreVal >= $bobotVal) return;

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $ref = $cell->getAttribute('r');
        $rowNum = preg_replace('/[A-Z]/', '', $ref);
        $mRef = 'M' . $rowNum;
        $row = $cell->parentNode;

        $mCell = null;
        foreach ($row->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'c' && $child->getAttribute('r') === $mRef) {
                $mCell = $child;
                break;
            }
        }

        if ($mCell) {
            while ($mCell->firstChild) $mCell->removeChild($mCell->firstChild);
            $mCell->removeAttribute('t');
        } else {
            $mCell = $dom->createElementNS($ns, 'c');
            $mCell->setAttribute('r', $mRef);
            $row->appendChild($mCell);
        }

        $mCell->setAttribute('t', 'inlineStr');
        $is = $dom->createElementNS($ns, 'is');
        $t = $dom->createElementNS($ns, 't');
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $t->appendChild($dom->createTextNode('Perlu perbaikan'));
        $is->appendChild($t);
        $mCell->appendChild($is);
    }

    protected function fillSheet2Custom(DOMDocument $dom2, DOMXPath $xpath2, string $ns2, array $findingLines, $report, array $lowItems, array $sheet3ZeroItems, array $items, $zip): bool
    {
        $sheetData = $xpath2->query('//s:sheetData')->item(0);
        if (!$sheetData) return false;

        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        $ssTextByIndex = [];
        if ($ssContent !== false) {
            $ssDom = new DOMDocument;
            $ssDom->loadXML($ssContent);
            $ssXpath = new DOMXPath($ssDom);
            $ssXpath->registerNamespace('s', $ns2);
            foreach ($ssXpath->query('//s:si') as $idx => $si) {
                $t = $ssXpath->query('.//s:t', $si)->item(0);
                $ssTextByIndex[$idx] = $t ? $t->textContent : '';
            }
        }

        $getCellText = function($cell) use ($ssTextByIndex) {
            $type = $cell->getAttribute('t');
            if ($type === 's') {
                $v = $cell->getElementsByTagName('v')->item(0);
                if ($v) { $idx = (int)$v->textContent; return $ssTextByIndex[$idx] ?? ''; }
            } elseif ($type === 'inlineStr') {
                $t = $cell->getElementsByTagName('t')->item(0);
                return $t ? $t->textContent : '';
            }
            return '';
        };

        // --- Find PA & NOTE rows (match monitoring Sheet3 line 1805-1832) ---
        $paRn = 0;
        $noteRn = 0;
        $paRow = null;
        $noteRow = null;
        foreach ($xpath2->query('//s:sheetData/s:row') as $row) {
            $r = (int)$row->getAttribute('r');
            foreach ($xpath2->query('s:c', $row) as $cell) {
                $type = $cell->getAttribute('t');
                $text = '';
                if ($type === 's') {
                    $v = $cell->getElementsByTagName('v')->item(0);
                    if ($v) {
                        $idx = (int)$v->textContent;
                        $text = $ssTextByIndex[$idx] ?? '';
                    }
                } elseif ($type === 'inlineStr') {
                    $t = $cell->getElementsByTagName('t')->item(0);
                    $text = $t ? $t->textContent : '';
                }
                if (str_contains($text, 'Temuan dengan kategori Peringatan Awal')) {
                    $paRn = $r;
                    $paRow = $row;
                } elseif ($text === 'NOTE:' && $paRn > 0 && $noteRn === 0) {
                    $noteRn = $r;
                    $noteRow = $row;
                }
            }
        }

        // --- Info block PA→NOTE (match monitoring Sheet3 line 1834-1941) ---
        if ($paRow && $noteRow) {
            $rowsToRemove = [];
            foreach ($xpath2->query('//s:sheetData/s:row[@r > ' . $paRn . ' and @r < ' . $noteRn . ']') as $row) {
                $rowsToRemove[] = $row;
            }
            foreach ($rowsToRemove as $row) {
                $sheetData->removeChild($row);
            }

            $infoRn = $paRn + 1;
            // (makeBRow via ExcelXmlHelpers trait)

            $infoRows = [];
            $pengawas = $report->pengawas ?? '';
            if ($pengawas !== '') {
                foreach (preg_split('/\r?\n/', $pengawas) as $line) {
                    if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom2, $ns2, trim($line), $infoRn);
                }
            }
            $aj = $report->rata_rata_aj ?? '';
            if ($aj !== '') {
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Rerata AJ ± ' . $aj . ' gln/hr', $infoRn);
            }
            $mo = $report->mesin_ozon ?? '';
            if ($mo !== '') {
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'MO: ' . $mo, $infoRn);
            }
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);
            $paLines = $findingLines['peringatan_awal'] ?? [];
            foreach ($paLines as $line) {
                if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom2, $ns2, $line, $infoRn);
            }
            $noteContent = $report->note ?? '';
            if ($noteContent !== '') {
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Note:', $infoRn);
                foreach (preg_split('/\r?\n/', $noteContent) as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    $line = preg_replace('/^-(?=[^\s-])/', '- ', $line);
                    $infoRows[] = static::xmlMakeBRow($dom2, $ns2, $line, $infoRn);
                }
            }
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Checklist tampilan gerai:', $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi cat: ' . ($report->kondisi_cat ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi awning: ' . ($report->kondisi_awning ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi vinyl reklame dinding/jalan: ' . ($report->kondisi_vinyl ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi stiker kaca: ' . ($report->kondisi_stiker_kaca ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);

            foreach ($infoRows as $row) {
                $sheetData->insertBefore($row, $noteRow);
            }

            $allRows = [];
            foreach ($sheetData->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'row') {
                    $allRows[] = $child;
                }
            }
            $renumberRn = $infoRn;
            $pastPa = false;
            $infoIdx = 0;
            foreach ($allRows as $row) {
                $rAttr = (int)$row->getAttribute('r');
                if (!$pastPa) {
                    if ($rAttr === $paRn) $pastPa = true;
                    continue;
                }
                if ($infoIdx < count($infoRows)) {
                    $infoIdx++;
                    continue;
                }
                $row->setAttribute('r', (string)$renumberRn);
                foreach ($xpath2->query('s:c', $row) as $cell) {
                    $ref = $cell->getAttribute('r');
                    $cell->setAttribute('r', preg_replace('/\d+$/', (string)$renumberRn, $ref));
                }
                $renumberRn++;
            }
        }

        // --- Zero-score items (match monitoring Sheet3 line 1943-2033) ---
        $makeRow = function($rn, $bText = null) use ($dom2, $ns2) {
            $row = $dom2->createElementNS($ns2, 'row');
            $row->setAttribute('r', (string)$rn);
            $row->setAttribute('spans', '1:13');
            for ($col = 'A'; $col !== 'N'; $col++) {
                $ref = $col . $rn;
                $cell = $dom2->createElementNS($ns2, 'c');
                $cell->setAttribute('r', $ref);
                $cell->setAttribute('s', '1');
                if ($col === 'B' && $bText !== null) {
                    $cell->setAttribute('t', 'inlineStr');
                    $is = $dom2->createElementNS($ns2, 'is');
                    $t = $dom2->createElementNS($ns2, 't');
                    $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                    $t->appendChild($dom2->createTextNode($bText));
                    $is->appendChild($t);
                    $cell->appendChild($is);
                }
                $row->appendChild($cell);
            }
            return $row;
        };

        $zeroItems = [];
        foreach ($items as $name => $data) {
            if ((int)$data['score'] === 0) $zeroItems[] = $name;
        }

        if (!empty($zeroItems)) {
            $n = count($zeroItems);

            $firstLine = '1. ' . $zeroItems[0];
            foreach ($xpath2->query('//s:sheetData/s:row[@r=120]/s:c[@r="B120"]') as $cell) {
                while ($cell->firstChild) $cell->removeChild($cell->firstChild);
                $cell->setAttribute('s', '1');
                $cell->setAttribute('t', 'inlineStr');
                $is = $dom2->createElementNS($ns2, 'is');
                $t = $dom2->createElementNS($ns2, 't');
                $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                $t->appendChild($dom2->createTextNode($firstLine));
                $is->appendChild($t);
                $cell->appendChild($is);
            }

            if ($n >= 2) {
                $refRow121 = null;
                foreach ($xpath2->query('//s:sheetData/s:row[@r=121]') as $row) {
                    $refRow121 = $row;
                }

                $delta = $n - 1;
                $rowsToShift = [];
                foreach ($xpath2->query('//s:sheetData/s:row[@r >= 121]') as $row) {
                    $rowsToShift[] = $row;
                }
                foreach ($rowsToShift as $row) {
                    $oldR = (int)$row->getAttribute('r');
                    $newR = $oldR + $delta;
                    $row->setAttribute('r', (string)$newR);
                    foreach ($xpath2->query('s:c', $row) as $cell) {
                        $ref = $cell->getAttribute('r');
                        $cell->setAttribute('r', preg_replace('/\d+$/', (string)$newR, $ref));
                    }
                }

                $rn = 121;
                for ($i = 1; $i < $n; $i++) {
                    $line = ($i + 1) . '. ' . $zeroItems[$i];
                    $sheetData->insertBefore($makeRow($rn, $line), $refRow121);
                    $rn++;
                }
            }
        } else {
            $rowsToRemove = [];
            foreach ($xpath2->query('//s:sheetData/s:row[@r >= 121 and @r <= 122]') as $row) {
                $rowsToRemove[] = $row;
            }
            foreach ($rowsToRemove as $row) {
                $sheetData->removeChild($row);
            }

            $rowsToShift = [];
            foreach ($xpath2->query('//s:sheetData/s:row[@r >= 123]') as $row) {
                $rowsToShift[] = $row;
            }
            foreach ($rowsToShift as $row) {
                $oldR = (int)$row->getAttribute('r');
                $newR = $oldR - 2;
                $row->setAttribute('r', (string)$newR);
                foreach ($xpath2->query('s:c', $row) as $cell) {
                    $ref = $cell->getAttribute('r');
                    $cell->setAttribute('r', preg_replace('/\d+$/', (string)$newR, $ref));
                }
            }
        }

        // --- MINOR & MAJOR sections (match monitoring Sheet3 line 2037-2136) ---
        $minorRn = 0;
        $majorRn = 0;
        foreach ($xpath2->query('//s:sheetData/s:row') as $row) {
            $r = (int)$row->getAttribute('r');
            foreach ($xpath2->query('s:c', $row) as $cell) {
                $ref = $cell->getAttribute('r');
                if (!str_starts_with($ref, 'B')) continue;
                $type = $cell->getAttribute('t');
                $text = '';
                if ($type === 's') {
                    $v = $cell->getElementsByTagName('v')->item(0);
                    if ($v) {
                        $idx = (int)$v->textContent;
                        $text = $ssTextByIndex[$idx] ?? '';
                    }
                } elseif ($type === 'inlineStr') {
                    $t = $cell->getElementsByTagName('t')->item(0);
                    $text = $t ? $t->textContent : '';
                }
                if (str_contains($text, 'Temuan dengan kategori MINOR')) {
                    $minorRn = $r;
                } elseif (str_contains($text, 'Temuan dengan kategori MAJOR')) {
                    $majorRn = $r;
                }
            }
        }

        if ($minorRn > 0 && $majorRn > 0 && $majorRn > $minorRn) {
            $minorPlaceholders = [];
            $majorPlaceholders = [];
            $foundMinor = false;
            $foundMajor = false;
            foreach ($sheetData->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE || $child->localName !== 'row') continue;
                $r = (int)$child->getAttribute('r');
                if ($r === $minorRn) { $foundMinor = true; continue; }
                if ($r === $majorRn) { $foundMajor = true; continue; }
                if ($foundMajor) {
                    $majorPlaceholders[] = $child;
                } elseif ($foundMinor) {
                    $minorPlaceholders[] = $child;
                }
            }

            foreach ($minorPlaceholders as $row) $sheetData->removeChild($row);
            foreach ($majorPlaceholders as $row) $sheetData->removeChild($row);

            $majorRowElement = null;
            foreach ($sheetData->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE || $child->localName !== 'row') continue;
                if ((int)$child->getAttribute('r') === $majorRn) {
                    $majorRowElement = $child;
                    break;
                }
            }

            if ($majorRowElement) {
                $makeDataRow = function ($rn, $bText = null) use ($dom2, $ns2) {
                    $row = $dom2->createElementNS($ns2, 'row');
                    $row->setAttribute('r', (string)$rn);
                    $row->setAttribute('spans', '1:15');
                    $cell = $dom2->createElementNS($ns2, 'c');
                    $cell->setAttribute('r', 'B' . $rn);
                    $cell->setAttribute('s', '1');
                    if ($bText !== null) {
                        $cell->setAttribute('t', 'inlineStr');
                        $is = $dom2->createElementNS($ns2, 'is');
                        $t = $dom2->createElementNS($ns2, 't');
                        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                        $t->appendChild($dom2->createTextNode($bText));
                        $is->appendChild($t);
                        $cell->appendChild($is);
                    }
                    $row->appendChild($cell);
                    return $row;
                };

                $rn = $minorRn + 1;

                foreach (($findingLines['minor'] ?? []) as $line) {
                    if (trim($line) === '') continue;
                    $sheetData->insertBefore($makeDataRow($rn++, trim($line)), $majorRowElement);
                }

                $sheetData->insertBefore($makeDataRow($rn++), $majorRowElement);

                $majorRowElement->setAttribute('r', (string)$rn);
                foreach ($xpath2->query('s:c', $majorRowElement) as $cell) {
                    $ref = $cell->getAttribute('r');
                    $cell->setAttribute('r', preg_replace('/\d+$/', (string)$rn, $ref));
                }
                $rn++;

                foreach (($findingLines['mayor'] ?? []) as $line) {
                    if (trim($line) === '') continue;
                    $sheetData->appendChild($makeDataRow($rn++, trim($line)));
                }
            }
        }

        // --- Penjelasan Formulir 3 (match monitoring Sheet3 line 2296-2410) ---
        $penjelasanIsi3 = $report->penjelasan_isi_3 ?? [];

        // Filter: only keep entries whose key (item ID) matches a current zero-score item
        $zeroScoreIds = [];
        foreach ($zeroItems as $zName) {
            if (isset($items[$zName]['item_id'])) {
                $zeroScoreIds[] = (int) $items[$zName]['item_id'];
            }
        }
        $penjelasanIsi3 = array_filter($penjelasanIsi3, fn($k) => in_array((int)$k, $zeroScoreIds), ARRAY_FILTER_USE_KEY);
        $penjelasanIsi3 = array_filter($penjelasanIsi3, fn($v) => trim($v) !== '');
        $penjelasanIsi3 = array_values($penjelasanIsi3);

        if (!empty($penjelasanIsi3)) {
            $penjelasanRn3 = 0;
            $penjelasanRow3 = null;
            foreach ($xpath2->query('//s:sheetData/s:row') as $row) {
                $r = (int)$row->getAttribute('r');
                foreach ($xpath2->query('s:c', $row) as $cell) {
                    $text = '';
                    $type = $cell->getAttribute('t');
                    if ($type === 's') {
                        $v = $cell->getElementsByTagName('v')->item(0);
                        if ($v) { $idx = (int)$v->textContent; $text = $ssTextByIndex[$idx] ?? ''; }
                    } elseif ($type === 'inlineStr') {
                        $t = $cell->getElementsByTagName('t')->item(0);
                        $text = $t ? $t->textContent : '';
                    }
                    if (str_contains($text, 'PENJELASAN')) {
                        $penjelasanRn3 = $r;
                        $penjelasanRow3 = $row;
                        break 2;
                    }
                }
            }

            if (!$penjelasanRow3) {
                $lastRn = 0;
                foreach ($xpath2->query('//s:sheetData/s:row') as $row) {
                    $rr = (int)$row->getAttribute('r');
                    if ($rr > $lastRn) $lastRn = $rr;
                }
                $penjelasanRn3 = $lastRn + 1;
                $penjelasanRow3 = $dom2->createElementNS($ns2, 'row');
                $penjelasanRow3->setAttribute('r', (string)$penjelasanRn3);
                $penjelasanRow3->setAttribute('spans', '1:15');
                $cell = $dom2->createElementNS($ns2, 'c');
                $cell->setAttribute('r', 'A' . $penjelasanRn3);
                $cell->setAttribute('t', 'inlineStr');
                $cell->setAttribute('s', '12');
                $is = $dom2->createElementNS($ns2, 'is');
                $t = $dom2->createElementNS($ns2, 't');
                $t->appendChild($dom2->createTextNode('PENJELASAN:'));
                $is->appendChild($t);
                $cell->appendChild($is);
                $penjelasanRow3->appendChild($cell);
                $sheetData->appendChild($penjelasanRow3);
            }

            $rn = $penjelasanRn3 + 1;
            $newRows = [];
            $i = 1;
            foreach ($penjelasanIsi3 as $teks) {
                $row = $dom2->createElementNS($ns2, 'row');
                $row->setAttribute('r', (string)$rn);
                $row->setAttribute('spans', '1:15');
                $cell = $dom2->createElementNS($ns2, 'c');
                $cell->setAttribute('r', 'B' . $rn);
                $cell->setAttribute('t', 'inlineStr');
                $cell->setAttribute('s', '1');
                $is = $dom2->createElementNS($ns2, 'is');
                $t = $dom2->createElementNS($ns2, 't');
                $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                $t->appendChild($dom2->createTextNode(($i++) . '. ' . trim($teks)));
                $is->appendChild($t);
                $cell->appendChild($is);
                $row->appendChild($cell);
                $newRows[] = $row;
                $rn++;
            }
            $ref = $penjelasanRow3->nextSibling;
            foreach ($newRows as $row) {
                $sheetData->insertBefore($row, $ref);
            }

            $allRows = [];
            foreach ($sheetData->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'row') {
                    $allRows[] = $child;
                }
            }
            $pastSection = false;
            $inserted = count($newRows);
            $skipped = 0;
            foreach ($allRows as $row) {
                $rAttr = (int)$row->getAttribute('r');
                if (!$pastSection) {
                    if ($rAttr === $penjelasanRn3) $pastSection = true;
                    continue;
                }
                if ($skipped < $inserted) { $skipped++; continue; }
                $row->setAttribute('r', (string)$rn);
                foreach ($xpath2->query('s:c', $row) as $cell) {
                    $ref = $cell->getAttribute('r');
                    $cell->setAttribute('r', preg_replace('/\d+$/', (string)$rn, $ref));
                }
                $rn++;
            }
        } else {
            // No penjelasan: clear the PENJELASAN row from the template
            $toRemove = [];
            foreach ($xpath2->query('//s:sheetData/s:row') as $row) {
                foreach ($xpath2->query('s:c', $row) as $cell) {
                    $text = '';
                    $type = $cell->getAttribute('t');
                    if ($type === 's') {
                        $v = $cell->getElementsByTagName('v')->item(0);
                        if ($v) { $idx = (int)$v->textContent; $text = $ssTextByIndex[$idx] ?? ''; }
                    } elseif ($type === 'inlineStr') {
                        $t = $cell->getElementsByTagName('t')->item(0);
                        $text = $t ? $t->textContent : '';
                    }
                    if (str_contains($text, 'PENJELASAN')) {
                        $toRemove[] = $row;
                        break;
                    }
                }
            }
            foreach ($toRemove as $row) {
                $row->parentNode->removeChild($row);
            }
        }

        return true;
    }

    protected function checkinFormPeriods(Gerai $gerai) { return collect(); }
    protected function checkinFormExistingPeriods(Gerai $gerai): array { return []; }
    protected function doCheckinExtraValidation(): array { return []; }
    protected function shouldCheckDuplicate(): bool { return false; }
    protected function doCheckinExtraData(array $validated): array { return ['is_pairing' => request()->input('pairing') === '1']; }
    protected function reportListRoute(): string { return '/report/pra-monitoring'; }

    protected function requiredFindingFields(): array
    {
        return ['pengawas', 'rata_rata_aj', 'mesin_ozon', 'peringatan_awal'];
    }

    protected function filterFindingData(array $data): array
    {
        unset($data['tds']);
        return $data;
    }

    protected function useExcelPdf(): bool
    {
        return true;
    }

    protected function postProcessExcel(string $outPath): void
    {
        $pyScript = base_path('scripts/format_pra_sheet2.py');
        exec('python ' . escapeshellarg($pyScript) . ' ' . escapeshellarg($outPath) . ' 2>&1', $pyOut, $pyErr);
        if ($pyErr !== 0 || !empty($pyOut)) {
            \Log::info('format_pra_sheet2', ['output' => $pyOut, 'exit' => $pyErr]);
        }
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

    public static function uploadWordTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'template' => 'required|file|mimes:doc,docx',
        ]);

        $file = $request->file('template');

        // Detect real format from magic bytes (filename extension may be wrong)
        $head = '';
        $fh = @fopen($file->getRealPath(), 'rb');
        if ($fh) {
            $head = fread($fh, 4);
            fclose($fh);
        }
        $isLegacyDoc = $head === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['doc', 'docx'])) {
            $ext = $isLegacyDoc ? 'doc' : 'docx';
        }

        $tmpInput = sys_get_temp_dir() . '/' . uniqid('mars_word_') . '.' . $ext;
        $tmpOutput = sys_get_temp_dir() . '/' . uniqid('mars_word_') . '.docx';

        try {
            $file->move(sys_get_temp_dir(), basename($tmpInput));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses file template. Silakan coba lagi.');
        }

        $script = base_path('scripts/prepare_word_template.py');
        $cmd = 'python ' . escapeshellarg($script) . ' ' . escapeshellarg($tmpInput) . ' ' . escapeshellarg($tmpOutput) . ' 2>&1';
        exec($cmd, $out, $code);

        if ($code === 0 && file_exists($tmpOutput)) {
            \Illuminate\Support\Facades\Storage::put('word-template-pra-monitoring.docx', file_get_contents($tmpOutput));
            \Illuminate\Support\Facades\Storage::delete('word-template-pra-monitoring.doc');
            @unlink($tmpInput);
            @unlink($tmpOutput);
            return back()->with('success', 'Template Surat Word Pra-Monitoring berhasil diupload.');
        }

        @unlink($tmpInput);
        @unlink($tmpOutput);
        return back()->with('error', 'Gagal memproses Template Surat Word (pastikan Microsoft Word terinstal dan file valid). Template lama tetap dipakai.');
    }

    public function pdf($id)
    {
        // PDF di halaman detail (tanpa ?excel=1) disamakan dengan monitoring/re-monitoring,
        // yaitu render DomPDF. Excel→PDF + cover letter Word hanya dari tombol PDF di Data
        // Laporan (param excel=1).
        if (!request()->boolean('excel')) {
            return parent::pdf($id);
        }

        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->with('gerai', 'user')->findOrFail($id);
        $this->authorizeReport($report);

        $revisi = request()->boolean('revisi');
        $typeName = $this->getTypeName();
        $periode = $report->periode_label ?? $report->checkin_at?->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('MMMM YYYY') ?? '';
        $filename = "{$typeName} - {$report->gerai->kode_gerai} - {$periode}";

        $tempDir = storage_path('app/temp-pdf');
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        // 1. Generate laporan PDF — utamakan hasil Excel→PDF (xlwings), fallback DomPDF
        $reportPdf = null;
        if (!request()->has('dompdf')) {
            $excelPath = $this->excel($report->id, $tempDir);
            if ($excelPath && file_exists($excelPath)) {
                $pdfPath = $tempDir . '/' . uniqid('report_') . '.pdf';
                $pyScript = base_path('scripts/xlwings-to-pdf.py');
                $cmd = 'python ' . escapeshellarg($pyScript) . ' ' . escapeshellarg($excelPath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
                exec($cmd, $output, $returnCode);
                @unlink($excelPath);
                if ($returnCode === 0 && file_exists($pdfPath)) {
                    $reportPdf = $pdfPath;
                }
            }
        }
        if (!$reportPdf) {
            $reportPdf = $this->pdfDompdf($report, $revisi, $filename, $tempDir . '/' . uniqid('report_') . '.pdf');
        }

        if (request()->boolean('no_cover')) {
            return response()->download($reportPdf, $filename . '.pdf')->deleteFileAfterSend(true);
        }

        $filename = 'CL ' . $filename;

        // 2. Cover letter + merge
        $coverPdf = $this->generateCoverLetter($report, $reportPdf, $tempDir);
        if ($coverPdf && file_exists($coverPdf)) {
            $merged = $tempDir . '/' . uniqid('merged_') . '.pdf';
            $mergeScript = base_path('scripts/merge_pdfs.py');
            $cmd = 'python ' . escapeshellarg($mergeScript) . ' ' . escapeshellarg($merged) . ' ' . escapeshellarg($coverPdf) . ' ' . escapeshellarg($reportPdf) . ' 2>&1';
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($merged)) {
                @unlink($coverPdf);
                @unlink($reportPdf);
                return response()->download($merged, $filename . '.pdf')->deleteFileAfterSend(true);
            }
            @unlink($coverPdf);
        }

        return response()->download($reportPdf, $filename . '.pdf')->deleteFileAfterSend(true);
    }

    private function generateCoverLetter($report, $reportPdfPath, $tempDir)
    {
        $templatePath = \Illuminate\Support\Facades\Storage::path('word-template-pra-monitoring.docx');
        if (!file_exists($templatePath)) {
            \Log::info('word cover: template tidak ditemukan', ['path' => $templatePath]);
            return null;
        }

        $pages = (int) $this->pdfPageCount($reportPdfPath);
        if ($pages <= 0) {
            $pages = 5;
        }
        $pagesWord = static::angkaToHuruf($pages);

        $replacements = [
            '{nomor_surat}' => request()->input('nomor', ''),
            '{nama_gerai}' => $report->gerai->nama_gerai ?? '',
            '{kode_gerai}' => $report->gerai->kode_gerai ?? '',
            '{alamat}' => request()->input('alamat', $report->gerai->alamat ?? ''),
            '{kota}' => request()->input('kota', $report->gerai->nama_kota ?? ''),
            '{franchisee}' => request()->input('franchisee', $report->gerai->franchisee ?? ''),
            '{lampiran}' => $pagesWord . ' Lembar',
            '{lembar_huruf}' => strtolower($pagesWord) . ' lembar',
        ];

        $replFile = $tempDir . '/' . uniqid('repl_') . '.json';
        file_put_contents($replFile, json_encode($replacements, JSON_UNESCAPED_UNICODE));

        $coverPdf = $tempDir . '/' . uniqid('cover_') . '.pdf';
        $script = base_path('scripts/word_fill_export.py');
        $cmd = 'python ' . escapeshellarg($script) . ' ' . escapeshellarg($templatePath) . ' ' . escapeshellarg($coverPdf) . ' ' . escapeshellarg($replFile) . ' 2>&1';
        exec($cmd, $out, $code);
        @unlink($replFile);

        if ($code === 0 && file_exists($coverPdf)) {
            return $coverPdf;
        }

        @unlink($coverPdf);
        \Log::info('word cover: generate gagal', ['output' => $out, 'exit' => $code]);
        return null;
    }

    private function pdfPageCount($path)
    {
        $script = base_path('scripts/pdf_pages.py');
        $cmd = 'python ' . escapeshellarg($script) . ' ' . escapeshellarg($path) . ' 2>&1';
        exec($cmd, $out, $code);
        return (int) trim(implode('', $out));
    }

    protected static function angkaToHuruf($n)
    {
        $n = (int) $n;
        if ($n <= 0) {
            return '';
        }
        $satuan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if ($n < 12) {
            return $satuan[$n];
        }
        if ($n < 20) {
            return static::angkaToHuruf($n - 10) . ' Belas';
        }
        if ($n < 100) {
            $puluhan = intdiv($n, 10);
            $sisa = $n % 10;
            $res = $satuan[$puluhan] . ' Puluh';
            return $sisa ? $res . ' ' . $satuan[$sisa] : $res;
        }
        return (string) $n;
    }

    public static function deleteWordTemplate(\Illuminate\Http\Request $request)
    {
        $candidates = ['word-template-pra-monitoring.docx', 'word-template-pra-monitoring.doc'];
        $existing = null;
        foreach ($candidates as $candidate) {
            if (\Illuminate\Support\Facades\Storage::exists($candidate)) {
                $existing = $candidate;
                break;
            }
        }

        if (!$existing) {
            return back()->with('error', 'Template Surat Word tidak ditemukan.');
        }

        if (!\Illuminate\Support\Facades\Storage::delete($existing)) {
            return back()->with('error', 'Gagal menghapus Template Surat Word: file mungkin sedang dibuka di program lain. Tutup file tersebut lalu coba lagi.');
        }

        return back()->with('success', 'Template Surat Word Pra-Monitoring berhasil dihapus.');
    }
}

