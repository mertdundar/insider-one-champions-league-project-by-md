<?php

namespace Tests\Feature\Database;

use App\Models\Team;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_exactly_four_teams(): void
    {
        $this->seed(TeamSeeder::class);

        $this->assertSame(4, Team::count());
    }

    public function test_it_seeds_the_expected_champions_league_teams(): void
    {
        $this->seed(TeamSeeder::class);

        $names = Team::orderBy('name')->pluck('name')->all();

        $this->assertSame(
            ['Team 75', 'Team 82', 'Team 88', 'Team 93'],
            $names,
        );
    }

    public function test_it_assigns_canonical_short_names(): void
    {
        $this->seed(TeamSeeder::class);

        $shortNames = Team::orderBy('name')->pluck('short_name')->all();

        $this->assertSame(['T75', 'T82', 'T88', 'T93'], $shortNames);
    }

    public function test_it_assigns_distinct_strengths_with_top_and_bottom(): void
    {
        $this->seed(TeamSeeder::class);

        $strengths = Team::pluck('strength', 'name')->all();

        $this->assertSame(93, $strengths['Team 93']);
        $this->assertSame(88, $strengths['Team 88']);
        $this->assertSame(82, $strengths['Team 82']);
        $this->assertSame(75, $strengths['Team 75']);
    }

    public function test_it_gives_every_team_the_same_advantage_home(): void
    {
        $this->seed(TeamSeeder::class);

        $advantages = Team::pluck('advantage_home')->unique()->values()->all();

        $this->assertSame([3], $advantages);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(TeamSeeder::class);
        $this->seed(TeamSeeder::class);
        $this->seed(TeamSeeder::class);

        $this->assertSame(4, Team::count());
    }
}
