import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import Controls from './Controls.vue';
import { useLeagueStore } from '../stores/league.js';

vi.mock('../api/client.js', () => {
    const base = {
        status: 'in_progress', current_week: 1, total_weeks: 6,
        teams: [], table: [], week_fixtures: {}, predictions: null,
    };
    return {
        api: {
            state: vi.fn().mockResolvedValue(base),
            generate: vi.fn().mockResolvedValue(base),
            playNext: vi.fn().mockResolvedValue(base),
            playAll: vi.fn().mockResolvedValue({ ...base, status: 'complete', current_week: 6 }),
            editFixture: vi.fn().mockResolvedValue(base),
            reset: vi.fn().mockResolvedValue({ ...base, status: 'setup', current_week: null }),
        },
        ApiError: class extends Error {},
    };
});

import { api } from '../api/client.js';

describe('Controls', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('renders all three buttons', () => {
        const wrapper = mount(Controls);
        const labels = wrapper.findAll('button').map((b) => b.text());
        expect(labels).toEqual(['Play All Weeks', 'Play Next Week', 'Reset Data']);
    });

    it('Play Next triggers store.playNext', async () => {
        const store = useLeagueStore();
        store.status = 'in_progress';

        const wrapper = mount(Controls);
        await wrapper.findAll('button')[1].trigger('click');

        expect(api.playNext).toHaveBeenCalledOnce();
    });

    it('Play All triggers store.playAll', async () => {
        const store = useLeagueStore();
        store.status = 'in_progress';

        const wrapper = mount(Controls);
        await wrapper.findAll('button')[0].trigger('click');

        expect(api.playAll).toHaveBeenCalledOnce();
    });

    it('Reset triggers store.reset', async () => {
        const wrapper = mount(Controls);
        await wrapper.findAll('button')[2].trigger('click');

        expect(api.reset).toHaveBeenCalledOnce();
    });

    it('disables play buttons when season is complete', () => {
        const store = useLeagueStore();
        store.status = 'complete';

        const wrapper = mount(Controls);
        const [playAll, playNext, reset] = wrapper.findAll('button');
        expect(playAll.attributes('disabled')).toBeDefined();
        expect(playNext.attributes('disabled')).toBeDefined();
        expect(reset.attributes('disabled')).toBeUndefined();
    });
});
