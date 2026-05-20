<?php

namespace Tests\Feature\Models;

use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_persisted_team_with_integer_strength(): void
    {
        $team = Team::factory()->create();

        $this->assertInstanceOf(Team::class, $team);
        $this->assertTrue($team->exists);
        $this->assertIsInt($team->strength);
    }

    public function test_factory_defaults_advantage_home_to_three(): void
    {
        $team = Team::factory()->create();

        $this->assertSame(3, $team->advantage_home);
    }

    public function test_team_name_must_be_unique(): void
    {
        Team::factory()->create(['name' => 'Team 93']);

        $this->expectException(QueryException::class);
        Team::factory()->create(['name' => 'Team 93']);
    }

    public function test_team_short_name_must_be_unique(): void
    {
        Team::factory()->create(['short_name' => 'T93']);

        $this->expectException(QueryException::class);
        Team::factory()->create(['short_name' => 'T93']);
    }
}
