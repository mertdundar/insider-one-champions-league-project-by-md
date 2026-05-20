import { defineStore } from 'pinia';
import { api } from '../api/client.js';

const emptyServerState = () => ({
    status: 'setup',
    currentWeek: null,
    totalWeeks: 6,
    teams: [],
    table: [],
    weekFixtures: {},
    predictions: null,
});

export const useLeagueStore = defineStore('league', {
    state: () => ({
        ...emptyServerState(),
        loading: false,
        error: null,
        simulationStarted: false,
    }),

    getters: {
        isSetup: (s) => s.status === 'setup',
        isInProgress: (s) => s.status === 'in_progress',
        predictionsAvailable: (s) => s.predictions !== null,
        canPlayNext: (s) => s.status === 'in_progress' && !s.loading,
        canPlayAll: (s) => s.status === 'in_progress' && !s.loading,
        showFixturesPreview: (s) =>
            s.status === 'in_progress' && s.currentWeek === null && !s.simulationStarted,
    },

    actions: {
        async _run(fn) {
            this.loading = true;
            this.error = null;
            try {
                const data = await fn();
                this._apply(data);
            } catch (e) {
                this.error = { code: e.code, message: e.message };
            } finally {
                this.loading = false;
            }
        },
        _apply(data) {
            this.status = data.status;
            this.currentWeek = data.current_week;
            this.totalWeeks = data.total_weeks;
            this.teams = data.teams || [];
            this.table = data.table || [];
            this.weekFixtures = data.week_fixtures || {};
            this.predictions = data.predictions;
            if (data.status === 'setup') {
                this.simulationStarted = false;
            }
        },
        startSimulation() { this.simulationStarted = true; },
        fetchState() { return this._run(() => api.state()); },
        generateFixtures() { return this._run(() => api.generate()); },
        playNext() { return this._run(() => api.playNext()); },
        playAll() { return this._run(() => api.playAll()); },
        editFixture(id, scores) { return this._run(() => api.editFixture(id, scores)); },
        reset() { return this._run(() => api.reset()); },
    },
});
