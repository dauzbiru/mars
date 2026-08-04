<?php

namespace App\Services;

use App\Models\Result;

class ScoreCalculator
{
    private static array $criteriaIndexCache = [];

    public static function calculateFromResults($results): float
    {
        $total = 0;
        $criteriaCache = [];

        foreach ($results as $result) {
            $item = $result->item;
            if (!$item || !$item->bobot) continue;

            $total += self::calculateItemScore($item, $result);
        }

        return $total;
    }

    public static function calculateItemScore($item, $result): float
    {
        if (!$item || !$item->bobot || !$result || !$result->criterion_id) {
            return 0;
        }
        $criteria = $item->criteria;
        $criteriaCount = $criteria->count();
        if ($criteriaCount <= 1) {
            return (float) $item->bobot;
        }
        $interval = $item->bobot / ($criteriaCount - 1);

        $itemId = $item->id;
        if (!isset(self::$criteriaIndexCache[$itemId])) {
            self::$criteriaIndexCache[$itemId] = array_flip($criteria->pluck('id')->toArray());
        }
        $idToIndex = self::$criteriaIndexCache[$itemId];
        $idx = $idToIndex[$result->criterion_id] ?? false;

        if ($idx === false) {
            return 0;
        }
        return $item->bobot - ($interval * $idx);
    }

    public static function calculateForReport($report): float
    {
        if ($report->nilai !== null) {
            return (float) $report->nilai;
        }

        return self::calculateFromResults($report->results);
    }

    public static function clearCache(): void
    {
        self::$criteriaIndexCache = [];
    }
}
