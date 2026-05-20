import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import TeamsListScreen from './TeamsListScreen.vue';
import { useLeagueStore } from '../stores/league.js';

vi.mock('../api/client.js', () => ({
    api: {
        state: vi.fn(),
        generate: vi.fn().mockResolvedValue({
            status: 'in_progress', current_week: null, total_weeks: 6,
            teams: [], table: [], week_fixtures: {}, predictions: null,
        }),
        playNext: vi.fn(), playAll: vi.fn(), editFixture: vi.fn(), reset: vi.fn(),
    },
    ApiError: class ApiError extends Error {},
}));

import { api } from '../api/client.js';

describe('TeamsListScreen', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('lists every team in the store', () => {
        const store = useLeagueStore();
        store.teams = [
            { id: 1, name: 'Team 93', short_name: 'T93', strength: 88 },
            { id: 2, name: 'Team 88', short_name: 'T88', strength: 92 },
            { id: 3, name: 'Team 82', short_name: 'T82', strength: 78 },
            { id: 4, name: 'Team 75', short_name: 'T75', strength: 82 },
        ];

        const wrapper = mount(TeamsListScreen);
        const text = wrapper.text();
        expect(text).toContain('Team 93');
        expect(text).toContain('Team 88');
        expect(text).toContain('Team 82');
        expect(text).toContain('Team 75');
    });

    it('calls store.generateFixtures when the button is clicked', async () => {
        const store = useLeagueStore();
        store.teams = [{ id: 1, name: 'Team 93', short_name: 'T93', strength: 88 }];

        const wrapper = mount(TeamsListScreen);
        await wrapper.find('button').trigger('click');

        expect(api.generate).toHaveBeenCalledOnce();
    });

    it('disables the button when no teams are loaded yet', () => {
        const store = useLeagueStore();
        store.teams = [];

        const wrapper = mount(TeamsListScreen);
        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });

    it('disables the button while an action is loading', () => {
        const store = useLeagueStore();
        store.teams = [{ id: 1, name: 'Team 93', short_name: 'T93', strength: 88 }];
        store.loading = true;

        const wrapper = mount(TeamsListScreen);
        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });
});
