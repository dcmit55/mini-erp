import client from './client';

export const updatePackingItem = (projectUid, itemUid, data) =>
    client.put(`/projects/${projectUid}/packing/${itemUid}`, data).then(r => r.data);

export const addCustomPackingItem = (projectUid, data) =>
    client.post(`/projects/${projectUid}/packing/custom`, data).then(r => r.data);

export const deletePackingItem = (projectUid, itemUid) =>
    client.delete(`/projects/${projectUid}/packing/${itemUid}`).then(r => r.data);

export const verifyPacking = (projectUid, verified) =>
    client.post(`/projects/${projectUid}/packing/verify`, { verified }).then(r => r.data);
