<?php

namespace App\Http\Controllers;

use App\Enums\SeasonStatus;
use App\Models\Season;
use App\Models\Team;
use App\Services\FixtureGenerator;
use App\Services\LeagueStateProvider;
use App\Services\MatchSimulator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    public function __construct(
        private LeagueStateProvider $stateProvider,
        private FixtureGenerator $fixtureGenerator,
        private MatchSimulator $simulator,
    ) {
    }

    public function state(): JsonResponse
    {
        return response()->json($this->stateProvider->currentState());
    }

    public function generate(): JsonResponse
    {
        $active = Season::where('status', SeasonStatus::InProgress->value)->first();
        if ($active) {
            return $this->error('FIXTURES_EXIST', 'A season is already in progress. Reset first.', 409);
        }

        DB::transaction(function () {
            Season::where('status', SeasonStatus::Setup->value)->delete();

            $season = Season::create([
                'status' => SeasonStatus::InProgress,
                'current_week' => null,
                'total_weeks' => 6,
            ]);

            // For random fixture generation
            $teamIds = Team::inRandomOrder()->pluck('id')->all();
            foreach ($this->fixtureGenerator->generate($teamIds) as $row) {
                $season->fixtures()->create($row);
            }
        });

        return response()->json($this->stateProvider->currentState(), 201);
    }

    public function playNext(): JsonResponse
    {
        $season = Season::orderBy('id', 'desc')->first();
        if (! $season || $season->status === SeasonStatus::Setup) {
            return $this->error('NO_FIXTURES', 'No active season. Generate fixtures first.', 409);
        }
        if ($season->status === SeasonStatus::Complete) {
            return $this->error('SEASON_COMPLETE', 'Season is already complete.', 409);
        }

        DB::transaction(fn () => $this->playWeek($season, ($season->current_week ?? 0) + 1));

        return response()->json($this->stateProvider->currentState());
    }

    public function playAll(): JsonResponse
    {
        $season = Season::orderBy('id', 'desc')->first();
        if (! $season || $season->status === SeasonStatus::Setup) {
            return $this->error('NO_FIXTURES', 'No active season. Generate fixtures first.', 409);
        }
        if ($season->status === SeasonStatus::Complete) {
            return response()->json($this->stateProvider->currentState());
        }

        DB::transaction(function () use ($season) {
            $next = ($season->current_week ?? 0) + 1;
            while ($next <= $season->total_weeks) {
                $this->playWeek($season, $next);
                $next++;
            }
        });

        return response()->json($this->stateProvider->currentState());
    }

    public function editFixture(Request $request, int $id): JsonResponse
    {
        $season = Season::orderBy('id', 'desc')->first();
        $fixture = $season?->fixtures()->find($id);

        if (! $fixture) {
            return $this->error('FIXTURE_NOT_FOUND', "Fixture {$id} not found in the active season.", 404);
        }

        if (! $fixture->isPlayed()) {
            return $this->error('FIXTURE_NOT_PLAYED', 'Cannot edit a fixture that has not been played.', 409);
        }

        $validator = validator($request->all(), [
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            return $this->error(
                'INVALID_SCORE',
                'Score validation failed.',
                422,
                $validator->errors()->toArray(),
            );
        }

        DB::transaction(fn () => $fixture->update([
            'home_score' => (int) $request->input('home_score'),
            'away_score' => (int) $request->input('away_score'),
        ]));

        return response()->json($this->stateProvider->currentState());
    }

    public function reset(): JsonResponse
    {
        DB::transaction(fn () => Season::query()->delete());

        return response()->json($this->stateProvider->currentState());
    }

    private function playWeek(Season $season, int $week): void
    {
        $fixtures = $season->fixtures()
            ->where('week', $week)
            ->whereNull('played_at')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        foreach ($fixtures as $f) {
            $score = $this->simulator->simulate(
                $f->homeTeam->strength,
                $f->awayTeam->strength,
                $f->homeTeam->advantage_home,
            );
            $f->update([
                'home_score' => $score['home'],
                'away_score' => $score['away'],
                'played_at' => now(),
            ]);
        }

        $season->current_week = $week;
        if ($week >= $season->total_weeks) {
            $season->status = SeasonStatus::Complete;
        }
        $season->save();
    }

    private function error(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details ?: (object) [],
            ],
        ], $status);
    }
}
