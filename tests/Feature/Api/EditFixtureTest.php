<?php

namespace Tests\Feature\Api;

use App\Models\Fixture;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditFixtureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->postJson('/api/fixtures/generate');
    }

    public function test_edit_persists_new_score_and_recomputes_standings(): void
    {
        $this->postJson('/api/play-all');
        $fixture = Fixture::orderBy('id')->first();
        $homeId = $fixture->home_team_id;
        $beforeRow = collect($this->getJson('/api/state')->json('table'))
            ->firstWhere('team.id', $homeId);

        $this->putJson("/api/fixtures/{$fixture->id}", [
            'home_score' => 9,
            'away_score' => 0,
        ])->assertOk();

        $fixture->refresh();
        $this->assertSame(9, $fixture->home_score);
        $this->assertSame(0, $fixture->away_score);

        $afterRow = collect($this->getJson('/api/state')->json('table'))
            ->firstWhere('team.id', $homeId);

        $this->assertNotSame($beforeRow['goals_for'], $afterRow['goals_for']);
    }

    public function test_edit_unplayed_fixture_returns_409(): void
    {
        $unplayed = Fixture::whereNull('played_at')->first();

        $this->putJson("/api/fixtures/{$unplayed->id}", [
            'home_score' => 2,
            'away_score' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'FIXTURE_NOT_PLAYED');
    }

    public function test_edit_unknown_fixture_returns_404(): void
    {
        $this->putJson('/api/fixtures/99999', [
            'home_score' => 1,
            'away_score' => 1,
        ])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'FIXTURE_NOT_FOUND');
    }

    public function test_edit_with_missing_score_returns_422(): void
    {
        $this->postJson('/api/play-all');
        $fixture = Fixture::orderBy('id')->first();

        $this->putJson("/api/fixtures/{$fixture->id}", ['home_score' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_SCORE');
    }

    public function test_edit_with_negative_score_returns_422(): void
    {
        $this->postJson('/api/play-all');
        $fixture = Fixture::orderBy('id')->first();

        $this->putJson("/api/fixtures/{$fixture->id}", [
            'home_score' => -1,
            'away_score' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_SCORE');
    }

    public function test_edit_with_non_integer_score_returns_422(): void
    {
        $this->postJson('/api/play-all');
        $fixture = Fixture::orderBy('id')->first();

        $this->putJson("/api/fixtures/{$fixture->id}", [
            'home_score' => 'two',
            'away_score' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_SCORE');
    }
}
