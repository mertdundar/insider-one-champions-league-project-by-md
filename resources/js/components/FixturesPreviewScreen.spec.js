import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import FixturesPreviewScreen from './FixturesPreviewScreen.vue';
import { useLeagueStore } from '../stores/league.js';

vi.mock('../api/client.js', () => ({
    api: {
        state: vi.fn(),
        generate: vi.fn(),
        playNext: vi.fn(),
        playAll: vi.fn(),
        editFixture: vi.fn(),
        reset: vi.fn().mockResolvedValue({
            status: 'setup', current_week: null, total_weeks: 6,
            teams: [], table: [], week_fixtures: {}, predictions: null,
        }),
    },
    ApiError: class ApiError extends Error {},
}));

import { api } from '../api/client.js';

function team(id, name, short) {
    return { id, name, short_name: short };
}

function generatedSeason() {
    const t93 = team(1, 'Team 93', 'T93');
    const t88 = team(2, 'Team 88', 'T88');
    const t82 = team(3, 'Team 82', 'T82');
    const t75 = team(4, 'Team 75', 'T75');
    const unplayed = (id, week, home, away) => ({
        id, week, home, away, home_score: null, away_score: null, played_at: null,
    });
    return {
        totalWeeks: 6,
        currentWeek: null,
        status: 'in_progress',
        weekFixtures: {
            '1': [unplayed(1, 1, t93, t75), unplayed(2, 1, t88, t82)],
            '2': [unplayed(3, 2, t75, t82), unplayed(4, 2, t93, t88)],
            '3': [unplayed(5, 3, t82, t93), unplayed(6, 3, t75, t88)],
            '4': [unplayed(7, 4, t75, t93), unplayed(8, 4, t82, t88)],
            '5': [unplayed(9, 5, t82, t75), unplayed(10, 5, t88, t93)],
            '6': [unplayed(11, 6, t93, t82), unplayed(12, 6, t88, t75)],
        },
    };
}

describe('FixturesPreviewScreen', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('renders a panel for each of the 6 weeks', () => {
        const store = useLeagueStore();
        Object.assign(store, generatedSeason());

        const wrapper = mount(FixturesPreviewScreen);
        const text = wrapper.text();
        for (let w = 1; w <= 6; w++) {
            expect(text).toContain(`Week ${w}`);
        }
    });

    it('lists every fixture with both team names and no score', () => {
        const store = useLeagueStore();
        Object.assign(store, generatedSeason());

        const wrapper = mount(FixturesPreviewScreen);
        const rows = wrapper.findAll('tbody tr');
        expect(rows).toHaveLength(12);
        expect(wrapper.text()).toContain('Team 93');
        expect(wrapper.text()).toContain('Team 75');
        expect(wrapper.text()).not.toMatch(/\d\s*-\s*\d/);
    });

    it('calls store.startSimulation when Start Simulation is clicked', async () => {
        const store = useLeagueStore();
        Object.assign(store, generatedSeason());
        const spy = vi.spyOn(store, 'startSimulation');

        const wrapper = mount(FixturesPreviewScreen);
        await wrapper.find('button.start').trigger('click');

        expect(spy).toHaveBeenCalledOnce();
        expect(store.simulationStarted).toBe(true);
    });

    it('calls api.reset when Regenerate Fixtures is clicked', async () => {
        const store = useLeagueStore();
        Object.assign(store, generatedSeason());

        const wrapper = mount(FixturesPreviewScreen);
        await wrapper.find('button.regenerate').trigger('click');

        expect(api.reset).toHaveBeenCalledOnce();
    });

    it('disables both buttons while a store action is loading', () => {
        const store = useLeagueStore();
        Object.assign(store, generatedSeason());
        store.loading = true;

        const wrapper = mount(FixturesPreviewScreen);
        expect(wrapper.find('button.start').attributes('disabled')).toBeDefined();
        expect(wrapper.find('button.regenerate').attributes('disabled')).toBeDefined();
    });
});
