import client from './client';

export const updateChecklistItem = (projectUid, itemId, data) =>
    client.put(`/projects/${projectUid}/checklist/${itemId}`, data).then(r => r.data);
