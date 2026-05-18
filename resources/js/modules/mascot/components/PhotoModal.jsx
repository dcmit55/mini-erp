import React, { useState } from 'react';
import { useMQ } from '../MascotQC';

export default function PhotoModal() {
    const { _photo, setPhoto } = useMQ();
    const [idx, setIdx] = useState(_photo?.idx || 0);

    if (!_photo?.urls?.length) return null;
    const urls = _photo.urls;
    const cur  = idx < urls.length ? idx : 0;

    const prev = () => setIdx(i => (i - 1 + urls.length) % urls.length);
    const next = () => setIdx(i => (i + 1) % urls.length);

    return (
        <div className="mq-overlay" onClick={() => setPhoto(null)}
            style={{ background: 'rgba(0,0,0,.85)', alignItems: 'center', justifyContent: 'center', flexDirection: 'column', gap: 16 }}>

            <button
                onClick={e => { e.stopPropagation(); setPhoto(null); }}
                style={{
                    position: 'fixed', top: 16, right: 16,
                    background: 'rgba(255,255,255,.15)', border: 'none', borderRadius: '50%',
                    width: 36, height: 36, fontSize: 18, cursor: 'pointer', color: '#fff',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                ✕
            </button>

            <div onClick={e => e.stopPropagation()}
                style={{ display: 'flex', alignItems: 'center', gap: 16 }}>

                {urls.length > 1 && (
                    <button onClick={prev} style={navBtn}>‹</button>
                )}

                <img
                    src={urls[cur]}
                    alt={`photo ${cur + 1}`}
                    style={{
                        maxWidth: '80vw', maxHeight: '75vh',
                        objectFit: 'contain', borderRadius: 10,
                        boxShadow: '0 8px 32px rgba(0,0,0,.5)',
                    }}
                />

                {urls.length > 1 && (
                    <button onClick={next} style={navBtn}>›</button>
                )}
            </div>

            {urls.length > 1 && (
                <div onClick={e => e.stopPropagation()}
                    style={{ display: 'flex', gap: 6 }}>
                    {urls.map((url, i) => (
                        <img key={i} src={url} alt=""
                            onClick={() => setIdx(i)}
                            style={{
                                width: 44, height: 44, objectFit: 'cover', borderRadius: 6,
                                cursor: 'pointer',
                                border: `2px solid ${i === cur ? '#6366f1' : 'transparent'}`,
                                opacity: i === cur ? 1 : .55,
                                transition: 'opacity .15s, border-color .15s',
                            }} />
                    ))}
                </div>
            )}

            <div style={{ color: 'rgba(255,255,255,.5)', fontSize: 12 }}>
                {cur + 1} / {urls.length}
            </div>
        </div>
    );
}

const navBtn = {
    background: 'rgba(255,255,255,.15)',
    border: 'none', borderRadius: '50%',
    width: 44, height: 44,
    fontSize: 24, cursor: 'pointer', color: '#fff',
    display: 'flex', alignItems: 'center', justifyContent: 'center',
    flexShrink: 0,
};
