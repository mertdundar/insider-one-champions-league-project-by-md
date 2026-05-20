import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { describe, it, expect, beforeEach } from 'vitest';
import App from './App.vue';

describe('App', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('renders the league title', () => {
        const wrapper = mount(App);
        expect(wrapper.text()).toContain('Insider One Champions League by Mert Dundar');
    });
});
