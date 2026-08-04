<?php

namespace Tests\Unit;

use App\Models\Criterion;
use App\Models\Item;
use App\Models\Result;
use App\Services\ScoreCalculator;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ScoreCalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ScoreCalculator::clearCache();
    }

    private function makeItem(int $id, float $bobot, array $criterionIds = []): Item
    {
        $item = new Item(['bobot' => $bobot]);
        $item->setAttribute('id', $id);

        $criteria = new Collection(array_map(
            fn ($cid) => (new Criterion(['description' => 'kriteria']))->setAttribute('id', $cid),
            $criterionIds
        ));

        $item->setRelation('criteria', $criteria);

        return $item;
    }

    private function makeResult(Item $item, int $criterionId): Result
    {
        $result = new Result(['criterion_id' => $criterionId]);
        $result->setRelation('item', $item);

        return $result;
    }

    public function test_single_criterion_returns_full_bobot(): void
    {
        $item = $this->makeItem(1, 10.0, [101]);

        $this->assertSame(10.0, ScoreCalculator::calculateItemScore($item, $this->makeResult($item, 101)));
    }

    public function test_first_criterion_gets_full_bobot(): void
    {
        $item = $this->makeItem(2, 10.0, [201, 202, 203]);

        $this->assertSame(10.0, ScoreCalculator::calculateItemScore($item, $this->makeResult($item, 201)));
    }

    public function test_middle_criterion_gets_partial_score(): void
    {
        $item = $this->makeItem(3, 10.0, [301, 302, 303]);

        $this->assertSame(5.0, ScoreCalculator::calculateItemScore($item, $this->makeResult($item, 302)));
    }

    public function test_last_criterion_gets_zero(): void
    {
        $item = $this->makeItem(4, 10.0, [401, 402, 403]);

        $this->assertSame(0.0, ScoreCalculator::calculateItemScore($item, $this->makeResult($item, 403)));
    }

    public function test_unknown_criterion_gets_zero(): void
    {
        $item = $this->makeItem(5, 10.0, [501, 502]);

        $this->assertSame(0.0, ScoreCalculator::calculateItemScore($item, $this->makeResult($item, 999)));
    }

    public function test_item_without_bobot_gets_zero(): void
    {
        $item = $this->makeItem(6, 0.0, [601]);

        $this->assertSame(0.0, ScoreCalculator::calculateItemScore($item, $this->makeResult($item, 601)));
    }

    public function test_calculate_from_results_sums_all_items(): void
    {
        $itemA = $this->makeItem(7, 10.0, [701, 702, 703]);
        $itemB = $this->makeItem(8, 4.0, [801, 802, 803]);

        $results = [
            $this->makeResult($itemA, 702), // 5
            $this->makeResult($itemB, 801), // 4
        ];

        $this->assertSame(9.0, ScoreCalculator::calculateFromResults($results));
    }

    public function test_result_without_item_is_skipped(): void
    {
        $item = $this->makeItem(9, 10.0, [901, 902]);
        $valid = $this->makeResult($item, 902);

        $missingItem = new Result(['criterion_id' => 1]);
        $missingItem->setRelation('item', null);

        $this->assertSame(0.0, ScoreCalculator::calculateFromResults([$valid, $missingItem]));
    }
}
