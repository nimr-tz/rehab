/**
 * Unit + property tests for the API client retry/backoff decision logic.
 *
 * These cover the pure functions extracted from the axios response interceptor
 * in src/api/http.js, so the retry policy can be verified without a live network.
 */

import fc from 'fast-check';
import {
  computeRetryDecision,
  computeRetryDelay,
  MAX_RETRIES,
  RETRY_DELAY,
  MAX_RETRY_WALL_MS,
} from '../../src/api/http';

describe('computeRetryDecision', () => {
  it('retries on 5xx server errors within limits', () => {
    [500, 502, 503, 504].forEach((status) => {
      const { retry } = computeRetryDecision({ status, hasResponse: true, retryCount: 0, elapsedMs: 0 });
      expect(retry).toBe(true);
    });
  });

  it('retries on network errors (no response)', () => {
    const { retry } = computeRetryDecision({ hasResponse: false, retryCount: 0, elapsedMs: 0 });
    expect(retry).toBe(true);
  });

  it('retries on request timeout (ECONNABORTED)', () => {
    const { retry } = computeRetryDecision({ code: 'ECONNABORTED', hasResponse: false, retryCount: 0, elapsedMs: 0 });
    expect(retry).toBe(true);
  });

  it('never retries a 401 (handled by the auth flow instead)', () => {
    const { retry } = computeRetryDecision({ status: 401, hasResponse: true, retryCount: 0, elapsedMs: 0 });
    expect(retry).toBe(false);
  });

  it('does not retry other 4xx client errors', () => {
    [400, 403, 404, 409, 422].forEach((status) => {
      const { retry } = computeRetryDecision({ status, hasResponse: true, retryCount: 0, elapsedMs: 0 });
      expect(retry).toBe(false);
    });
  });

  it('flags 429 as rate-limited and allows exactly one retry', () => {
    const first = computeRetryDecision({ status: 429, hasResponse: true, retryCount: 0, elapsedMs: 0 });
    expect(first).toEqual({ retry: true, isRateLimited: true });

    const second = computeRetryDecision({ status: 429, hasResponse: true, retryCount: 1, elapsedMs: 0 });
    expect(second).toEqual({ retry: false, isRateLimited: true });
  });

  it('stops retrying once MAX_RETRIES is reached', () => {
    const { retry } = computeRetryDecision({ status: 500, hasResponse: true, retryCount: MAX_RETRIES, elapsedMs: 0 });
    expect(retry).toBe(false);
  });

  it('stops retrying once the wall-time budget is exhausted', () => {
    const { retry } = computeRetryDecision({ status: 500, hasResponse: true, retryCount: 0, elapsedMs: MAX_RETRY_WALL_MS });
    expect(retry).toBe(false);
  });

  it('property: 4xx (except 429) is never retried regardless of attempt/time', () => {
    fc.assert(
      fc.property(
        fc.integer({ min: 400, max: 499 }).filter((s) => s !== 429),
        fc.nat({ max: 5 }),
        fc.nat({ max: MAX_RETRY_WALL_MS }),
        (status, retryCount, elapsedMs) => {
          const { retry } = computeRetryDecision({ status, hasResponse: true, retryCount, elapsedMs });
          expect(retry).toBe(false);
        }
      ),
      { numRuns: 50 }
    );
  });
});

describe('computeRetryDelay', () => {
  it('uses exponential backoff for normal retries', () => {
    expect(computeRetryDelay({ isRateLimited: false, retryCount: 1 })).toBe(RETRY_DELAY); // 1000
    expect(computeRetryDelay({ isRateLimited: false, retryCount: 2 })).toBe(RETRY_DELAY * 2); // 2000
  });

  it('honours the Retry-After header for rate-limited responses', () => {
    expect(computeRetryDelay({ isRateLimited: true, retryAfterHeader: '3' })).toBe(3000);
  });

  it('defaults rate-limit delay to 5s when Retry-After is missing/invalid', () => {
    expect(computeRetryDelay({ isRateLimited: true })).toBe(5000);
    expect(computeRetryDelay({ isRateLimited: true, retryAfterHeader: 'not-a-number' })).toBe(5000);
  });

  it('caps the rate-limit delay at 10s', () => {
    expect(computeRetryDelay({ isRateLimited: true, retryAfterHeader: '999' })).toBe(10000);
  });
});
