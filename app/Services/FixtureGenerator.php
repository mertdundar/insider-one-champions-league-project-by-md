<?php

namespace App\Services;

use InvalidArgumentException;

class FixtureGenerator
{
    /**
     * Generate a double round-robin schedule for the given team IDs.
     *
     * For 4 teams, 6 weeks of 2 matches each week
     *
     * @param  list<int>  $teamIds
     * @return list<array{week:int,home_team_id:int,away_team_id:int}>
     */
    public function generate(array $teamIds): array
    {
        $n = count($teamIds);

        if ($n < 2 || $n % 2 !== 0) {
            throw new InvalidArgumentException(
                "FixtureGenerator needs an even number of teams (>= 2); got {$n}."
            );
        }
        if (count(array_unique($teamIds)) !== $n) {
            throw new InvalidArgumentException('Team IDs must be unique.');
        }

        $weeksPerLeg = $n - 1;
        $rotation = array_values($teamIds);
        $firstLeg = [];

        for ($week = 1; $week <= $weeksPerLeg; $week++) {
            for ($i = 0; $i < $n / 2; $i++) {
                $firstLeg[] = [
                    'week' => $week,
                    'home_team_id' => $rotation[$i],
                    'away_team_id' => $rotation[$n - 1 - $i],
                ];
            }
            // Rotate: keep $rotation[0] fixed, rotate the rest clockwise.
            $fixed = $rotation[0];
            $tail = array_slice($rotation, 1);
            array_unshift($tail, array_pop($tail));
            $rotation = array_merge([$fixed], $tail);
        }

        $secondLeg = array_map(fn (array $m): array => [
            'week' => $m['week'] + $weeksPerLeg,
            'home_team_id' => $m['away_team_id'],
            'away_team_id' => $m['home_team_id'],
        ], $firstLeg);

        return array_merge($firstLeg, $secondLeg);
    }
}
