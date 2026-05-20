<template>
    <section class="preview">
        <h2>Generated Fixtures</h2>
        <div class="weeks">
            <Panel v-for="week in weeks" :key="week" :title="`Week ${week}`">
                <table class="fixtures">
                    <tbody>
                        <tr v-for="f in fixturesFor(week)" :key="f.id">
                            <td class="team home">{{ f.home.name }}</td>
                            <td class="vs">vs</td>
                            <td class="team away">{{ f.away.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </Panel>
        </div>
        <div class="actions">
            <AppButton
                class="start"
                :disabled="store.loading"
                @click="store.startSimulation()"
            >
                Start Simulation
            </AppButton>
            <AppButton
                class="regenerate"
                variant="danger"
                :disabled="store.loading"
                @click="store.reset()"
            >
                Regenerate Fixtures
            </AppButton>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import Panel from './Panel.vue';
import AppButton from './AppButton.vue';
import { useLeagueStore } from '../stores/league.js';

const store = useLeagueStore();
const weeks = computed(() => Array.from({ length: store.totalWeeks }, (_, i) => i + 1));
function fixturesFor(week) {
    return store.weekFixtures[String(week)] || [];
}
</script>

<style scoped>
.preview { width: 100%; }
h2 { font-size: 18px; margin: 0 0 16px; font-weight: 500; color: var(--ink-muted); }
.weeks {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
@media (max-width: 768px) {
    .weeks { grid-template-columns: 1fr; }
}
.fixtures { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.fixtures tr td { border-bottom: 1px solid var(--border); padding: 10px 12px; }
.fixtures tr:last-child td { border-bottom: 0; }
.team { width: 45%; }
.team.home { text-align: right; }
.team.away { text-align: left; }
.vs { text-align: center; color: var(--ink-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; }
.actions {
    margin-top: 24px;
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}
</style>
