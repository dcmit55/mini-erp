import client from './client';

export const getRejectLogs = (projectUid) =>
    client.get(`/projects/${projectUid}/reject-logs`).then(r => r.data);

export const createRejectLog = (projectUid, data) =>
    client.post(`/projects/${projectUid}/reject-logs`, data).then(r => r.data);

export const updateRejectLog = (logUid, data) =>
    client.put(`/reject-logs/${logUid}`, data).then(r => r.data);
