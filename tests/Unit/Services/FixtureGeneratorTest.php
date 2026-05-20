<?php

namespace Tests\Unit\Services;

use App\Services\FixtureGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FixtureGeneratorTest extends TestCase
{
    private FixtureGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new FixtureGenerator();
    }

    public function test_four_teams_produces_twelve_fixtures_across_six_weeks(): void
    {
        $fixtures = $this->gen->generate([1, 2, 3, 4]);

        $this->assertCount(12, $fixtures);
        $weeks = array_unique(array_column($fixtures, 'week'));
        sort($weeks);
        $this->assertSame([1, 2, 3, 4, 5, 6], $weeks);
    }

    public function test_each_week_has_exactly_two_matches(): void
    {
        $fixtures = $this->gen->generate([1, 2, 3, 4]);

        $counts = array_count_values(array_column($fixtures, 'week'));
        $this->assertSame([2, 2, 2, 2, 2, 2], array_values($counts));
    }

    public function test_each_pair_meets_exactly_twice_once_per_venue(): void
    {
        $fixtures = $this->gen->generate([1, 2, 3, 4]);

        $pairCounts = [];
        $homeFor = [];
        foreach ($fixtures as $f) {
            $key = $f['home_team_id'] < $f['away_team_id']
                ? "{$f['home_team_id']}-{$f['away_team_id']}"
                : "{$f['away_team_id']}-{$f['home_team_id']}";
            $pairCounts[$key] = ($pairCounts[$key] ?? 0) + 1;
            $homeFor[$key][] = $f['home_team_id'];
        }

        $this->assertCount(6, $pairCounts);
        foreach ($pairCounts as $count) {
            $this->assertSame(2, $count);
        }
        foreach ($homeFor as $hosts) {
            $this->assertNotEquals($hosts[0], $hosts[1], 'Both legs hosted by same team.');
        }
    }

    public function test_no_team_plays_itself(): void
    {
        $fixtures = $this->gen->generate([1, 2, 3, 4]);
        foreach ($fixtures as $f) {
            $this->assertNotEquals($f['home_team_id'], $f['away_team_id']);
        }
    }

    public function test_every_team_plays_three_home_and_three_away(): void
    {
        $fixtures = $this->gen->generate([1, 2, 3, 4]);

        foreach ([1, 2, 3, 4] as $teamId) {
            $home = array_filter($fixtures, fn ($f) => $f['home_team_id'] === $teamId);
            $away = array_filter($fixtures, fn ($f) => $f['away_team_id'] === $teamId);
            $this->assertCount(3, $home, "Team {$teamId} home count");
            $this->assertCount(3, $away, "Team {$teamId} away count");
        }
    }

    public function test_odd_team_count_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gen->generate([1, 2, 3]);
    }

    public function test_empty_input_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gen->generate([]);
    }

    public function test_duplicate_team_ids_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gen->generate([1, 2, 2, 3]);
    }

    public function test_works_for_six_teams_as_well(): void
    {
        $fixtures = $this->gen->generate([1, 2, 3, 4, 5, 6]);
        $this->assertCount(30, $fixtures);
        $this->assertSame(10, max(array_column($fixtures, 'week')));
    }
}
