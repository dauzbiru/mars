<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\MonitoringReport;
use App\Models\PraMonitoringReport;
use App\Models\Result;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // E32 = total score, E33 = 975, E34 = grade letter
        static::xmlSetNumber($xpath1, $dom1, $ns, 'E32', round($totalScore));
        static::xmlSetNumber($xpath1, $dom1, $ns, 'E33', 975);
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
        // Fill datachart: Row1= tanggal lengkap, Row2= score, Row3= 975
        static::xmlSetInlineStr($xpath3, $dom3, $ns3, 'B1', $tanggalLengkap);
        static::xmlSetNumber($xpath3, $dom3, $ns3, 'B2', round($totalScore));
        static::xmlSetNumber($xpath3, $dom3, $ns3, 'B3', 975);
    }

    protected function onPhase3Cell(string $sheetName, int $ssIndex, array $ssIndexText, array $ssIndexScore, DOMElement $cell, DOMDocument $dom, array $items): void
    {
        if ($sheetName !== 'xl/worksheets/sheet2.xml') return;

        $placeholder = $ssIndexText[$ssIndex] ?? '';
        if (!preg_match('/\{item_score:(.*?)\}/', $placeholder, $m)) return;

        $itemName = trim($m[1]);
        if (!isset($items[$itemName])) return;

        $scoreVal = (float)$ssIndexScore[$ssIndex];
        $bobotVal = (float)$items[$itemName]['bobot'];
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

    protected function fillSheet2Custom(DOMDocument $dom2, DOMXPath $xpath2, string $ns2, array $findingLines, $finding, array $lowItems, array $sheet3ZeroItems, array $items, $zip): bool
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
        if ($paRow && $noteRow && $finding) {
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
            $pengawas = $finding->pengawas ?? '';
            if ($pengawas !== '') {
                foreach (preg_split('/\r?\n/', $pengawas) as $line) {
                    if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom2, $ns2, trim($line), $infoRn);
                }
            }
            $aj = $finding->rata_rata_aj ?? '';
            if ($aj !== '') {
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Rerata AJ ± ' . $aj . ' gln/hr', $infoRn);
            }
            $mo = $finding->mesin_ozon ?? '';
            if ($mo !== '') {
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'MO: ' . $mo, $infoRn);
            }
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);
            $paLines = $findingLines['peringatan_awal'] ?? [];
            foreach ($paLines as $line) {
                if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom2, $ns2, $line, $infoRn);
            }
            $noteContent = $finding->note ?? '';
            if ($noteContent !== '') {
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);
                $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Note:', $infoRn);
                foreach (preg_split('/\r?\n/', $noteContent) as $line) {
                    if (trim($line) !== '') $infoRows[] = static::xmlMakeBRow($dom2, $ns2, trim($line), $infoRn);
                }
            }
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, '', $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Checklist tampilan gerai:', $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi cat: ' . ($finding->kondisi_cat ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi awning: ' . ($finding->kondisi_awning ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi vinyl reklame dinding/jalan: ' . ($finding->kondisi_vinyl ?: 'Baik'), $infoRn);
            $infoRows[] = static::xmlMakeBRow($dom2, $ns2, 'Kondisi stiker kaca: ' . ($finding->kondisi_stiker_kaca ?: 'Baik'), $infoRn);
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
        $penjelasanIsi3 = $finding ? ($finding->penjelasan_isi_3 ?? []) : [];

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

    public function checkinForm(Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending && request('pairing') !== '1') {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan. Selesaikan dulu.');
        }

        return view('monitoring.checkin', compact('gerai') + ['prefix' => $this->prefix(), 'periods' => collect()]);
    }

    public function doCheckin(Request $request, Gerai $gerai)
    {
        $pending = $this->pendingReport();
        if ($pending && $request->input('pairing') !== '1') {
            return redirect("/{$this->prefix()}/{$pending->id}/assessment")
                ->with('warning', 'Anda masih memiliki laporan yang belum diselesaikan.');
        }

        $data = $request->validate([
            'location' => 'required|string|max:255',
            'checkin_at' => 'required|date',
        ]);

        $pairing = $request->input('pairing') === '1';

        $report = PraMonitoringReport::create([
            'gerai_id' => $gerai->id,
            'user_id' => Auth::id(),
            'location' => $data['location'],
            'checkin_at' => \Carbon\Carbon::parse($data['checkin_at'] . ' ' . now()->format('H:i:s')),
            'is_pairing' => $pairing,
        ]);

        $categories = Category::whereNull('parent_id')->with('items.criteria')->get();
        foreach ($categories as $cat) {
            foreach ($cat->items as $item) {
                if ($item->criteria->isNotEmpty()) {
                    Result::create([
                        'item_id' => $item->id,
                        'user_id' => Auth::id(),
                        'reportable_type' => PraMonitoringReport::class,
                        'reportable_id' => $report->id,
                        'criterion_id' => $item->criteria->first()->id,
                    ]);
                }
            }
        }

        return redirect("/{$this->prefix()}/{$report->id}/assessment");
    }

    public function destroy(Request $request, $id)
    {
        $report = $this->modelClass()::withoutGlobalScope('no_pairing')->findOrFail($id);
        $this->authorizeReport($report);

        session()->forget('assessment_snapshot_' . $report->id);

        $report->results()->delete();
        $report->finding?->delete();
        $report->delete();

        $redirect = $request->input('_from') === 'list'
            ? '/report/pra-monitoring'
            : "/{$this->prefix()}";

        return redirect($redirect)->with('success', 'Laporan berhasil dihapus.');
    }

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
        return $report->periode_label;
    }
}

