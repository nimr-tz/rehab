import apiClient, { handleApiCall } from './http';

export const normalizeStaffQrToken = (qrValue) => {
    const value = String(qrValue || '').trim();
    const match = value.match(/\/badge\/([^/?#]+)/i);

    if (match) {
        return decodeURIComponent(match[1]);
    }

    return value;
};

// Staff Scanner API
export const lookupAttendeeByQR = async (qrToken) => {
    const token = encodeURIComponent(normalizeStaffQrToken(qrToken));
    return handleApiCall(() => apiClient.get(`/staff/lookup-qr/${token}`));
};

export const markAttendance = async (attendeeId, day, attendeeType = 'user') => {
    return handleApiCall(() => apiClient.post(`/staff/mark-attendance`, {
        attendee_id: attendeeId,
        attendee_type: attendeeType,
        day: day,
    }));
};

// Session-door scanning (CPD evidence)
export const recordSessionAttendance = async (sessionId, qrValue) => {
    return handleApiCall(() => apiClient.post(`/staff/sessions/${sessionId}/attendance`, {
        token: normalizeStaffQrToken(qrValue),
    }));
};

export const getSessionAttendanceSummary = async (sessionId) => {
    return handleApiCall(() => apiClient.get(`/staff/sessions/${sessionId}/attendance`));
};
