import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, isDevBuild } from '../config/appConfig';

export const logDev = (...args) => {
    if (isDevBuild) console.log(...args);
};

export const warnDev = (...args) => {
    if (isDevBuild) console.warn(...args);
};

export const errorDev = (...args) => {
    if (isDevBuild) console.error(...args);
};

// Retry configuration
export const MAX_RETRIES = 2;
export const RETRY_DELAY = 1000; // 1 second base delay
export const MAX_RETRY_WALL_MS = 15000; // never spend more than 15 s in retries

/**
 * Pure decision function: given the error/attempt state, should the request be
 * retried? Extracted from the interceptor so it can be unit-tested without a
 * live network. Never retries 401 (handled separately) or other 4xx.
 */
export const computeRetryDecision = ({ status, hasResponse, code, retryCount = 0, elapsedMs = 0 } = {}) => {
    const withinWallTime = elapsedMs < MAX_RETRY_WALL_MS;
    const isRateLimited = status === 429;

    if (isRateLimited) {
        // Rate-limited responses get a single dedicated retry.
        return { retry: withinWallTime && retryCount < 1, isRateLimited };
    }

    const retry =
        withinWallTime &&
        retryCount < MAX_RETRIES &&
        (
            !hasResponse ||                 // Network error
            code === 'ECONNABORTED' ||      // Timeout
            (typeof status === 'number' && status >= 500) // Server error (not 4xx)
        );

    return { retry, isRateLimited };
};

/**
 * Pure delay calculator. Rate-limited requests honour `Retry-After` (capped at
 * 10 s); everything else uses exponential backoff off the (already incremented)
 * attempt count.
 */
export const computeRetryDelay = ({ isRateLimited, retryCount = 1, retryAfterHeader } = {}) => {
    if (isRateLimited) {
        const seconds = parseInt(retryAfterHeader || '5', 10);
        return Math.min((Number.isFinite(seconds) ? seconds : 5) * 1000, 10000);
    }
    return RETRY_DELAY * Math.pow(2, retryCount - 1);
};

// Auth error handler — set by ProfileContext so the API layer can signal logout
let _authErrorHandler = null;
export const setAuthErrorHandler = (handler) => {
    _authErrorHandler = handler;
};

// Create axios instance with configuration
const apiClient = axios.create({
    baseURL: API_BASE_URL,
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
});

// Request interceptor for logging and modifications
apiClient.interceptors.request.use(
    async (config) => {
        // Try to get token from AsyncStorage
        try {
            const token = await AsyncStorage.getItem('@auth_token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
        } catch (error) {
            warnDev('[API Client] Error reading headers:', error);
        }

        logDev(`[API Request] ${config.method?.toUpperCase()} ${config.url}`);
        return config;
    },
    (error) => {
        errorDev('[API Request Error]', error);
        return Promise.reject(error);
    }
);

// Response interceptor for error handling and retry logic
apiClient.interceptors.response.use(
    (response) => {
        logDev(`[API Response] ${response.config.url} - Status: ${response.status}`);
        return response;
    },
    async (error) => {
        const config = error.config;
        const status = error.response?.status;

        // 401 → clear token and signal the auth context immediately; do not retry
        if (status === 401) {
            try { await AsyncStorage.removeItem('@auth_token'); } catch (_) {}
            _authErrorHandler?.();
            warnDev('[API] 401 Unauthorized — session cleared');
            return Promise.reject(error);
        }

        // Initialize retry state
        if (!config._retryCount) {
            config._retryCount = 0;
            config._retryStart = Date.now();
        }

        const elapsedMs = Date.now() - config._retryStart;
        const { retry, isRateLimited } = computeRetryDecision({
            status,
            hasResponse: !!error.response,
            code: error.code,
            retryCount: config._retryCount,
            elapsedMs,
        });

        if (retry) {
            config._retryCount += 1;
            const delay = computeRetryDelay({
                isRateLimited,
                retryCount: config._retryCount,
                retryAfterHeader: error.response?.headers?.['retry-after'],
            });

            logDev(`[API Retry] Attempt ${config._retryCount} for ${config.url} after ${delay}ms`);

            await new Promise(resolve => setTimeout(resolve, delay));
            return apiClient(config);
        }

        // Log error details - use warn for client errors (4xx) to avoid RedBox in Expo
        if (error.response) {
            const logFn = status >= 500 ? errorDev : warnDev;
            logFn(`[API Error] ${config.url} - Status: ${status}`, error.response.data);
        } else if (error.request) {
            warnDev('[API Error] No response received', error.message);
        } else {
            warnDev('[API Error]', error.message);
        }

        return Promise.reject(error);
    }
);

// Helper function to handle API calls with consistent error handling
export const handleApiCall = async (apiCall) => {
    try {
        const response = await apiCall();
        return response.data;
    } catch (error) {
        // Re-throw with additional context
        throw {
            message: error.message,
            status: error.response?.status,
            data: error.response?.data,
            isNetworkError: !error.response,
        };
    }
};

export default apiClient;
