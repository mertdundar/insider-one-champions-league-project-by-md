<template>
    <Panel title="Team Name">
        <table class="league-table">
            <thead>
                <tr>
                    <th class="left">Team</th>
                    <th>PTS</th>
                    <th>P</th>
                    <th>W</th>
                    <th>D</th>
                    <th>L</th>
                    <th>GD</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in store.table" :key="row.team.id">
                    <td class="left">{{ row.team.name }}</td>
                    <td><strong>{{ row.pts }}</strong></td>
                    <td>{{ row.played }}</td>
                    <td>{{ row.won }}</td>
                    <td>{{ row.drawn }}</td>
                    <td>{{ row.lost }}</td>
                    <td>{{ row.goal_diff }}</td>
                </tr>
            </tbody>
        </table>
    </Panel>
</template>

<script setup>
import Panel from './Panel.vue';
import { useLeagueStore } from '../stores/league.js';

const store = useLeagueStore();
</script>

<style scoped>
.league-table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.league-table th, .league-table td { padding: 10px 12px; text-align: center; border-bottom: 1px solid var(--border); }
.league-table th.left, .league-table td.left { text-align: left; }
.league-table th { font-weight: 500; background: var(--surface-alt); color: var(--ink-muted); }
.league-table tbody tr:last-child td { border-bottom: 0; }
</style>
