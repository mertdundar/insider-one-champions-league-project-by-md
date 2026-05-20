<?php

namespace Tests\Unit\Services;

use App\Services\ChampionshipPredictor;
use App\Services\LeagueTableCalculator;
use App\Services\MatchSimulator;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ChampionshipPredictorTest extends TestCase
{
    private function predictor(int $seed = 1, int $iterations = 500): ChampionshipPredictor
    {
        return new ChampionshipPredictor(
            new MatchSimulator(new Randomizer(new Mt19937($seed))),
            new LeagueTableCalculator(),
            $iterations,
        );
    }

    private function teams(): array
    {
        return [
            ['id' => 1, 'strength' => 92, 'advantage_home' => 3],
            ['id' => 2, 'strength' => 88, 'advantage_home' => 3],
            ['id' => 3, 'strength' => 82, 'advantage_home' => 3],
            ['id' => 4, 'strength' => 78, 'advantage_home' => 3],
        ];
    }

    public function test_returns_null_before_week_four(): void
    {
        foreach ([1, 2, 3] as $week) {
            $result = $this->predictor()->predict($this->teams(), [], [], $week);
            $this->assertNull($result, "Week {$week} should not yet predict");
        }
    }

    public function test_returns_array_from_week_four(): void
    {
        $result = $this->predictor(iterations: 100)->predict($this->teams(), [], [], 4);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    public function test_percentages_are_integers_and_sum_to_exactly_one_hundred(): void
    {
        $result = $this->predictor(iterations: 1000)->predict($this->teams(), [], [], 4);

        foreach ($result as $row) {
            $this->assertIsInt($row['percent'], "Expected integer percent, got: ".var_export($row['percent'], true));
        }
        $this->assertSame(100, array_sum(array_column($result, 'percent')));
    }

    public function test_team_with_clinched_lead_and_no_remaining_fixtures_gets_one_hundred_percent(): void
    {
        // Team 1: 6 wins (18 pts); Team 2: 3 wins (9 pts); Teams 3,4: 0 wins.
        $played = [];
        foreach ([2, 3, 4] as $opponent) {
            $played[] = ['home_team_id' => 1, 'away_team_id' => $opponent, 'home_score' => 2, 'away_score' => 0];
            $played[] = ['home_team_id' => $opponent, 'away_team_id' => 1, 'home_score' => 0, 'away_score' => 2];
        }

        $result = $this->predictor(iterations: 100)->predict($this->teams(), $played, [], 6);

        $byId = array_column($result, null, 'team_id');
        $this->assertSame(100, $byId[1]['percent']);
        $this->assertSame(0, $byId[2]['percent']);
    }

    public function test_seeded_predictor_is_deterministic(): void
    {
        $remaining = [
            ['home_team_id' => 1, 'away_team_id' => 2],
            ['home_team_id' => 3, 'away_team_id' => 4],
        ];

        $a = $this->predictor(seed: 99, iterations: 200)->predict($this->teams(), [], $remaining, 4);
        $b = $this->predictor(seed: 99, iterations: 200)->predict($this->teams(), [], $remaining, 4);

        $this->assertSame($a, $b);
    }

    public function test_result_is_sorted_descending_by_percent(): void
    {
        $result = $this->predictor(iterations: 500)->predict($this->teams(), [], [], 4);

        $percents = array_column($result, 'percent');
        $sorted = $percents;
        rsort($sorted);
        $this->assertSame($sorted, $percents);
    }
}
