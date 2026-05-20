<template>
    <Panel title="Championship Predictions">
        <table v-if="store.predictionsAvailable" class="predictions">
            <thead>
                <tr><th class="left">Team</th><th>%</th></tr>
            </thead>
            <tbody>
                <tr v-for="row in store.predictions" :key="row.team.id">
                    <td class="left">{{ row.team.name }}</td>
                    <td>{{ row.percent }}</td>
                </tr>
            </tbody>
        </table>
        <p v-else class="empty">Available from week 4.</p>
    </Panel>
</template>

<script setup>
import Panel from './Panel.vue';
import { useLeagueStore } from '../stores/league.js';

const store = useLeagueStore();
</script>

<style scoped>
.predictions { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.predictions th, .predictions td { padding: 10px 12px; text-align: center; border-bottom: 1px solid var(--border); }
.predictions th.left, .predictions td.left { text-align: left; }
.predictions th { font-weight: 500; background: var(--surface-alt); color: var(--ink-muted); }
.predictions tbody tr:last-child td { border-bottom: 0; }
.empty { padding: 16px; color: var(--ink-muted); text-align: center; }
</style>
