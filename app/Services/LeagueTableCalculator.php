<?php

namespace App\Services;

class LeagueTableCalculator
{
    /**
     * Calculate league standings from the played fixtures.
     *
     * Sort by: pts, goal_diff, goals_for, team_id in ASC as last resort
     * If all conditions are same, lastly sort by team_id which is unique
     * FAQ did not specify for all tie condition
     *
     * @param  list<int>  $teamIds
     * @param  list<array{home_team_id:int,away_team_id:int,home_score:int,away_score:int}>  $playedFixtures
     * @return list<array{position:int,team_id:int,played:int,won:int,drawn:int,lost:int,goals_for:int,goals_against:int,goal_diff:int,pts:int}>
     */
    public function calculate(array $teamIds, array $playedFixtures): array
    {
        $rows = [];
        foreach ($teamIds as $id) {
            $rows[$id] = [
                'team_id' => $id,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_diff' => 0,
                'pts' => 0,
            ];
        }

        foreach ($playedFixtures as $f) {
            $home = $f['home_team_id'];
            $away = $f['away_team_id'];
            $hs = $f['home_score'];
            $as = $f['away_score'];

            $rows[$home]['played']++;
            $rows[$away]['played']++;
            $rows[$home]['goals_for'] += $hs;
            $rows[$home]['goals_against'] += $as;
            $rows[$away]['goals_for'] += $as;
            $rows[$away]['goals_against'] += $hs;

            if ($hs > $as) {
                $rows[$home]['won']++;
                $rows[$home]['pts'] += 3;
                $rows[$away]['lost']++;
            } elseif ($as > $hs) {
                $rows[$away]['won']++;
                $rows[$away]['pts'] += 3;
                $rows[$home]['lost']++;
            } else {
                $rows[$home]['drawn']++;
                $rows[$away]['drawn']++;
                $rows[$home]['pts']++;
                $rows[$away]['pts']++;
            }
        }

        foreach ($rows as &$row) {
            $row['goal_diff'] = $row['goals_for'] - $row['goals_against'];
        }
        unset($row);

        $sorted = array_values($rows);
        usort($sorted, fn (array $a, array $b): int =>
            [$b['pts'], $b['goal_diff'], $b['goals_for'], $a['team_id']]
            <=>
            [$a['pts'], $a['goal_diff'], $a['goals_for'], $b['team_id']]
        );

        foreach ($sorted as $i => &$row) {
            $row['position'] = $i + 1;
        }
        unset($row);

        return $sorted;
    }
}
