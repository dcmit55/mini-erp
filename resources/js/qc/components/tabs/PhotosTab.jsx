import React, { useState, useRef } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { uploadPhoto, deletePhoto } from '../../api/photos';
import { Upload, Trash2, X, Image, Camera } from 'lucide-react';

const TYPE_OPTS = [
    { value: 'reject_log',     label: 'Reject Log' },
    { value: 'checklist_item', label: 'Checklist Item' },
    { value: 'packing_item',   label: 'Packing Item' },
    { value: 'daily_item',     label: 'Daily Progress Item' },
];

const SECTION_LABELS = {
    reject_log:     'Reject Logs',
    checklist_item: 'Checklist',
    packing_item:   'Packing',
    daily_item:     'Daily Progress',
};

const inputStyle = {
    width: '100%', border: '1px solid #e2e8f0', borderRadius: 8,
    padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box', background: '#fff',
};

const btnStyle = (bg, color = '#fff') => ({
    padding: '6px 12px', borderRadius: 8, border: 'none', cursor: 'pointer',
    background: bg, color, fontSize: 12, fontWeight: 600, outline: 'none',
    display: 'inline-flex', alignItems: 'center', gap: 5,
});

function collectPhotos(project) {
    const all = [];
    (project.reject_logs ?? []).forEach(rl =>
        (rl.photos ?? []).forEach(ph =>
            all.push({ ...ph, section: 'reject_log', ownerLabel: rl.item_name })
        )
    );
    (project.checklist_items ?? []).forEach(ci =>
        (ci.photos ?? []).forEach(ph =>
            all.push({ ...ph, section: 'checklist_item', ownerLabel: `Item #${ci.item_id}` })
        )
    );
    (project.packing_items ?? []).forEach(pi =>
        (pi.photos ?? []).forEach(ph =>
            all.push({ ...ph, section: 'packing_item', ownerLabel: pi.name })
        )
    );
    (project.daily_progress ?? []).forEach(dp =>
        (dp.items ?? []).forEach(di =>
            (di.photos ?? []).forEach(ph =>
                all.push({ ...ph, section: 'daily_item', ownerLabel: `${dp.date} · ${di.item_id}` })
            )
        )
    );
    return all;
}

function getItemsForType(project, type) {
    switch (type) {
        case 'reject_log':
            return (project.reject_logs ?? []).map(rl => ({
                uid: rl.uid, label: `${rl.item_name} [${rl.rework_status}]`,
            }));
        case 'checklist_item':
            return (project.checklist_items ?? []).map(ci => ({
                uid: ci.uid, label: `Item #${ci.item_id} (${ci.status ?? 'pending'})`,
            }));
        case 'packing_item':
            return (project.packing_items ?? []).filter(pi => !pi.is_hidden).map(pi => ({
                uid: pi.uid, label: pi.name,
            }));
        case 'daily_item':
            return (project.daily_progress ?? []).flatMap(dp =>
                (dp.items ?? []).map(di => ({
                    uid: di.uid, label: `${dp.date} — ${di.item_id}`,
                }))
            );
        default:
            return [];
    }
}

// ── Upload Dialog ─────────────────────────────────────────────────────

function UploadDialog({ project, onClose }) {
    const qc = useQueryClient();
    const fileRef = useRef(null);
    const [type, setType] = useState('reject_log');
    const [itemUid, setItemUid] = useState('');
    const [context, setContext] = useState('');
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [err, setErr] = useState(null);

    const items = getItemsForType(project, type);

    const handleTypeChange = (v) => { setType(v); setItemUid(''); };

    const handleFile = (f) => {
        if (!f) return;
        setFile(f);
        const reader = new FileReader();
        reader.onload = e => setPreview(e.target.result);
        reader.readAsDataURL(f);
    };

    const mut = useMutation({
        mutationFn: () => uploadPhoto(file, type, itemUid, { context: context.trim() || undefined }),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['project', project.uid] }); onClose(); },
        onError: e => setErr(e.message),
    });

    const canSubmit = file && itemUid;

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.4)', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.18)', width: '100%', maxWidth: 440 }}>
                <div style={{ padding: '14px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b', display: 'flex', alignItems: 'center', gap: 6 }}>
                        <Camera size={15} style={{ color: '#6366f1' }} /> Upload Photo
                    </div>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 4, outline: 'none', display: 'flex' }}>
                        <X size={16} />
                    </button>
                </div>

                <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 12 }}>
                    {err && (
                        <div style={{ fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 10px' }}>{err}</div>
                    )}

                    {/* File picker */}
                    <div
                        onClick={() => fileRef.current?.click()}
                        style={{ border: '2px dashed #e2e8f0', borderRadius: 12, padding: 20, cursor: 'pointer', textAlign: 'center', background: '#f8fafc', transition: 'border-color .15s' }}
                        onMouseEnter={e => e.currentTarget.style.borderColor = '#6366f1'}
                        onMouseLeave={e => e.currentTarget.style.borderColor = '#e2e8f0'}>
                        {preview ? (
                            <img src={preview} alt="preview"
                                style={{ maxHeight: 140, maxWidth: '100%', borderRadius: 8, objectFit: 'cover' }} />
                        ) : (
                            <>
                                <Camera size={28} style={{ color: '#94a3b8', marginBottom: 6 }} />
                                <div style={{ fontSize: 13, color: '#64748b' }}>Click to select photo</div>
                                <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>JPG, PNG — max 5 MB</div>
                            </>
                        )}
                        <input ref={fileRef} type="file" accept="image/*" style={{ display: 'none' }}
                            onChange={e => handleFile(e.target.files?.[0])} />
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Attach To *</div>
                        <select style={inputStyle} value={type} onChange={e => handleTypeChange(e.target.value)}>
                            {TYPE_OPTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Select Item *</div>
                        <select style={inputStyle} value={itemUid} onChange={e => setItemUid(e.target.value)}>
                            <option value="">Select…</option>
                            {items.map(i => <option key={i.uid} value={i.uid}>{i.label}</option>)}
                        </select>
                        {items.length === 0 && (
                            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 4 }}>No items available for this type.</div>
                        )}
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Context (optional)</div>
                        <input type="text" style={inputStyle} placeholder="e.g. Before repair, After finish…"
                            value={context} onChange={e => setContext(e.target.value)} />
                    </div>
                </div>

                <div style={{ padding: '12px 18px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                    <button onClick={onClose} style={btnStyle('#f1f5f9', '#475569')}>Cancel</button>
                    <button onClick={() => mut.mutate()} disabled={!canSubmit || mut.isPending}
                        style={{ ...btnStyle('#6366f1'), opacity: (!canSubmit || mut.isPending) ? 0.5 : 1 }}>
                        <Upload size={13} /> {mut.isPending ? 'Uploading…' : 'Upload'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Photo Card ────────────────────────────────────────────────────────

function PhotoCard({ photo, projectUid }) {
    const qc = useQueryClient();
    const [hover, setHover] = useState(false);
    const [lightbox, setLightbox] = useState(false);

    const delMut = useMutation({
        mutationFn: () => deletePhoto(photo.uid),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['project', projectUid] }),
    });

    return (
        <>
            <div
                style={{ position: 'relative', borderRadius: 10, overflow: 'hidden', background: '#f1f5f9', aspectRatio: '1', cursor: 'pointer' }}
                onMouseEnter={() => setHover(true)}
                onMouseLeave={() => setHover(false)}>
                <img src={photo.url} alt={photo.ownerLabel}
                    onClick={() => setLightbox(true)}
                    style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }} />
                {hover && (
                    <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,.52)', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 6, padding: 6 }}>
                        <div style={{ fontSize: 11, color: '#fff', textAlign: 'center', lineHeight: 1.3, overflow: 'hidden', textOverflow: 'ellipsis', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical' }}>
                            {photo.ownerLabel}
                        </div>
                        {photo.context && (
                            <div style={{ fontSize: 10, color: '#cbd5e1', textAlign: 'center' }}>{photo.context}</div>
                        )}
                        <button onClick={e => { e.stopPropagation(); delMut.mutate(); }} disabled={delMut.isPending}
                            style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '4px 10px', borderRadius: 6, border: 'none', cursor: 'pointer', background: '#ef4444', color: '#fff', fontSize: 11, fontWeight: 600, outline: 'none', marginTop: 2 }}>
                            <Trash2 size={11} /> {delMut.isPending ? '…' : 'Delete'}
                        </button>
                    </div>
                )}
            </div>

            {/* Lightbox */}
            {lightbox && (
                <div onClick={() => setLightbox(false)}
                    style={{ position: 'fixed', inset: 0, zIndex: 99999, background: 'rgba(0,0,0,.85)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
                    <img src={photo.url} alt={photo.ownerLabel}
                        style={{ maxWidth: '100%', maxHeight: '90vh', borderRadius: 10, objectFit: 'contain', boxShadow: '0 20px 60px rgba(0,0,0,.5)' }} />
                    <button onClick={() => setLightbox(false)}
                        style={{ position: 'absolute', top: 16, right: 16, background: 'rgba(255,255,255,.15)', border: 'none', borderRadius: 8, padding: 8, cursor: 'pointer', color: '#fff', display: 'flex', outline: 'none' }}>
                        <X size={20} />
                    </button>
                </div>
            )}
        </>
    );
}

// ── Main ──────────────────────────────────────────────────────────────

export default function PhotosTab({ project }) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const allPhotos = collectPhotos(project);

    const sections = Object.entries(SECTION_LABELS)
        .map(([key, label]) => ({ key, label, photos: allPhotos.filter(p => p.section === key) }))
        .filter(s => s.photos.length > 0);

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Header */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <span style={{ fontSize: 12, color: '#94a3b8' }}>
                    {allPhotos.length} photo{allPhotos.length !== 1 ? 's' : ''}
                </span>
                <button onClick={() => setUploadOpen(true)} style={btnStyle('#6366f1')}>
                    <Upload size={14} /> Upload Photo
                </button>
            </div>

            {/* Empty state */}
            {allPhotos.length === 0 && (
                <div style={{ textAlign: 'center', color: '#94a3b8', padding: '60px 0', fontSize: 13 }}>
                    <Image size={32} style={{ color: '#cbd5e1', marginBottom: 10 }} />
                    <div>No photos yet.</div>
                    <div style={{ fontSize: 11, marginTop: 4 }}>Upload photos to document QC results.</div>
                </div>
            )}

            {/* Grouped gallery */}
            {sections.map(sec => (
                <div key={sec.key}>
                    <div style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 8 }}>
                        {sec.label} ({sec.photos.length})
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 8 }}>
                        {sec.photos.map(ph => (
                            <PhotoCard key={ph.uid} photo={ph} projectUid={project.uid} />
                        ))}
                    </div>
                </div>
            ))}

            {uploadOpen && <UploadDialog project={project} onClose={() => setUploadOpen(false)} />}
        </div>
    );
}
