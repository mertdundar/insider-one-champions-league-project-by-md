<template>
    <main class="app">
        <h1>Insider One Champions League by Mert Dundar</h1>
        <TeamsListScreen v-if="store.isSetup" />
        <FixturesPreviewScreen v-else-if="store.showFixturesPreview" />
        <SimulationScreen v-else />
        <p v-if="store.error" class="error">{{ store.error.message }}</p>
    </main>
</template>

<script setup>
import { onMounted } from 'vue';
import { useLeagueStore } from './stores/league.js';
import TeamsListScreen from './components/TeamsListScreen.vue';
import FixturesPreviewScreen from './components/FixturesPreviewScreen.vue';
import SimulationScreen from './components/SimulationScreen.vue';

const store = useLeagueStore();
onMounted(() => store.fetchState());
</script>

<style scoped>
.app { max-width: 1200px; margin: 0 auto; padding: 24px; }
h1 { font-size: 22px; margin: 0 0 20px; font-weight: 500; color: var(--ink-muted); text-align: center; }
.error { padding: 12px 16px; background: #fdecea; color: var(--danger); border-radius: var(--radius); margin-top: 16px; }
</style>
