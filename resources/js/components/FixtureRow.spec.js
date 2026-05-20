import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import FixtureRow from './FixtureRow.vue';

vi.mock('../api/client.js', () => ({
    api: {
        state: vi.fn(), generate: vi.fn(), playNext: vi.fn(), playAll: vi.fn(),
        editFixture: vi.fn().mockResolvedValue({
            status: 'in_progress', current_week: 1, total_weeks: 6,
            teams: [], table: [], week_fixtures: {}, predictions: null,
        }),
        reset: vi.fn(),
    },
    ApiError: class extends Error {},
}));

import { api } from '../api/client.js';

function played() {
    return {
        id: 11, week: 1,
        home: { id: 1, name: 'Team 93', short_name: 'T93' },
        away: { id: 2, name: 'Team 75', short_name: 'T75' },
        home_score: 2, away_score: 1, played_at: '2026-05-19T14:32:11Z',
    };
}

function mountInTable(props) {
    return mount({
        components: { FixtureRow },
        props: ['fixture'],
        template: '<table><tbody><FixtureRow :fixture="fixture" /></tbody></table>',
    }, { props });
}

describe('FixtureRow', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('shows the score in read mode and an Edit button for played fixtures', () => {
        const w = mountInTable({ fixture: played() });
        expect(w.text()).toContain('2 - 1');
        expect(w.find('.edit-link').exists()).toBe(true);
    });

    it('hides the Edit button on unplayed fixtures', () => {
        const w = mountInTable({
            fixture: { ...played(), home_score: null, away_score: null, played_at: null },
        });
        expect(w.find('.edit-link').exists()).toBe(false);
        expect(w.text()).toContain('–');
    });

    it('clicking Edit reveals score inputs prefilled with the current score', async () => {
        const w = mountInTable({ fixture: played() });
        await w.find('.edit-link').trigger('click');

        const inputs = w.findAll('input[type="number"]');
        expect(inputs).toHaveLength(2);
        expect(inputs[0].element.value).toBe('2');
        expect(inputs[1].element.value).toBe('1');
    });

    it('Save calls editFixture with the new score and exits edit mode', async () => {
        const w = mountInTable({ fixture: played() });
        await w.find('.edit-link').trigger('click');

        const inputs = w.findAll('input[type="number"]');
        await inputs[0].setValue(5);
        await inputs[1].setValue(0);
        await w.find('.save').trigger('click');
        await new Promise((r) => setTimeout(r, 0));

        expect(api.editFixture).toHaveBeenCalledWith(11, { home_score: 5, away_score: 0 });
    });

    it('Cancel exits edit mode without calling the API', async () => {
        const w = mountInTable({ fixture: played() });
        await w.find('.edit-link').trigger('click');
        await w.find('.cancel').trigger('click');

        expect(api.editFixture).not.toHaveBeenCalled();
        expect(w.find('.edit-link').exists()).toBe(true);
    });

    it('disables Save on negative input', async () => {
        const w = mountInTable({ fixture: played() });
        await w.find('.edit-link').trigger('click');
        await w.findAll('input[type="number"]')[0].setValue(-1);

        expect(w.find('.save').attributes('disabled')).toBeDefined();
    });
});
