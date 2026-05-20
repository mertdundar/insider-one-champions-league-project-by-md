<?php

namespace Tests\Unit\Services;

use App\Services\LeagueTableCalculator;
use PHPUnit\Framework\TestCase;

class LeagueTableCalculatorTest extends TestCase
{
    private LeagueTableCalculator $calc;

    protected function setUp(): void
    {
        $this->calc = new LeagueTableCalculator();
    }

    public function test_empty_fixtures_returns_all_zero_rows(): void
    {
        $table = $this->calc->calculate([1, 2, 3, 4], []);

        $this->assertCount(4, $table);
        foreach ($table as $row) {
            $this->assertSame(0, $row['played']);
            $this->assertSame(0, $row['pts']);
        }
    }

    public function test_a_win_awards_three_points_to_the_winner_and_none_to_the_loser(): void
    {
        $table = $this->calc->calculate([1, 2], [
            ['home_team_id' => 1, 'away_team_id' => 2, 'home_score' => 2, 'away_score' => 0],
        ]);
        $byId = array_column($table, null, 'team_id');

        $this->assertSame(3, $byId[1]['pts']);
        $this->assertSame(1, $byId[1]['won']);
        $this->assertSame(0, $byId[2]['pts']);
        $this->assertSame(1, $byId[2]['lost']);
    }

    public function test_a_draw_awards_one_point_to_each_side(): void
    {
        $table = $this->calc->calculate([1, 2], [
            ['home_team_id' => 1, 'away_team_id' => 2, 'home_score' => 1, 'away_score' => 1],
        ]);
        $byId = array_column($table, null, 'team_id');

        $this->assertSame(1, $byId[1]['pts']);
        $this->assertSame(1, $byId[1]['drawn']);
        $this->assertSame(1, $byId[2]['pts']);
        $this->assertSame(1, $byId[2]['drawn']);
    }

    public function test_goals_for_against_and_diff_are_tallied_per_side(): void
    {
        $table = $this->calc->calculate([1, 2], [
            ['home_team_id' => 1, 'away_team_id' => 2, 'home_score' => 3, 'away_score' => 1],
        ]);
        $byId = array_column($table, null, 'team_id');

        $this->assertSame(3, $byId[1]['goals_for']);
        $this->assertSame(1, $byId[1]['goals_against']);
        $this->assertSame(2, $byId[1]['goal_diff']);
        $this->assertSame(1, $byId[2]['goals_for']);
        $this->assertSame(3, $byId[2]['goals_against']);
        $this->assertSame(-2, $byId[2]['goal_diff']);
    }

    public function test_goal_difference_breaks_ties_on_points(): void
    {
        $fixtures = [
            ['home_team_id' => 1, 'away_team_id' => 3, 'home_score' => 3, 'away_score' => 0],
            ['home_team_id' => 2, 'away_team_id' => 3, 'home_score' => 1, 'away_score' => 0],
            ['home_team_id' => 3, 'away_team_id' => 1, 'home_score' => 0, 'away_score' => 0],
            ['home_team_id' => 3, 'away_team_id' => 2, 'home_score' => 0, 'away_score' => 0],
        ];

        $table = $this->calc->calculate([1, 2, 3], $fixtures);

        $this->assertSame(1, $table[0]['team_id']);
        $this->assertSame(2, $table[1]['team_id']);
        $this->assertSame(3, $table[2]['team_id']);
    }

    public function test_goals_for_breaks_ties_on_pts_and_goal_diff(): void
    {
        $fixtures = [
            ['home_team_id' => 1, 'away_team_id' => 3, 'home_score' => 3, 'away_score' => 2],
            ['home_team_id' => 2, 'away_team_id' => 3, 'home_score' => 1, 'away_score' => 0],
        ];

        $table = $this->calc->calculate([1, 2, 3], $fixtures);

        $this->assertSame(1, $table[0]['team_id']);
        $this->assertSame(2, $table[1]['team_id']);
        $this->assertSame(3, $table[2]['team_id']);
    }

    public function test_team_id_ascending_is_the_deterministic_fallback(): void
    {
        $table = $this->calc->calculate([3, 1, 2], []);

        $this->assertSame([1, 2, 3], array_column($table, 'team_id'));
    }

    public function test_positions_are_assigned_one_through_n(): void
    {
        $table = $this->calc->calculate([1, 2, 3, 4], []);

        $this->assertSame([1, 2, 3, 4], array_column($table, 'position'));
    }
}
