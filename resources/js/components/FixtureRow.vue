<template>
    <tr v-if="!editing">
        <td class="team home">{{ fixture.home.name }}</td>
        <td class="score">{{ scoreDisplay }}</td>
        <td class="team away">{{ fixture.away.name }}</td>
        <td class="actions">
            <button
                v-if="canEdit"
                type="button"
                class="edit-link"
                @click="startEdit"
            >
                Edit
            </button>
        </td>
    </tr>
    <tr v-else>
        <td class="team home">{{ fixture.home.name }}</td>
        <td class="score-edit">
            <input type="number" min="0" v-model.number="draftHome" />
            <span>-</span>
            <input type="number" min="0" v-model.number="draftAway" />
        </td>
        <td class="team away">{{ fixture.away.name }}</td>
        <td class="actions">
            <button
                type="button"
                class="save"
                :disabled="!valid || store.loading"
                @click="save"
            >Save</button>
            <button
                type="button"
                class="cancel"
                @click="cancel"
            >Cancel</button>
        </td>
    </tr>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useLeagueStore } from '../stores/league.js';

const props = defineProps({ fixture: { type: Object, required: true } });
const store = useLeagueStore();
const editing = ref(false);
const draftHome = ref(0);
const draftAway = ref(0);

const isPlayed = computed(() =>
    props.fixture.home_score !== null && props.fixture.away_score !== null,
);
const canEdit = computed(() => isPlayed.value);
const scoreDisplay = computed(() => isPlayed.value
    ? `${props.fixture.home_score} - ${props.fixture.away_score}`
    : '–');
const valid = computed(() =>
    Number.isInteger(draftHome.value) && draftHome.value >= 0 &&
    Number.isInteger(draftAway.value) && draftAway.value >= 0,
);

function startEdit() {
    draftHome.value = props.fixture.home_score;
    draftAway.value = props.fixture.away_score;
    editing.value = true;
}

function cancel() { editing.value = false; }

async function save() {
    await store.editFixture(props.fixture.id, {
        home_score: draftHome.value,
        away_score: draftAway.value,
    });
    editing.value = false;
}
</script>

<style scoped>
.team.home { text-align: right; padding: 8px 12px; }
.team.away { text-align: left; padding: 8px 12px; }
.score, .score-edit { text-align: center; font-weight: 600; min-width: 80px; padding: 8px 12px; }
.score-edit { display: flex; align-items: center; justify-content: center; gap: 6px; }
.score-edit input { width: 40px; padding: 4px 6px; text-align: center; border: 1px solid var(--border); border-radius: 4px; font: inherit; }
.actions { padding: 8px 12px; text-align: center; }
.edit-link { background: none; border: 0; color: var(--accent); cursor: pointer; padding: 2px 8px; font: inherit; }
.edit-link:hover { text-decoration: underline; }
.save, .cancel { padding: 4px 10px; margin: 0 2px; border: 0; border-radius: 4px; cursor: pointer; font: inherit; }
.save { background: var(--accent); color: white; }
.save:disabled { opacity: 0.5; cursor: not-allowed; }
.cancel { background: var(--surface-alt); color: var(--ink); border: 1px solid var(--border); }
tr td { border-bottom: 1px solid var(--border); }
</style>
