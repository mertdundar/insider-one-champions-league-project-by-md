<template>
    <Panel :title="title">
        <table class="results">
            <tbody>
                <FixtureRow v-for="f in fixtures" :key="f.id" :fixture="f" />
            </tbody>
        </table>
        <p v-if="fixtures.length === 0" class="empty">No fixtures yet.</p>
    </Panel>
</template>

<script setup>
import { computed } from 'vue';
import Panel from './Panel.vue';
import FixtureRow from './FixtureRow.vue';
import { useLeagueStore } from '../stores/league.js';

const store = useLeagueStore();

const week = computed(() => store.currentWeek ?? 0);
const lookupWeek = computed(() => store.currentWeek ?? 1);
const title = computed(() => (week.value === 0 ? 'Week 1' : `Week ${week.value}`));
const fixtures = computed(() => store.weekFixtures[String(lookupWeek.value)] || []);
</script>

<style scoped>
.results { width: 100%; min-width: 420px; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.results tr:last-child td { border-bottom: 0; }
.empty { padding: 12px; color: var(--ink-muted); text-align: center; }
</style>
