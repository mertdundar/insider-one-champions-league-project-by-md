<?php

namespace Tests\Feature\Models;

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_an_unplayed_fixture(): void
    {
        $fixture = Fixture::factory()->create();

        $this->assertInstanceOf(Fixture::class, $fixture);
        $this->assertNull($fixture->home_score);
        $this->assertNull($fixture->away_score);
        $this->assertNull($fixture->played_at);
        $this->assertFalse($fixture->isPlayed());
    }

    public function test_played_state_sets_scores_and_timestamp(): void
    {
        $fixture = Fixture::factory()->played(2, 1)->create();

        $this->assertSame(2, $fixture->home_score);
        $this->assertSame(1, $fixture->away_score);
        $this->assertInstanceOf(Carbon::class, $fixture->played_at);
        $this->assertTrue($fixture->isPlayed());
    }

    public function test_is_played_is_false_when_only_one_score_is_set(): void
    {
        $partial = Fixture::factory()->create(['home_score' => 3, 'away_score' => null]);

        $this->assertFalse($partial->isPlayed());
    }

    public function test_is_played_is_true_for_zero_zero_draws(): void
    {
        $draw = Fixture::factory()->played(0, 0)->create();

        $this->assertTrue($draw->isPlayed());
    }

    public function test_belongs_to_season(): void
    {
        $season = Season::factory()->create();
        $fixture = Fixture::factory()->create(['season_id' => $season->id]);

        $this->assertTrue($fixture->season->is($season));
    }

    public function test_belongs_to_distinct_home_and_away_teams(): void
    {
        $home = Team::factory()->create(['name' => 'Home FC',  'short_name' => 'HOM']);
        $away = Team::factory()->create(['name' => 'Away FC',  'short_name' => 'AWA']);
        $fixture = Fixture::factory()->create([
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        $this->assertTrue($fixture->homeTeam->is($home));
        $this->assertTrue($fixture->awayTeam->is($away));
        $this->assertFalse($fixture->homeTeam->is($fixture->awayTeam));
    }
}
