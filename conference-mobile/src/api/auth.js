import { Platform } from 'react-native';
import apiClient, { handleApiCall } from './http';

export const login = async (email, password) => {
    const normalizedEmail = (email || '').trim().toLowerCase();
    return handleApiCall(() => apiClient.post('/login', {
        email: normalizedEmail,
        password,
        device_name: `Rehab Staff ${Platform.OS} ${String(Platform.Version)}`
    }));
};

export const getCurrentUser = async () => {
    return handleApiCall(() => apiClient.get('/user'));
};

export const logout = async () => {
    return handleApiCall(() => apiClient.post('/logout'));
};
