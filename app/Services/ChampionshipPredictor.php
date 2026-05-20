<?php

namespace App\Services;

class ChampionshipPredictor
{
    public function __construct(
        private MatchSimulator $simulator,
        private LeagueTableCalculator $tableCalculator,
        private int $iterations = 10000,
    ) {
    }

    /**
     * Premier League rules for championship probabilities.
     *
     * Returns null before week 4
     * (case study indicates after 4th week estimations should be done)
     *
     * Percentages are rounded to integers for convenience of UI and always sum to exactly 100
     *
     * @param  list<array{id:int,strength:int,advantage_home:int}>  $teams
     * @param  list<array{home_team_id:int,away_team_id:int,home_score:int,away_score:int}>  $played
     * @param  list<array{home_team_id:int,away_team_id:int}>  $remaining
     * @return list<array{team_id:int,percent:int}>|null
     */
    public function predict(array $teams, array $played, array $remaining, int $currentWeek): ?array
    {
        if ($currentWeek < 4) {
            return null;
        }

        $teamIds = array_column($teams, 'id');
        $teamMap = array_column($teams, null, 'id');
        $wins = array_fill_keys($teamIds, 0);

        for ($i = 0; $i < $this->iterations; $i++) {
            $simulated = [];
            foreach ($remaining as $f) {
                $home = $teamMap[$f['home_team_id']];
                $away = $teamMap[$f['away_team_id']];
                $score = $this->simulator->simulate(
                    $home['strength'],
                    $away['strength'],
                    $home['advantage_home'] ?? 3,
                );
                $simulated[] = [
                    'home_team_id' => $f['home_team_id'],
                    'away_team_id' => $f['away_team_id'],
                    'home_score' => $score['home'],
                    'away_score' => $score['away'],
                ];
            }
            $table = $this->tableCalculator->calculate($teamIds, [...$played, ...$simulated]);
            $wins[$table[0]['team_id']]++;
        }

        $percents = $this->roundToIntegersSummingToHundred($wins, $this->iterations);

        $result = [];
        foreach ($teamIds as $id) {
            $result[] = [
                'team_id' => $id,
                'percent' => $percents[$id],
            ];
        }
        usort($result, fn (array $a, array $b): int => $b['percent'] <=> $a['percent']);

        return $result;
    }

    /**
     * Largest-remainder (Hamilton) rounding for correct prediction total of integers
     *
     * @param  array<int,int>  $wins  team_id => win count
     * @return array<int,int>  team_id => integer percent
     */
    private function roundToIntegersSummingToHundred(array $wins, int $iterations): array
    {
        $floors = [];
        $remainders = [];
        foreach ($wins as $id => $count) {
            $raw = $count / $iterations * 100;
            $floors[$id] = (int) floor($raw);
            $remainders[$id] = $raw - $floors[$id];
        }

        $deficit = 100 - array_sum($floors);
        if ($deficit > 0) {
            arsort($remainders);
            $awarded = 0;
            foreach (array_keys($remainders) as $id) {
                if ($awarded >= $deficit) {
                    break;
                }
                $floors[$id]++;
                $awarded++;
            }
        }

        return $floors;
    }
}
