import client from './client';

export const getDailyProgress = (projectUid, date) =>
    client.get(`/projects/${projectUid}/daily/${date}`).then(r => r.data);

export const upsertDailyProgress = (projectUid, date, data) =>
    client.put(`/projects/${projectUid}/daily/${date}`, data).then(r => r.data);

export const updateDailyItem = (projectUid, date, itemId, data) =>
    client.put(`/projects/${projectUid}/daily/${date}/items/${itemId}`, data).then(r => r.data);

export const finalizeDailyItem = (projectUid, date, itemId, data) =>
    client.post(`/projects/${projectUid}/daily/${date}/items/${itemId}/finalize`, data).then(r => r.data);
