import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import LeagueTable from './LeagueTable.vue';
import WeekResults from './WeekResults.vue';
import ChampionshipPredictions from './ChampionshipPredictions.vue';
import { useLeagueStore } from '../stores/league.js';

vi.mock('../api/client.js', () => ({
    api: { state: vi.fn(), generate: vi.fn(), playNext: vi.fn(), playAll: vi.fn(), editFixture: vi.fn(), reset: vi.fn() },
    ApiError: class extends Error {},
}));

function team(id, name, short) {
    return { id, name, short_name: short };
}

describe('LeagueTable', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('renders one row per table entry with Pts column populated', () => {
        const store = useLeagueStore();
        store.table = [
            { position: 1, team: team(1, 'Team 93', 'T93'), played: 2, won: 2, drawn: 0, lost: 0, goals_for: 4, goals_against: 1, goal_diff: 3, pts: 6 },
            { position: 2, team: team(2, 'Team 82', 'T82'), played: 2, won: 1, drawn: 0, lost: 1, goals_for: 2, goals_against: 2, goal_diff: 0, pts: 3 },
        ];

        const wrapper = mount(LeagueTable);
        const rows = wrapper.findAll('tbody tr');
        expect(rows).toHaveLength(2);
        expect(rows[0].text()).toContain('Team 93');
        expect(rows[0].text()).toContain('6');
    });
});

describe('WeekResults', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('renders fixtures for the current week with scores', () => {
        const store = useLeagueStore();
        store.currentWeek = 1;
        store.weekFixtures = {
            '1': [{
                id: 10, week: 1,
                home: team(1, 'Team 93', 'T93'),
                away: team(2, 'Team 75', 'T75'),
                home_score: 2, away_score: 1, played_at: '2026-05-19T14:32:11Z',
            }],
        };

        const wrapper = mount(WeekResults);
        expect(wrapper.text()).toContain('Week 1');
        expect(wrapper.text()).toContain('Team 93');
        expect(wrapper.text()).toContain('2 - 1');
        expect(wrapper.text()).toContain('Team 75');
    });

    it('renders dash for unplayed scores', () => {
        const store = useLeagueStore();
        store.currentWeek = 1;
        store.weekFixtures = {
            '1': [{
                id: 10, week: 1,
                home: team(1, 'Team 93', 'T93'),
                away: team(2, 'Team 75', 'T75'),
                home_score: null, away_score: null, played_at: null,
            }],
        };

        const wrapper = mount(WeekResults);
        expect(wrapper.text()).toContain('–');
    });

    it('lists week-1 fixtures after generate when no week has been played yet', () => {
        const store = useLeagueStore();
        store.currentWeek = null;
        store.weekFixtures = {
            '1': [{
                id: 10, week: 1,
                home: team(1, 'Team 93', 'T93'),
                away: team(2, 'Team 75', 'T75'),
                home_score: null, away_score: null, played_at: null,
            }],
            '2': [{
                id: 11, week: 2,
                home: team(2, 'Team 75', 'T75'),
                away: team(1, 'Team 93', 'T93'),
                home_score: null, away_score: null, played_at: null,
            }],
        };

        const wrapper = mount(WeekResults);
        expect(wrapper.text()).toContain('Week 1');
        expect(wrapper.findAll('tbody tr')).toHaveLength(1);
        expect(wrapper.text()).toContain('Team 93');
        expect(wrapper.text()).toContain('Team 75');
        expect(wrapper.text()).not.toContain('No fixtures yet');
    });
});

describe('ChampionshipPredictions', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('shows the empty state when predictions are null', () => {
        const store = useLeagueStore();
        store.predictions = null;

        const wrapper = mount(ChampionshipPredictions);
        expect(wrapper.text()).toContain('Available from week 4');
    });

    it('lists predictions with integer percentages once available', () => {
        const store = useLeagueStore();
        store.predictions = [
            { team: team(1, 'Team 93', 'T93'), percent: 62 },
            { team: team(2, 'Team 75', 'T75'), percent: 38 },
        ];

        const wrapper = mount(ChampionshipPredictions);
        expect(wrapper.text()).toContain('Team 93');
        expect(wrapper.text()).toContain('62');
        expect(wrapper.text()).toContain('Team 75');
        expect(wrapper.text()).toContain('38');
        expect(wrapper.text()).not.toContain('62.');
        expect(wrapper.text()).not.toContain('37.6');
    });
});
