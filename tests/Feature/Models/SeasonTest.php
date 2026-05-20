<?php

namespace Tests\Feature\Models;

use App\Enums\SeasonStatus;
use App\Models\Fixture;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_defaults_to_setup_status_with_no_current_week(): void
    {
        $season = Season::factory()->create();

        $this->assertSame(SeasonStatus::Setup, $season->status);
        $this->assertNull($season->current_week);
        $this->assertSame(6, $season->total_weeks);
    }

    public function test_in_progress_state_sets_status_and_current_week(): void
    {
        $season = Season::factory()->inProgress(3)->create();

        $this->assertSame(SeasonStatus::InProgress, $season->status);
        $this->assertSame(3, $season->current_week);
    }

    public function test_complete_state_sets_status_and_terminal_week(): void
    {
        $season = Season::factory()->complete()->create();

        $this->assertSame(SeasonStatus::Complete, $season->status);
        $this->assertSame(6, $season->current_week);
    }

    public function test_status_is_persisted_as_string_and_restored_as_enum(): void
    {
        Season::factory()->inProgress(2)->create();

        $row = \DB::table('seasons')->first();

        $this->assertSame('in_progress', $row->status);
        $this->assertSame(SeasonStatus::InProgress, Season::first()->status);
    }

    public function test_has_many_fixtures(): void
    {
        $season = Season::factory()->create();
        Fixture::factory()->count(3)->create(['season_id' => $season->id]);

        $this->assertCount(3, $season->fixtures);
        $this->assertInstanceOf(Fixture::class, $season->fixtures->first());
    }
}
