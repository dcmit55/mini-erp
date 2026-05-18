import React, { useState } from 'react';
import { useMQ } from '../MascotQC';
import {
    PM_REQ_STRICT, PM_OPT_FLEX, PM_ITEMS, PI_ITEMS,
} from '../constants/mascotConstants';

export default function PackingListTab({ project: p }) {
    const { updateProject, setPhoto } = useMQ();
    const [newItem, setNewItem]   = useState('');
    const [newQty,  setNewQty]    = useState(1);

    const items   = p.packingItems    || {};
    const custom  = p.packingCustom   || [];
    const vphotos = p.packingVerifyPhotos || [];
    const stdList = p.mascotType === 'Mascot' ? PM_ITEMS : PI_ITEMS;
    const reqList = p.mascotType === 'Mascot' ? PM_REQ_STRICT : PI_ITEMS;

    /* toggle standard item */
    const toggle = (name) => {
        updateProject(p.id, proj => {
            if (!proj.packingItems[name]) proj.packingItems[name] = { checked: false, photos: [] };
            proj.packingItems[name].checked = !proj.packingItems[name].checked;
        });
    };

    /* add photo to a packing item */
    const addItemPhoto = (name, url) => {
        updateProject(p.id, proj => {
            if (!proj.packingItems[name]) proj.packingItems[name] = { checked: false, photos: [] };
            proj.packingItems[name].photos = [...(proj.packingItems[name].photos || []), url];
        });
    };

    /* add custom item */
    const addCustom = () => {
        if (!newItem.trim()) return;
        updateProject(p.id, proj => {
            proj.packingCustom = [...(proj.packingCustom || []), { name: newItem.trim(), qty: newQty }];
            proj.packingItems[newItem.trim()] = { checked: false, photos: [] };
        });
        setNewItem('');
        setNewQty(1);
    };

    /* remove custom item */
    const removeCustom = (name) => {
        updateProject(p.id, proj => {
            proj.packingCustom = (proj.packingCustom || []).filter(c => c.name !== name);
            delete proj.packingItems[name];
        });
    };

    /* toggle custom item */
    const toggleCustom = (name) => {
        updateProject(p.id, proj => {
            if (!proj.packingItems[name]) proj.packingItems[name] = { checked: false, photos: [] };
            proj.packingItems[name].checked = !proj.packingItems[name].checked;
        });
    };

    /* add verification photo */
    const addVerifyPhoto = (url) => {
        updateProject(p.id, proj => {
            proj.packingVerifyPhotos = [...(proj.packingVerifyPhotos || []), url];
        });
    };

    /* toggle packing verified */
    const toggleVerified = () => {
        updateProject(p.id, proj => {
            proj.packingVerified = !proj.packingVerified;
        });
    };

    const checkedReq = reqList.filter(n => items[n]?.checked).length;
    const checkedAll = [...stdList, ...custom.map(c => c.name)].filter(n => items[n]?.checked).length;
    const totalAll   = stdList.length + custom.length;

    return (
        <div>
            {/* summary */}
            <div className="mq-cl-summary" style={{ marginBottom: 16 }}>
                <span>Required: <b style={{ color: checkedReq === reqList.length ? '#10b981' : '#f59e0b' }}>{checkedReq}/{reqList.length}</b></span>
                <span>Total: <b>{checkedAll}/{totalAll}</b></span>
                {p.packingVerified && <span style={{ color: '#10b981', fontWeight: 700 }}>✓ VERIFIED</span>}
            </div>

            {/* required items */}
            <div className="mq-card" style={{ marginBottom: 12 }}>
                <div className="mq-card-title" style={{ marginBottom: 10 }}>Required Items</div>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {reqList.map(name => (
                        <PackingItemRow
                            key={name}
                            name={name}
                            checked={items[name]?.checked || false}
                            photos={items[name]?.photos || []}
                            required
                            onToggle={() => toggle(name)}
                            onAddPhoto={url => addItemPhoto(name, url)}
                            onViewPhotos={idx => setPhoto({ urls: items[name]?.photos || [], idx })}
                        />
                    ))}
                </div>
            </div>

            {/* optional items (Mascot only) */}
            {p.mascotType === 'Mascot' && PM_OPT_FLEX.length > 0 && (
                <div className="mq-card" style={{ marginBottom: 12 }}>
                    <div className="mq-card-title" style={{ marginBottom: 10 }}>Optional Items</div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                        {PM_OPT_FLEX.map(name => (
                            <PackingItemRow
                                key={name}
                                name={name}
                                checked={items[name]?.checked || false}
                                photos={items[name]?.photos || []}
                                onToggle={() => toggle(name)}
                                onAddPhoto={url => addItemPhoto(name, url)}
                                onViewPhotos={idx => setPhoto({ urls: items[name]?.photos || [], idx })}
                            />
                        ))}
                    </div>
                </div>
            )}

            {/* custom items */}
            <div className="mq-card" style={{ marginBottom: 12 }}>
                <div className="mq-card-title" style={{ marginBottom: 10 }}>Custom Items</div>

                {custom.length === 0 && (
                    <div style={{ fontSize: 13, color: '#94a3b8', marginBottom: 10 }}>No custom items yet</div>
                )}

                <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginBottom: 12 }}>
                    {custom.map(c => (
                        <div key={c.name} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                            <PackingItemRow
                                name={c.name}
                                checked={items[c.name]?.checked || false}
                                photos={items[c.name]?.photos || []}
                                onToggle={() => toggleCustom(c.name)}
                                onAddPhoto={url => addItemPhoto(c.name, url)}
                                onViewPhotos={idx => setPhoto({ urls: items[c.name]?.photos || [], idx })}
                                style={{ flex: 1 }}
                            />
                            <button className="mq-btn-ghost" style={{ fontSize: 12, color: '#ef4444', padding: '4px 8px' }}
                                onClick={() => removeCustom(c.name)}>✕</button>
                        </div>
                    ))}
                </div>

                <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    <input className="mq-input" style={{ flex: 1 }}
                        placeholder="Item name…"
                        value={newItem}
                        onChange={e => setNewItem(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && addCustom()} />
                    <input className="mq-input" type="number" min={1}
                        style={{ width: 60 }}
                        value={newQty}
                        onChange={e => setNewQty(Number(e.target.value))} />
                    <button className="mq-btn-primary" onClick={addCustom}>Add</button>
                </div>
            </div>

            {/* verification photos */}
            <div className="mq-card" style={{ marginBottom: 12 }}>
                <div className="mq-card-title" style={{ marginBottom: 10 }}>Verification Photos</div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center', marginBottom: 12 }}>
                    {vphotos.map((url, i) => (
                        <img key={i} src={url} alt=""
                            onClick={() => setPhoto({ urls: vphotos, idx: i })}
                            style={{ width: 64, height: 64, objectFit: 'cover', borderRadius: 8, cursor: 'pointer', border: '1px solid #e2e8f0' }} />
                    ))}
                    <label style={{ width: 64, height: 64, border: '1.5px dashed #cbd5e1', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 22, color: '#94a3b8', flexDirection: 'column' }}>
                        📷
                        <span style={{ fontSize: 10, color: '#94a3b8' }}>Add</span>
                        <input type="file" accept="image/*" hidden onChange={(e) => {
                            const f = e.target.files[0]; if (!f) return;
                            const r = new FileReader();
                            r.onload = ev => addVerifyPhoto(ev.target.result);
                            r.readAsDataURL(f);
                            e.target.value = '';
                        }} />
                    </label>
                </div>

                <button
                    className={p.packingVerified ? 'mq-btn-success' : 'mq-btn-primary'}
                    onClick={toggleVerified}>
                    {p.packingVerified ? '✓ Packing Verified — Click to Unverify' : 'Mark Packing as Verified'}
                </button>
            </div>
        </div>
    );
}

/* ── Single packing item row ─────────────────────────────────────────────── */
function PackingItemRow({ name, checked, photos, required, onToggle, onAddPhoto, onViewPhotos, style }) {
    const handleFile = (e) => {
        const f = e.target.files[0]; if (!f) return;
        const r = new FileReader();
        r.onload = ev => onAddPhoto(ev.target.result);
        r.readAsDataURL(f);
        e.target.value = '';
    };

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, ...style }}>
            <button
                onClick={onToggle}
                style={{
                    flex: 1, textAlign: 'left', padding: '7px 12px',
                    borderRadius: 8, cursor: 'pointer', fontWeight: 500, fontSize: 13,
                    border: '1.5px solid',
                    borderColor: checked ? '#10b981' : required ? '#f97316' : '#e2e8f0',
                    background: checked ? '#d1fae5' : required ? '#fff7ed' : '#f8fafc',
                    color: checked ? '#065f46' : required ? '#c2410c' : '#475569',
                    display: 'flex', alignItems: 'center', gap: 6,
                }}>
                <span>{checked ? '☑' : '☐'}</span>
                <span>{name}</span>
                {required && !checked && <span style={{ fontSize: 10, marginLeft: 'auto', opacity: .7 }}>required</span>}
            </button>

            {photos.length > 0 && (
                <button className="mq-btn-ghost" style={{ fontSize: 11, padding: '4px 6px' }}
                    onClick={() => onViewPhotos(0)}>
                    📷 {photos.length}
                </button>
            )}

            <label style={{ cursor: 'pointer', padding: '4px 6px', borderRadius: 6, border: '1px solid #e2e8f0', fontSize: 14, color: '#94a3b8', background: '#f8fafc' }}>
                +📷
                <input type="file" accept="image/*" hidden onChange={handleFile} />
            </label>
        </div>
    );
}
