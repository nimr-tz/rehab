import apiClient, { handleApiCall } from './http';

export const getSessions = async () => {
    return handleApiCall(() => apiClient.get('/sessions'));
};
