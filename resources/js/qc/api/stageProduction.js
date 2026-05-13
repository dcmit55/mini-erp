import client from './client';

const base = (uid, stage) => `/projects/${uid}/stages/${stage}`;

export const getStageRecords    = (uid, stage) => client.get(`${base(uid, stage)}/records`).then(r => r.data);
export const createStageRecord  = (uid, stage, data) => client.post(`${base(uid, stage)}/records`, data).then(r => r.data);
export const updateStageRecord  = (uid, stage, itemUid, data) => client.put(`${base(uid, stage)}/records/${itemUid}`, data).then(r => r.data);
export const inspectRecord      = (uid, stage, itemUid, data) => client.post(`${base(uid, stage)}/records/${itemUid}/inspect`, data).then(r => r.data);
export const getStageRejectLogs    = (uid, stage) => client.get(`${base(uid, stage)}/reject-logs`).then(r => r.data);
export const createStageRejectLog  = (uid, stage, data) => client.post(`${base(uid, stage)}/reject-logs`, data).then(r => r.data);
export const batchCreateRejectLogs = (uid, stage, rows) => client.post(`${base(uid, stage)}/reject-logs/batch`, { rows }).then(r => r.data);
export const updateStageRejectLog  = (uid, stage, logUid, data) => client.put(`${base(uid, stage)}/reject-logs/${logUid}`, data).then(r => r.data);
export const getStageGallery    = (uid, stage) => client.get(`${base(uid, stage)}/gallery`).then(r => r.data);
export const getStageHistory    = (uid, stage) => client.get(`${base(uid, stage)}/history`).then(r => r.data);
