const readEnv = (key) => {
  if (typeof process === 'undefined' || !process.env) return null;
  return process.env[key] || null;
};

const trimTrailingSlash = (value) => String(value || '').replace(/\/+$/, '');

// Set EXPO_PUBLIC_WEB_BASE_URL (and optionally EXPO_PUBLIC_API_BASE_URL) for
// each build profile; the placeholder only works for local development.
export const WEB_BASE_URL = trimTrailingSlash(
  readEnv('EXPO_PUBLIC_WEB_BASE_URL') || 'http://localhost:8000'
);

export const API_BASE_URL = trimTrailingSlash(
  readEnv('EXPO_PUBLIC_API_BASE_URL') || `${WEB_BASE_URL}/api`
);

export const APP_NAME = 'Rehab Summit Staff';

const isTestEnvironment = readEnv('NODE_ENV') === 'test';

export const isDevBuild = !isTestEnvironment && (
  typeof __DEV__ !== 'undefined'
    ? __DEV__
    : readEnv('NODE_ENV') !== 'production'
);
