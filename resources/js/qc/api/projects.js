import client from './client';

export const getAvailableJobOrders = (context = 'mascot') =>
    client.get(`/job-orders/available?context=${context}`).then(r => r.data);

export const getProjects = () =>
    client.get('/projects').then(r => r.data);

export const getProject = (uid) =>
    client.get(`/projects/${uid}`).then(r => r.data);

export const createProject = (data) =>
    client.post('/projects', data).then(r => r.data);

export const deleteProject = (uid) =>
    client.delete(`/projects/${uid}`).then(r => r.data);

export const submitFinalDecision = (uid, data) =>
    client.post(`/projects/${uid}/final-decision`, data).then(r => r.data);

export const addCustomPart = (uid, part) =>
    client.patch(`/projects/${uid}/custom-parts`, { part }).then(r => r.data);
