import React, { useState, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getStageGallery } from '../../../api/stageProduction';
import { uploadPhoto, deletePhoto } from '../../../api/photos';
import { STAGE_COLORS } from '../../../data/models';
import { Image, Camera, Upload, X, ZoomIn, Trash2, AlertCircle } from 'lucide-react';

// ── Lightbox ──────────────────────────────────────────────────────────

function Lightbox({ photo, onClose, onDelete, deleting }) {
    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, background: 'rgba(0,0,0,.88)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }}
            onClick={onClose}>
            <div style={{ position: 'relative', maxWidth: 860, width: '100%', display: 'flex', flexDirection: 'column', gap: 12 }}
                onClick={e => e.stopPropagation()}>
                {/* Close */}
                <button onClick={onClose} style={{ position: 'absolute', top: -36, right: 0, background: 'rgba(255,255,255,.15)', border: 'none', borderRadius: 8, padding: '4px 10px', cursor: 'pointer', color: '#fff', outline: 'none', display: 'flex', alignItems: 'center', gap: 4, fontSize: 12 }}>
                    <X size={14} /> Close
                </button>

                {/* Image */}
                <img src={photo.url} alt=""
                    style={{ width: '100%', maxHeight: '70vh', objectFit: 'contain', borderRadius: 12 }} />

                {/* Metadata */}
                <div style={{ background: 'rgba(255,255,255,.1)', borderRadius: 10, padding: '10px 14px', color: '#e2e8f0', fontSize: 12, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', gap: 16 }}>
                        {photo.context && <span><strong>Context:</strong> {photo.context}</span>}
                        {photo.created_at && <span><strong>Date:</strong> {new Date(photo.created_at).toLocaleDateString()}</span>}
                        {photo.meta?.operator && <span><strong>By:</strong> {photo.meta.operator}</span>}
                    </div>
                    <button onClick={() => onDelete(photo.uid)} disabled={deleting}
                        style={{ display: 'flex', alignItems: 'center', gap: 5, padding: '4px 10px', borderRadius: 7, border: '1px solid rgba(239,68,68,.5)', background: 'rgba(239,68,68,.15)', color: '#fca5a5', cursor: 'pointer', fontSize: 12, outline: 'none', opacity: deleting ? 0.5 : 1 }}>
                        <Trash2 size={13} /> {deleting ? 'Deleting…' : 'Delete'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Drop Zone ─────────────────────────────────────────────────────────

function DropZone({ onFile }) {
    const [dragging, setDragging] = useState(false);
    const inputRef = useRef(null);
    const camRef   = useRef(null);

    const handleDrop = (e) => {
        e.preventDefault();
        setDragging(false);
        const file = e.dataTransfer.files[0];
        if (file?.type.startsWith('image/')) onFile(file);
    };

    return (
        <div
            onDragOver={e => { e.preventDefault(); setDragging(true); }}
            onDragLeave={() => setDragging(false)}
            onDrop={handleDrop}
            style={{ border: `2px dashed ${dragging ? '#6366f1' : '#e2e8f0'}`, borderRadius: 14, padding: '28px 20px', textAlign: 'center', background: dragging ? '#f0f0ff' : '#fafafa', transition: 'all .15s', cursor: 'pointer' }}
            onClick={() => inputRef.current?.click()}>
            <Upload size={28} color={dragging ? '#6366f1' : '#cbd5e1'} style={{ margin: '0 auto 10px' }} />
            <div style={{ fontSize: 13, color: '#64748b', fontWeight: 500 }}>Drag & drop photo here, or click to browse</div>
            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 6 }}>JPG, PNG, WEBP accepted</div>

            {/* Buttons */}
            <div style={{ display: 'flex', gap: 10, justifyContent: 'center', marginTop: 14 }}>
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 14px', borderRadius: 8, background: '#6366f1', color: '#fff', fontSize: 12, fontWeight: 600, cursor: 'pointer', outline: 'none' }}
                    onClick={e => e.stopPropagation()}>
                    <Image size={14} /> Browse File
                    <input ref={inputRef} type="file" accept="image/*" style={{ display: 'none' }}
                        onChange={e => { if (e.target.files[0]) onFile(e.target.files[0]); }} />
                </label>
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 14px', borderRadius: 8, background: '#0f172a', color: '#fff', fontSize: 12, fontWeight: 600, cursor: 'pointer', outline: 'none' }}
                    onClick={e => e.stopPropagation()}>
                    <Camera size={14} /> Camera
                    {/* capture="environment" = rear camera on mobile */}
                    <input ref={camRef} type="file" accept="image/*" capture="environment" style={{ display: 'none' }}
                        onChange={e => { if (e.target.files[0]) onFile(e.target.files[0]); }} />
                </label>
            </div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────

export default function GalleryTab({ projectUid, stage }) {
    const qc    = useQueryClient();
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const { data: photos = [], isLoading } = useQuery({
        queryKey: ['stage-gallery', projectUid, stage],
        queryFn: () => getStageGallery(projectUid, stage),
        staleTime: 30_000,
    });

    const [lightbox, setLightbox] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [uploadErr, setUploadErr] = useState(null);
    const [deletingUid, setDeletingUid] = useState(null);

    const handleUpload = async (file) => {
        setUploading(true);
        setUploadErr(null);
        try {
            await uploadPhoto(file, 'daily_item', null, { context: `${stage}_gallery`, meta: JSON.stringify({ stage }) });
            qc.invalidateQueries({ queryKey: ['stage-gallery', projectUid, stage] });
        } catch (e) {
            setUploadErr('Upload failed. Please try again.');
        }
        setUploading(false);
    };

    const delMut = useMutation({
        mutationFn: deletePhoto,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['stage-gallery', projectUid, stage] });
            setLightbox(null);
        },
    });

    if (isLoading) return <div style={{ padding: '40px 0', textAlign: 'center', color: '#94a3b8' }}>Loading…</div>;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Upload area */}
            <DropZone onFile={handleUpload} />
            {uploading && <div style={{ fontSize: 12, color: '#6366f1', textAlign: 'center' }}>Uploading…</div>}
            {uploadErr && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '7px 10px' }}>
                    <AlertCircle size={13} /> {uploadErr}
                </div>
            )}

            {/* Photo count */}
            {photos.length > 0 && (
                <div style={{ fontSize: 11, color: '#94a3b8' }}>{photos.length} photo{photos.length !== 1 ? 's' : ''}</div>
            )}

            {/* Grid — 3-column */}
            {photos.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '32px 0', color: '#cbd5e1', fontSize: 13 }}>
                    No photos yet. Upload or capture above.
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 8 }}>
                    {photos.map(p => (
                        <div key={p.uid} style={{ position: 'relative', borderRadius: 10, overflow: 'hidden', background: '#f1f5f9', cursor: 'pointer', aspectRatio: '1' }}
                            onClick={() => setLightbox(p)}>
                            <img src={p.url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }} />
                            <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,0)', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'background .15s' }}
                                onMouseEnter={e => e.currentTarget.style.background = 'rgba(0,0,0,.35)'}
                                onMouseLeave={e => e.currentTarget.style.background = 'rgba(0,0,0,0)'}>
                                <ZoomIn size={22} color="#fff" style={{ opacity: 0, transition: 'opacity .15s' }}
                                    ref={el => { if (el) { const parent = el.parentElement; parent.onmouseenter = () => { el.style.opacity = 1; }; parent.onmouseleave = () => { el.style.opacity = 0; }; } }} />
                            </div>
                            {p.context && (
                                <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, background: 'rgba(0,0,0,.5)', color: '#fff', fontSize: 9, padding: '3px 6px', textTransform: 'uppercase', letterSpacing: '.04em' }}>
                                    {p.context}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {lightbox && (
                <Lightbox
                    photo={lightbox}
                    onClose={() => setLightbox(null)}
                    onDelete={uid => { setDeletingUid(uid); delMut.mutate(uid); }}
                    deleting={delMut.isPending}
                />
            )}
        </div>
    );
}
