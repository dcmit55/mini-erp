import React, { useMemo, useState } from 'react';
import { useMQ } from '../MascotQC';
import { SECTIONS, DAILY_SECTIONS, fmtDate } from '../constants/mascotConstants';

/* ── collect all photos from project into flat groups ── */
function collectGroups(p) {
    const groups = [];

    /* 1. Daily progress */
    const dailyByDate = {};
    Object.entries(p.dailyProgress || {}).forEach(([date, dpEntry]) => {
        const photos = [];
        Object.entries(dpEntry.items || {}).forEach(([itemId, item]) => {
            const itemLabel = DAILY_SECTIONS.flatMap(s => s.items).find(i => i.id === itemId)?.l || itemId;

            /* part photos */
            Object.entries(item.partData || {}).forEach(([pk, partVal]) => {
                const [op, part] = pk.split('::');
                (partVal.photos || []).forEach((url, idx) => {
                    photos.push({
                        url,
                        label: `${itemLabel} › ${op} › ${part}`,
                        deleteAt: { type: 'daily_part', date, itemId, partKey: pk, idx },
                    });
                });
            });

            /* finalize photos */
            (item.finalizePhotos || []).forEach((url, idx) => {
                photos.push({
                    url,
                    label: `${itemLabel} [finalize]`,
                    deleteAt: { type: 'daily_finalize', date, itemId, idx },
                });
            });
        });
        if (photos.length) {
            if (!dailyByDate[date]) dailyByDate[date] = [];
            dailyByDate[date].push(...photos);
        }
    });
    Object.entries(dailyByDate)
        .sort(([a], [b]) => b.localeCompare(a))
        .forEach(([date, photos]) => {
            groups.push({ id: `daily_${date}`, title: `📋 Daily Progress — ${fmtDate(date)}`, photos });
        });

    /* 2. Finishing checklist */
    const clPhotos = [];
    SECTIONS.forEach(sec => {
        sec.items.forEach(item => {
            const cl = p.checklistItems?.[item.id];
            (cl?.photos || []).forEach((url, idx) => {
                clPhotos.push({
                    url,
                    label: item.l,
                    deleteAt: { type: 'checklist', itemId: item.id, idx },
                });
            });
        });
    });
    if (clPhotos.length) groups.push({ id: 'checklist', title: '✅ Finishing Checklist', photos: clPhotos });

    /* 3. Defect evidence */
    const defPhotos = [];
    (p.rejectLog || []).forEach(log => {
        (log.photos || []).forEach((url, idx) => {
            defPhotos.push({
                url,
                label: log.itemName || '—',
                deleteAt: { type: 'defect', logId: log.id, idx },
            });
        });
    });
    if (defPhotos.length) groups.push({ id: 'defects', title: '🐛 Defect Evidence', photos: defPhotos });

    /* 4. Packing */
    const packPhotos = [];
    Object.entries(p.packingItems || {}).forEach(([name, val]) => {
        (val.photos || []).forEach((url, idx) => {
            packPhotos.push({
                url,
                label: name,
                deleteAt: { type: 'packing', itemName: name, idx },
            });
        });
    });
    (p.packingVerifyPhotos || []).forEach((url, idx) => {
        packPhotos.push({
            url,
            label: 'Packing Verification',
            deleteAt: { type: 'packing_verify', idx },
        });
    });
    if (packPhotos.length) groups.push({ id: 'packing', title: '📦 Packing', photos: packPhotos });

    return groups;
}

/* ── apply delete to project ── */
function applyDelete(proj, d) {
    if (d.type === 'checklist') {
        const it = proj.checklistItems?.[d.itemId];
        if (it?.photos) it.photos = it.photos.filter((_, i) => i !== d.idx);
    } else if (d.type === 'daily_part') {
        const it = proj.dailyProgress?.[d.date]?.items?.[d.itemId];
        const pd = it?.partData?.[d.partKey];
        if (pd?.photos) pd.photos = pd.photos.filter((_, i) => i !== d.idx);
    } else if (d.type === 'daily_finalize') {
        const it = proj.dailyProgress?.[d.date]?.items?.[d.itemId];
        if (it?.finalizePhotos) it.finalizePhotos = it.finalizePhotos.filter((_, i) => i !== d.idx);
    } else if (d.type === 'defect') {
        const log = proj.rejectLog?.find(l => l.id === d.logId);
        if (log?.photos) log.photos = log.photos.filter((_, i) => i !== d.idx);
    } else if (d.type === 'packing') {
        const it = proj.packingItems?.[d.itemName];
        if (it?.photos) it.photos = it.photos.filter((_, i) => i !== d.idx);
    } else if (d.type === 'packing_verify') {
        if (proj.packingVerifyPhotos) proj.packingVerifyPhotos = proj.packingVerifyPhotos.filter((_, i) => i !== d.idx);
    }
}

/* ── main component ── */
export default function GalleryTab({ project: p }) {
    const { updateProject, setPhoto } = useMQ();
    const [confirmDel, setConfirmDel] = useState(null); // deleteAt object pending confirm
    const [filter, setFilter] = useState('all');

    const groups = useMemo(() => collectGroups(p), [p]);
    const totalCount = groups.reduce((s, g) => s + g.photos.length, 0);

    const doDelete = (deleteAt) => {
        updateProject(p.id, proj => applyDelete(proj, deleteAt));
        setConfirmDel(null);
    };

    const openLightbox = (allUrls, startIdx) => {
        setPhoto({ urls: allUrls, idx: startIdx });
    };

    if (totalCount === 0) {
        return (
            <div style={{ textAlign: 'center', padding: '60px 20px', color: '#94a3b8' }}>
                <div style={{ fontSize: 40, marginBottom: 12 }}>📷</div>
                <div style={{ fontSize: 14, fontWeight: 600 }}>No photos yet</div>
                <div style={{ fontSize: 12, marginTop: 4 }}>Upload photos in Daily Progress or Finishing Checklist to see them here.</div>
            </div>
        );
    }

    /* filter tabs */
    const filterOpts = [
        { id: 'all', label: `All (${totalCount})` },
        ...groups.map(g => ({ id: g.id, label: `${g.title.split(' ')[0]} ${g.photos.length}` })),
    ];
    const visibleGroups = filter === 'all' ? groups : groups.filter(g => g.id === filter);

    return (
        <div>
            {/* filter bar */}
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: 16 }}>
                {filterOpts.map(f => (
                    <button key={f.id}
                        onClick={() => setFilter(f.id)}
                        className={`mq-tab-pill${filter === f.id ? ' active' : ''}`}
                        style={{ fontSize: 11 }}>
                        {f.label}
                    </button>
                ))}
            </div>

            {visibleGroups.map(group => {
                const allUrls = group.photos.map(ph => ph.url);
                return (
                    <div key={group.id} style={{ marginBottom: 24 }}>
                        <div style={{ fontSize: 12, fontWeight: 700, color: '#475569', marginBottom: 10, paddingBottom: 6, borderBottom: '1px solid #e2e8f0' }}>
                            {group.title}
                            <span style={{ marginLeft: 6, fontWeight: 400, color: '#94a3b8' }}>{group.photos.length} foto</span>
                        </div>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
                            {group.photos.map((ph, i) => (
                                <PhotoCard
                                    key={`${ph.deleteAt.type}_${i}`}
                                    url={ph.url}
                                    label={ph.label}
                                    onView={() => openLightbox(allUrls, i)}
                                    onDelete={() => setConfirmDel(ph.deleteAt)}
                                />
                            ))}
                        </div>
                    </div>
                );
            })}

            {/* confirm delete dialog */}
            {confirmDel && (
                <div className="mq-overlay" onClick={() => setConfirmDel(null)}>
                    <div className="mq-mbox" style={{ maxWidth: 340 }} onClick={e => e.stopPropagation()}>
                        <div className="mq-mbox-hd">
                            <span>Hapus Foto?</span>
                            <button className="mq-close" onClick={() => setConfirmDel(null)}>✕</button>
                        </div>
                        <p style={{ fontSize: 13, color: '#475569', margin: '0 0 16px' }}>
                            Foto ini akan dihapus permanen dari data proyek. Tindakan ini tidak bisa dibatalkan.
                        </p>
                        <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
                            <button className="mq-btn-ghost" onClick={() => setConfirmDel(null)}>Batal</button>
                            <button
                                onClick={() => doDelete(confirmDel)}
                                style={{ padding: '7px 16px', borderRadius: 8, border: 'none', background: '#ef4444', color: '#fff', fontWeight: 700, fontSize: 13, cursor: 'pointer' }}>
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

function PhotoCard({ url, label, onView, onDelete }) {
    return (
        <div style={{ position: 'relative', flexShrink: 0 }}>
            <img
                src={url}
                alt=""
                onClick={onView}
                style={{ width: 90, height: 90, objectFit: 'cover', borderRadius: 8, border: '1px solid #e2e8f0', cursor: 'zoom-in', display: 'block' }}
            />
            {/* delete button */}
            <button
                onClick={onDelete}
                title="Hapus foto"
                style={{
                    position: 'absolute', top: -6, right: -6,
                    width: 20, height: 20, borderRadius: '50%',
                    background: '#ef4444', color: '#fff', border: '2px solid #fff',
                    cursor: 'pointer', fontSize: 11, fontWeight: 700,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    lineHeight: 1,
                }}>×</button>
            {/* label tooltip on hover */}
            <div style={{
                position: 'absolute', bottom: 0, left: 0, right: 0,
                background: 'rgba(0,0,0,.55)', color: '#fff',
                fontSize: 9, padding: '3px 4px', borderRadius: '0 0 8px 8px',
                overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap',
            }}>{label}</div>
        </div>
    );
}
