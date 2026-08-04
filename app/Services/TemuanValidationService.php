<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TemuanValidationService
{
    public static function validateCompleteness($report, Collection $results, Collection $allItems, bool $hasPenjelasanFormulir2 = true): array
    {
        $incomplete = [];

        $hasFinding = !empty($report->major) || !empty($report->minor) || !empty($report->peringatan_awal)
            || !empty($report->ttd_petugas) || !empty($report->ttd_pimpinan);
        if (!$hasFinding) {
            $incomplete[] = 'Pengisian Temuan';
            return $incomplete;
        }

        $temuanFields = ['major', 'minor', 'peringatan_awal', 'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca'];
        foreach ($temuanFields as $f) {
            if (empty(trim($report->$f ?? ''))) {
                $incomplete[] = match ($f) {
                    'major' => 'Mayor',
                    'minor' => 'Minor',
                    'peringatan_awal' => 'Peringatan Awal',
                    'note' => 'Note',
                    'kondisi_cat' => 'Kondisi Cat',
                    'kondisi_awning' => 'Kondisi Awning',
                    'kondisi_vinyl' => 'Kondisi Vinyl Reklame',
                    'kondisi_stiker_kaca' => 'Kondisi Stiker Kaca',
                    default => $f,
                };
                break;
            }
        }

        if (empty($report->ttd_petugas)) {
            $incomplete[] = 'TTD Petugas';
        }
        if (empty($report->ttd_pimpinan)) {
            $incomplete[] = 'TTD Pimpinan';
        }

        if ($hasPenjelasanFormulir2 && !empty($report->penjelasan_isi) && is_array($report->penjelasan_isi)) {
            foreach ($report->penjelasan_isi as $val) {
                if (empty(trim($val))) {
                    $incomplete[] = 'Penjelasan Formulir 2';
                    break;
                }
            }
        }

        $zeroScoreItemIds = self::findZeroScoreItems($results, $allItems);
        if (!empty($zeroScoreItemIds)) {
            $penjelasan3 = $report->penjelasan_isi_3 ?? [];
            $allFilled = true;
            foreach ($zeroScoreItemIds as $itemId) {
                if (empty($penjelasan3[$itemId]) || empty(trim($penjelasan3[$itemId]))) {
                    $allFilled = false;
                    break;
                }
            }
            if (!$allFilled) {
                $incomplete[] = 'Penjelasan Formulir 3';
            }
        }

        return $incomplete;
    }

    public static function findZeroScoreItems(Collection $results, Collection $allItems): array
    {
        $ids = [];
        foreach ($allItems as $item) {
            if (!$item->bobot || $item->criteria->count() <= 1) continue;
            $result = $results->get($item->id);
            if (!$result || !$result->criterion_id) continue;
            $criteria = $item->criteria;
            $idToIndex = array_flip($criteria->pluck('id')->toArray());
            $idx = $idToIndex[$result->criterion_id] ?? false;
            if ($idx !== false && $idx === $criteria->count() - 1) {
                $ids[] = $item->id;
            }
        }
        return $ids;
    }

    public static function renumberPeringatanAwal(string $text): string
    {
        $lines = preg_split('/\r?\n/', $text);
        $counter = 1;
        foreach ($lines as &$line) {
            $trimmed = trim($line);
            if (preg_match('/^(\d+)\.\s*/', $trimmed)) {
                $rest = preg_replace('/^(\d+)\.\s*/', '', $trimmed);
                $indent = substr($line, 0, strpos($line, $trimmed));
                $line = $indent . $counter . '. ' . $rest;
                $counter++;
            }
        }
        return implode("\n", $lines);
    }
}
