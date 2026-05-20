<?php

namespace App\Services;

use App\Enums\SeasonStatus;
use App\Models\Season;
use App\Models\Team;

class LeagueStateProvider
{
    public function __construct(
        private LeagueTableCalculator $tableCalculator,
        private ChampionshipPredictor $predictor,
    ) {
    }

    public function currentState(): array
    {
        $teams = Team::orderBy('id')->get();
        $season = Season::orderBy('id', 'desc')->first();

        $teamsArr = $teams->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'short_name' => $t->short_name,
            'strength' => $t->strength,
        ])->all();

        if (! $season || $season->status === SeasonStatus::Setup) {
            return [
                'status' => SeasonStatus::Setup->value,
                'current_week' => null,
                'total_weeks' => 6,
                'teams' => $teamsArr,
                'table' => [],
                'week_fixtures' => (object) [],
                'predictions' => null,
            ];
        }

        $fixtures = $season->fixtures()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->orderBy('id')
            ->get();

        $playedRows = $fixtures
            ->filter(fn ($f) => $f->isPlayed())
            ->map(fn ($f) => [
                'home_team_id' => $f->home_team_id,
                'away_team_id' => $f->away_team_id,
                'home_score' => $f->home_score,
                'away_score' => $f->away_score,
            ])
            ->values()
            ->all();

        $remainingRows = $fixtures
            ->filter(fn ($f) => ! $f->isPlayed())
            ->map(fn ($f) => [
                'home_team_id' => $f->home_team_id,
                'away_team_id' => $f->away_team_id,
            ])
            ->values()
            ->all();

        $teamIds = $teams->pluck('id')->sort()->values()->all();
        $teamLookup = $teams->keyBy('id');

        $rawTable = $this->tableCalculator->calculate($teamIds, $playedRows);
        $table = array_map(function (array $row) use ($teamLookup) {
            $team = $teamLookup[$row['team_id']];
            return [
                'position' => $row['position'],
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'short_name' => $team->short_name,
                ],
                'played' => $row['played'],
                'won' => $row['won'],
                'drawn' => $row['drawn'],
                'lost' => $row['lost'],
                'goals_for' => $row['goals_for'],
                'goals_against' => $row['goals_against'],
                'goal_diff' => $row['goal_diff'],
                'pts' => $row['pts'],
            ];
        }, $rawTable);

        $weekFixtures = [];
        foreach ($fixtures as $f) {
            $weekFixtures[(string) $f->week][] = [
                'id' => $f->id,
                'week' => $f->week,
                'home' => [
                    'id' => $f->homeTeam->id,
                    'name' => $f->homeTeam->name,
                    'short_name' => $f->homeTeam->short_name,
                ],
                'away' => [
                    'id' => $f->awayTeam->id,
                    'name' => $f->awayTeam->name,
                    'short_name' => $f->awayTeam->short_name,
                ],
                'home_score' => $f->home_score,
                'away_score' => $f->away_score,
                'played_at' => $f->played_at?->toIso8601String(),
            ];
        }

        $teamsForPredict = $teams->map(fn ($t) => [
            'id' => $t->id,
            'strength' => $t->strength,
            'advantage_home' => $t->advantage_home,
        ])->all();

        $rawPredictions = $this->predictor->predict(
            $teamsForPredict,
            $playedRows,
            $remainingRows,
            $season->current_week ?? 0,
        );

        $predictions = $rawPredictions !== null
            ? array_map(function (array $row) use ($teamLookup) {
                $team = $teamLookup[$row['team_id']];
                return [
                    'team' => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'short_name' => $team->short_name,
                    ],
                    'percent' => $row['percent'],
                ];
            }, $rawPredictions)
            : null;

        return [
            'status' => $season->status->value,
            'current_week' => $season->current_week,
            'total_weeks' => $season->total_weeks,
            'teams' => $teamsArr,
            'table' => $table,
            'week_fixtures' => $weekFixtures ?: (object) [],
            'predictions' => $predictions,
        ];
    }
}
