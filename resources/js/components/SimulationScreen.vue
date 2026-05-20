<template>
    <section class="sim">
        <h2>Simulation</h2>
        <div class="grid">
            <LeagueTable />
            <WeekResults />
            <ChampionshipPredictions />
        </div>
        <Controls />
    </section>
</template>

<script setup>
import LeagueTable from './LeagueTable.vue';
import WeekResults from './WeekResults.vue';
import ChampionshipPredictions from './ChampionshipPredictions.vue';
import Controls from './Controls.vue';
</script>

<style scoped>
.sim { width: 100%; }
h2 { font-size: 18px; margin: 0 0 12px; font-weight: 500; color: var(--ink-muted); }
.grid {
    display: grid;
    grid-template-columns: 1.3fr 1.5fr 1fr;
    gap: 16px;
}
@media (max-width: 1199px) {
    .grid { grid-template-columns: 1fr; }
}
</style>
