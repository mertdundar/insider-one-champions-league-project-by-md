import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useLeagueStore } from './league.js';
import { ApiError } from '../api/client.js';

vi.mock('../api/client.js', async (orig) => {
    const actual = await orig();
    return {
        ...actual,
        api: {
            state: vi.fn(),
            generate: vi.fn(),
            playNext: vi.fn(),
            playAll: vi.fn(),
            editFixture: vi.fn(),
            reset: vi.fn(),
        },
    };
});

import { api } from '../api/client.js';

const inProgressState = () => ({
    status: 'in_progress',
    current_week: 1,
    total_weeks: 6,
    teams: [{ id: 1, name: 'Team 93', short_name: 'T93', strength: 88 }],
    table: [],
    week_fixtures: { '1': [] },
    predictions: null,
});

describe('useLeagueStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('initial state is setup with empty data', () => {
        const store = useLeagueStore();
        expect(store.status).toBe('setup');
        expect(store.teams).toEqual([]);
        expect(store.predictions).toBeNull();
        expect(store.isSetup).toBe(true);
    });

    it('fetchState applies server response', async () => {
        api.state.mockResolvedValue(inProgressState());

        const store = useLeagueStore();
        await store.fetchState();

        expect(store.status).toBe('in_progress');
        expect(store.currentWeek).toBe(1);
        expect(store.teams).toHaveLength(1);
        expect(store.isInProgress).toBe(true);
    });

    it('captures ApiError on failure and clears loading', async () => {
        api.generate.mockRejectedValue(new ApiError('FIXTURES_EXIST', 'Already in progress', 409));

        const store = useLeagueStore();
        await store.generateFixtures();

        expect(store.error).toEqual({ code: 'FIXTURES_EXIST', message: 'Already in progress' });
        expect(store.loading).toBe(false);
    });

    it('toggles loading around an async run', async () => {
        let resolveFn;
        api.playAll.mockReturnValue(new Promise((r) => { resolveFn = r; }));

        const store = useLeagueStore();
        const promise = store.playAll();
        expect(store.loading).toBe(true);

        resolveFn(inProgressState());
        await promise;

        expect(store.loading).toBe(false);
    });

    it('predictionsAvailable getter reflects non-null predictions', async () => {
        api.state.mockResolvedValue({
            ...inProgressState(),
            current_week: 4,
            predictions: [{ team: { id: 1, name: 'Team 93', short_name: 'T93' }, percent: 40 }],
        });

        const store = useLeagueStore();
        await store.fetchState();

        expect(store.predictionsAvailable).toBe(true);
    });

    it('canPlayNext is false while loading even if in progress', async () => {
        api.state.mockResolvedValue(inProgressState());
        const store = useLeagueStore();
        await store.fetchState();
        expect(store.canPlayNext).toBe(true);

        let resolveFn;
        api.playNext.mockReturnValue(new Promise((r) => { resolveFn = r; }));
        const promise = store.playNext();
        expect(store.canPlayNext).toBe(false);
        resolveFn(inProgressState());
        await promise;
    });

    it('showFixturesPreview is true after generate before simulation starts', async () => {
        api.generate.mockResolvedValue({
            ...inProgressState(),
            current_week: null,
        });

        const store = useLeagueStore();
        await store.generateFixtures();

        expect(store.showFixturesPreview).toBe(true);
        expect(store.simulationStarted).toBe(false);
    });

    it('startSimulation flips the flag and hides the preview', async () => {
        api.generate.mockResolvedValue({ ...inProgressState(), current_week: null });
        const store = useLeagueStore();
        await store.generateFixtures();

        store.startSimulation();

        expect(store.simulationStarted).toBe(true);
        expect(store.showFixturesPreview).toBe(false);
    });

    it('showFixturesPreview is false once at least one week has been played', async () => {
        api.state.mockResolvedValue(inProgressState());
        const store = useLeagueStore();
        await store.fetchState();
        expect(store.currentWeek).toBe(1);
        expect(store.showFixturesPreview).toBe(false);
    });

    it('reset clears simulationStarted by returning to setup', async () => {
        const store = useLeagueStore();
        store.simulationStarted = true;
        api.reset.mockResolvedValue({
            status: 'setup', current_week: null, total_weeks: 6,
            teams: [], table: [], week_fixtures: {}, predictions: null,
        });
        await store.reset();
        expect(store.simulationStarted).toBe(false);
    });
});
