import { describe, it, expect, beforeEach, vi } from 'vitest';
import { api, ApiError } from './client.js';

describe('api client', () => {
    beforeEach(() => {
        global.fetch = vi.fn();
    });

    it('returns parsed JSON on 200', async () => {
        global.fetch.mockResolvedValue({
            ok: true,
            status: 200,
            text: () => Promise.resolve('{"status":"setup"}'),
        });

        const result = await api.state();
        expect(result).toEqual({ status: 'setup' });
    });

    it('wraps the backend error envelope into ApiError', async () => {
        global.fetch.mockResolvedValue({
            ok: false,
            status: 409,
            statusText: 'Conflict',
            text: () => Promise.resolve('{"error":{"code":"FIXTURES_EXIST","message":"Already in progress","details":{}}}'),
        });

        await expect(api.generate()).rejects.toMatchObject({
            name: 'ApiError',
            code: 'FIXTURES_EXIST',
            status: 409,
        });
    });

    it('falls back to UNKNOWN when the body is not the expected envelope', async () => {
        global.fetch.mockResolvedValue({
            ok: false,
            status: 500,
            statusText: 'Server Error',
            text: () => Promise.resolve('not json'),
        });

        await expect(api.reset()).rejects.toMatchObject({
            code: 'UNKNOWN',
            status: 500,
        });
    });

    it('wraps a network error', async () => {
        global.fetch.mockRejectedValue(new TypeError('Failed to fetch'));

        await expect(api.state()).rejects.toMatchObject({
            code: 'NETWORK_ERROR',
            status: 0,
        });
    });

    it('sends JSON body on PUT', async () => {
        global.fetch.mockResolvedValue({
            ok: true,
            status: 200,
            text: () => Promise.resolve('{}'),
        });

        await api.editFixture(11, { home_score: 2, away_score: 1 });

        const [url, opts] = global.fetch.mock.calls[0];
        expect(url).toBe('/api/fixtures/11');
        expect(opts.method).toBe('PUT');
        expect(opts.headers['Content-Type']).toBe('application/json');
        expect(JSON.parse(opts.body)).toEqual({ home_score: 2, away_score: 1 });
    });
});
