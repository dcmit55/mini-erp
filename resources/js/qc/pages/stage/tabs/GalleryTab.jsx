import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getStageGallery } from '../../../api/stageProduction';
import { deletePhoto } from '../../../api/photos';
import { STAGE_COLORS } from '../../../data/models';
import { X, ZoomIn, Trash2, Image } from 'lucide-react';

// ── Lightbox ──────────────────────────────────────────────────────────

function Lightbox({ photo, onClose, onDelete, deleting }) {
    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, background: 'rgba(0,0,0,.9)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}
            onClick={onClose}>
            <div style={{ position: 'relative', maxWidth: 800, width: '100%', display: 'flex', flexDirection: 'column', gap: 10 }}
                onClick={e => e.stopPropagation()}>

                {/* Close */}
                <button onClick={onClose} style={{ position: 'absolute', top: -40, right: 0, background: 'rgba(255,255,255,.15)', border: 'none', borderRadius: 8, padding: '4px 12px', cursor: 'pointer', color: '#fff', outline: 'none', display: 'flex', alignItems: 'center', gap: 5, fontSize: 12 }}>
                    <X size={13} /> Tutup
                </button>

                {/* Image */}
                <img src={photo.url} alt="" style={{ width: '100%', maxHeight: '72vh', objectFit: 'contain', borderRadius: 10 }} />

                {/* Footer bar */}
                <div style={{ background: 'rgba(255,255,255,.1)', borderRadius: 9, padding: '9px 14px', color: '#e2e8f0', fontSize: 12, display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                    <div style={{ display: 'flex', gap: 14, flexWrap: 'wrap' }}>
                        {photo.log_order && (
                            <span><strong style={{ color: '#c4b5fd' }}>Baris #{photo.log_order}</strong></span>
                        )}
                        {photo.log_name && (
                            <span>{photo.log_name}{photo.log_category ? ` — ${photo.log_category}` : ''}</span>
                        )}
                        {photo.created_at && (
                            <span style={{ color: '#94a3b8' }}>{new Date(photo.created_at).toLocaleDateString('id-ID')}</span>
                        )}
                    </div>
                    <button onClick={() => onDelete(photo.uid)} disabled={deleting}
                        style={{ flexShrink: 0, display: 'flex', alignItems: 'center', gap: 5, padding: '4px 10px', borderRadius: 7, border: '1px solid rgba(239,68,68,.5)', background: 'rgba(239,68,68,.15)', color: '#fca5a5', cursor: 'pointer', fontSize: 12, outline: 'none', opacity: deleting ? 0.5 : 1 }}>
                        <Trash2 size={12} /> {deleting ? 'Menghapus…' : 'Hapus'}
                    </button>
                </div>
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
        queryFn:  () => getStageGallery(projectUid, stage),
        staleTime: 30_000,
    });

    const [lightbox, setLightbox] = useState(null);

    const delMut = useMutation({
        mutationFn: deletePhoto,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['stage-gallery', projectUid, stage] });
            setLightbox(null);
        },
    });

    if (isLoading) {
        return (
            <div style={{ padding: '40px 0', textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>
                Memuat galeri…
            </div>
        );
    }

    if (photos.length === 0) {
        return (
            <div style={{ textAlign: 'center', padding: '56px 20px', color: '#cbd5e1' }}>
                <Image size={40} color="#e2e8f0" style={{ display: 'block', margin: '0 auto 12px' }} />
                <div style={{ fontSize: 13, fontWeight: 500 }}>Belum ada foto</div>
                <div style={{ fontSize: 11, marginTop: 4 }}>Upload foto via tab Inspection</div>
            </div>
        );
    }

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {/* Count */}
            <div style={{ fontSize: 11, color: '#94a3b8' }}>
                {photos.length} foto
            </div>

            {/* Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))', gap: 10 }}>
                {photos.map((p, i) => (
                    <div key={p.uid}
                        style={{ background: '#fff', borderRadius: 10, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)', cursor: 'pointer', transition: 'transform .15s, box-shadow .15s' }}
                        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,.13)'; }}
                        onMouseLeave={e => { e.currentTarget.style.transform = ''; e.currentTarget.style.boxShadow = '0 1px 4px rgba(0,0,0,.08)'; }}
                        onClick={() => setLightbox(p)}>

                        {/* Thumbnail */}
                        <div style={{ position: 'relative', aspectRatio: '1', background: '#f1f5f9', overflow: 'hidden' }}>
                            <img src={p.url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }} />
                            <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,0)', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'background .15s' }}
                                onMouseEnter={e => e.currentTarget.style.background = 'rgba(0,0,0,.3)'}
                                onMouseLeave={e => e.currentTarget.style.background = 'rgba(0,0,0,0)'}>
                                <ZoomIn size={18} color="#fff" style={{ opacity: 0, transition: 'opacity .15s' }}
                                    ref={el => { if (el) { const overlay = el.parentElement; overlay.onmouseenter = () => { el.style.opacity = 1; }; overlay.onmouseleave = () => { el.style.opacity = 0; }; } }} />
                            </div>
                        </div>

                        {/* Description */}
                        <div style={{ padding: '6px 8px' }}>
                            {p.log_order ? (
                                <>
                                    <div style={{ fontSize: 10, fontWeight: 700, color: color.text, marginBottom: 1 }}>
                                        #{p.log_order} · {p.log_name || '—'}
                                    </div>
                                    {p.log_category && (
                                        <div style={{ fontSize: 9, color: '#94a3b8', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                            {p.log_category}
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div style={{ fontSize: 10, color: '#94a3b8' }}>
                                    {p.context || 'Foto'}
                                </div>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {lightbox && (
                <Lightbox
                    photo={lightbox}
                    onClose={() => setLightbox(null)}
                    onDelete={uid => delMut.mutate(uid)}
                    deleting={delMut.isPending}
                />
            )}
        </div>
    );
}
