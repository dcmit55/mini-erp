import client from './client';

/**
 * Upload a photo.
 * @param {File} file
 * @param {'checklist_item'|'packing_item'|'reject_log'|'daily_item'} photoableType
 * @param {string} photoableUid
 * @param {object} opts  - { context, meta }
 */
export const uploadPhoto = (file, photoableType, photoableUid, opts = {}) => {
    const form = new FormData();
    form.append('photo', file);
    form.append('photoable_type', photoableType);
    form.append('photoable_uid', photoableUid);
    if (opts.context) form.append('context', opts.context);
    if (opts.meta)    form.append('meta', opts.meta);

    return client.post('/photos', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }).then(r => r.data);
};

export const deletePhoto = (uid) =>
    client.delete(`/photos/${uid}`).then(r => r.data);
