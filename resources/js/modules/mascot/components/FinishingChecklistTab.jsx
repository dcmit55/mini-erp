import React, { useState } from 'react';
import { useMQ } from '../MascotQC';
import {
    SECTIONS, fmtDt, calcProg,
    PM_REQ_STRICT, PM_OPT_FLEX, PI_ITEMS,
} from '../constants/mascotConstants';

export default function FinishingChecklistTab({ project: p }) {
    const { updateProject, setFail, setPhoto } = useMQ();
    const [expandedItem, setExpandedItem] = useState(null);

    const items = p.checklistItems || {};

    const setStatus = (itemId, status) => {
        updateProject(p.id, proj => {
            if (!proj.checklistItems[itemId]) return;
            proj.checklistItems[itemId].status = status;
            proj.checklistItems[itemId].history = [
                ...(proj.checklistItems[itemId].history || []),
                { status, at: new Date().toISOString() },
            ];
            proj.progress = calcProg(proj);
        });
        if (status === 'FAIL') {
            setFail({ projectId: p.id, itemId, source: 'finishing' });
        }
    };

    const setNote = (itemId, note) => {
        updateProject(p.id, proj => {
            if (proj.checklistItems[itemId]) proj.checklistItems[itemId].note = note;
        });
    };

    const addPhoto = (itemId, url) => {
        updateProject(p.id, proj => {
            const it = proj.checklistItems[itemId];
            if (it) it.photos = [...(it.photos || []), url];
        });
    };

    const removePhoto = (itemId, idx) => {
        updateProject(p.id, proj => {
            const it = proj.checklistItems[itemId];
            if (it) it.photos = it.photos.filter((_, i) => i !== idx);
        });
    };

    const summary = computeSummary(items);

    return (
        <div>
            {/* summary bar */}
            <div style={{ display: 'flex', gap: 16, background: '#fff', borderRadius: 10, padding: '10px 16px', marginBottom: 14, boxShadow: '0 1px 4px rgba(0,0,0,.06)', flexWrap: 'wrap' }}>
                <span style={{ fontSize: 12, color: '#64748b' }}>Pass: <b style={{ color: '#10b981' }}>{summary.pass}</b></span>
                <span style={{ fontSize: 12, color: '#64748b' }}>Fail: <b style={{ color: '#ef4444' }}>{summary.fail}</b></span>
                <span style={{ fontSize: 12, color: '#64748b' }}>Pending: <b style={{ color: '#94a3b8' }}>{summary.pending}</b></span>
                <span style={{ marginLeft: 'auto', fontSize: 12, fontWeight: 700, color: '#6366f1' }}>{summary.pct}% complete</span>
            </div>

            {/* sections 1–8, 10 */}
            {SECTIONS.map(section => (
                <div key={section.id} style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', marginBottom: 12, boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 10, paddingBottom: 8, borderBottom: '1px solid #f1f5f9' }}>
                        <span style={{ fontSize: 18 }}>{section.icon}</span>
                        <span style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>{section.title}</span>
                        <span style={{ fontSize: 11, color: '#94a3b8', marginLeft: 'auto' }}>
                            {section.items.filter(i => items[i.id]?.status === 'PASS').length}/{section.items.length} passed
                        </span>
                    </div>

                    {section.items.map(item => {
                        const d = items[item.id] || {};
                        const isExpanded = expandedItem === item.id;
                        const status = d.status;

                        return (
                            <div key={item.id} style={{
                                borderRadius: 8, border: `1.5px solid ${status === 'PASS' ? '#bbf7d0' : status === 'FAIL' ? '#fecaca' : '#f1f5f9'}`,
                                background: status === 'PASS' ? '#f0fdf4' : status === 'FAIL' ? '#fef2f2' : '#fafafa',
                                padding: '10px 12px', marginBottom: 6,
                            }}>
                                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                                    {/* status circle */}
                                    <div style={{
                                        width: 26, height: 26, borderRadius: '50%', flexShrink: 0, display: 'flex',
                                        alignItems: 'center', justifyContent: 'center', fontSize: 11, fontWeight: 700,
                                        background: status === 'PASS' ? '#d1fae5' : status === 'FAIL' ? '#fee2e2' : '#f1f5f9',
                                        border: `2px solid ${status === 'PASS' ? '#10b981' : status === 'FAIL' ? '#ef4444' : '#e2e8f0'}`,
                                        color: status === 'PASS' ? '#065f46' : status === 'FAIL' ? '#991b1b' : '#94a3b8',
                                    }}>
                                        {status === 'PASS' ? '✓' : status === 'FAIL' ? '✗' : item.id}
                                    </div>
                                    <div style={{ flex: 1 }}>
                                        <div style={{ fontSize: 13, color: '#1e293b', marginBottom: 6 }}>{item.l}</div>
                                        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                                            <button className={`mq-bpass${status === 'PASS' ? ' active' : ''}`}
                                                onClick={() => setStatus(item.id, status === 'PASS' ? null : 'PASS')}>
                                                ✓ Pass
                                            </button>
                                            <button className={`mq-bfail${status === 'FAIL' ? ' active' : ''}`}
                                                onClick={() => setStatus(item.id, status === 'FAIL' ? null : 'FAIL')}>
                                                ✗ Fail
                                            </button>
                                            <button className="mq-btn-ghost" style={{ fontSize: 11, padding: '3px 8px' }}
                                                onClick={() => setExpandedItem(isExpanded ? null : item.id)}>
                                                {isExpanded ? '▲' : '▼ Note/Photo'}
                                            </button>
                                        </div>
                                    </div>
                                    {d.photos?.length > 0 && (
                                        <button className="mq-btn-ghost" style={{ fontSize: 11 }}
                                            onClick={() => setPhoto({ urls: d.photos, idx: 0 })}>
                                            📷 {d.photos.length}
                                        </button>
                                    )}
                                </div>

                                {isExpanded && (
                                    <div style={{ marginTop: 10, paddingTop: 10, borderTop: '1px solid #f1f5f9' }}>
                                        <textarea className="mq-input" rows={2} style={{ resize: 'vertical', marginBottom: 8 }}
                                            value={d.note || ''} placeholder="Inspection note…"
                                            onChange={e => setNote(item.id, e.target.value)} />
                                        <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap', alignItems: 'center' }}>
                                            {(d.photos || []).map((url, i) => (
                                                <div key={i} style={{ position: 'relative' }}>
                                                    <img src={url} alt="" onClick={() => setPhoto({ urls: d.photos, idx: i })}
                                                        style={{ width: 44, height: 44, objectFit: 'cover', borderRadius: 6, cursor: 'pointer', border: '1px solid #e2e8f0' }} />
                                                    <button onClick={() => removePhoto(item.id, i)}
                                                        style={{ position: 'absolute', top: -4, right: -4, width: 16, height: 16, borderRadius: '50%', background: '#ef4444', color: '#fff', border: 'none', cursor: 'pointer', fontSize: 10, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>×</button>
                                                </div>
                                            ))}
                                            <label style={{ width: 44, height: 44, border: '1.5px dashed #cbd5e1', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 18, color: '#94a3b8' }}>
                                                +<input type="file" accept="image/*" hidden onChange={e => {
                                                    const file = e.target.files[0]; if (!file) return;
                                                    const r = new FileReader();
                                                    r.onload = ev => addPhoto(item.id, ev.target.result);
                                                    r.readAsDataURL(file); e.target.value = '';
                                                }} />
                                            </label>
                                        </div>
                                        {d.history?.length > 0 && (
                                            <div style={{ marginTop: 8 }}>
                                                {d.history.map((h, i) => (
                                                    <div key={i} style={{ display: 'flex', gap: 8, fontSize: 11, padding: '2px 0' }}>
                                                        <span className={h.status === 'PASS' ? 'mq-badge-pass' : 'mq-badge-fail'} style={{ fontSize: 10 }}>{h.status}</span>
                                                        <span style={{ color: '#94a3b8' }}>{fmtDt(h.at)}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            ))}

            {/* section 9: packing list */}
            <PackingSection project={p} />
        </div>
    );
}

/* ── Packing List section (Section 9) ─────────────────────────────────────── */
function PackingSection({ project: p }) {
    const { updateProject, setPhoto } = useMQ();
    const [customInput, setCustomInput] = useState('');
    const [removedOpt, setRemovedOpt] = useState([]); // hidden optional items

    const reqItems = p.mascotType === 'Mascot' ? PM_REQ_STRICT : PI_ITEMS;
    const optItems = (p.mascotType === 'Mascot' ? PM_OPT_FLEX : []).filter(n => !removedOpt.includes(n));
    const custom   = p.packingCustom || [];
    const pItems   = p.packingItems  || {};
    const verPhotos = p.packingVerifyPhotos || [];
    const verified  = p.packingVerified   || false;

    const toggle = (name) => {
        if (verified) return;
        updateProject(p.id, proj => {
            if (!proj.packingItems) proj.packingItems = {};
            if (!proj.packingItems[name]) proj.packingItems[name] = { checked: false, photos: [] };
            proj.packingItems[name].checked = !proj.packingItems[name].checked;
        });
    };

    const addItemPhoto = (name, url) => {
        if (verified) return;
        updateProject(p.id, proj => {
            if (!proj.packingItems?.[name]) return;
            proj.packingItems[name].photos = [...(proj.packingItems[name].photos || []), url];
        });
    };

    const addVerifyPhoto = (url) => {
        updateProject(p.id, proj => {
            proj.packingVerifyPhotos = [...(proj.packingVerifyPhotos || []), url];
        });
    };

    const addCustom = () => {
        const n = customInput.trim();
        if (!n) return;
        updateProject(p.id, proj => {
            if (!proj.packingCustom) proj.packingCustom = [];
            if (!proj.packingCustom.find(x => x.name === n)) {
                proj.packingCustom = [...proj.packingCustom, { name: n }];
                if (!proj.packingItems) proj.packingItems = {};
                proj.packingItems[n] = { checked: false, photos: [] };
            }
        });
        setCustomInput('');
    };

    const canVerify = () => {
        if (verPhotos.length === 0) return false;
        const allChecked = [...reqItems, ...optItems, ...custom.map(c => c.name)]
            .filter(n => pItems[n]?.checked);
        return allChecked.every(n => (pItems[n]?.photos || []).length > 0);
    };

    const verify = () => {
        if (!canVerify()) return;
        updateProject(p.id, proj => {
            proj.packingVerified = true;
            proj.progress = calcProg(proj);
        });
    };

    const checkedCount = [...reqItems, ...optItems, ...custom.map(c => c.name)]
        .filter(n => pItems[n]?.checked).length;
    const missingPhotos = [...reqItems, ...optItems, ...custom.map(c => c.name)]
        .filter(n => pItems[n]?.checked && (pItems[n]?.photos || []).length === 0).length;

    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', marginBottom: 12, boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 14, paddingBottom: 8, borderBottom: '1px solid #f1f5f9' }}>
                <span style={{ fontSize: 18 }}>📦</span>
                <span style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>Packing List</span>
                {verified && <span style={{ marginLeft: 4, fontSize: 11, padding: '2px 8px', borderRadius: 999, background: '#d1fae5', color: '#065f46', fontWeight: 700 }}>✓ Verified</span>}
                <span style={{ marginLeft: 'auto', fontSize: 11, color: '#94a3b8' }}>{checkedCount} checked</span>
            </div>

            {/* required items */}
            <div style={{ marginBottom: 12 }}>
                <div style={{ fontSize: 11, fontWeight: 700, color: '#f97316', marginBottom: 6, textTransform: 'uppercase', letterSpacing: '.04em' }}>Required Items</div>
                {reqItems.map(name => (
                    <PackingItem key={name} name={name} data={pItems[name] || {}} required verified={verified}
                        onToggle={() => toggle(name)}
                        onPhoto={url => addItemPhoto(name, url)}
                        onView={idx => setPhoto({ urls: pItems[name]?.photos, idx })} />
                ))}
            </div>

            {/* optional items */}
            {optItems.length > 0 && (
                <div style={{ marginBottom: 12 }}>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#6366f1', marginBottom: 6, textTransform: 'uppercase', letterSpacing: '.04em' }}>Optional Items</div>
                    {optItems.map(name => (
                        <PackingItem key={name} name={name} data={pItems[name] || {}} verified={verified}
                            onToggle={() => toggle(name)}
                            onPhoto={url => addItemPhoto(name, url)}
                            onView={idx => setPhoto({ urls: pItems[name]?.photos, idx })}
                            onRemove={!verified ? () => setRemovedOpt(r => [...r, name]) : null} />
                    ))}
                    {removedOpt.length > 0 && (
                        <button className="mq-btn-ghost" style={{ fontSize: 11, marginTop: 4 }}
                            onClick={() => setRemovedOpt([])}>
                            Restore {removedOpt.length} removed
                        </button>
                    )}
                </div>
            )}

            {/* custom items */}
            <div style={{ marginBottom: 12 }}>
                <div style={{ fontSize: 11, fontWeight: 700, color: '#64748b', marginBottom: 6, textTransform: 'uppercase', letterSpacing: '.04em' }}>Custom Items</div>
                {custom.map(c => (
                    <PackingItem key={c.name} name={c.name} data={pItems[c.name] || {}} verified={verified}
                        onToggle={() => toggle(c.name)}
                        onPhoto={url => addItemPhoto(c.name, url)}
                        onView={idx => setPhoto({ urls: pItems[c.name]?.photos, idx })} />
                ))}
                {!verified && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 6 }}>
                        <input className="mq-input" placeholder="Add custom item…" value={customInput}
                            onChange={e => setCustomInput(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && addCustom()} />
                        <button className="mq-btn-primary" style={{ fontSize: 12 }} onClick={addCustom}>Add</button>
                    </div>
                )}
            </div>

            {/* verification photos */}
            <div style={{ marginBottom: 12 }}>
                <div style={{ fontSize: 11, fontWeight: 700, color: '#64748b', marginBottom: 6 }}>Verification Photo (min. 1, wajib)</div>
                <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap', alignItems: 'center' }}>
                    {verPhotos.map((url, i) => (
                        <img key={i} src={url} alt="" onClick={() => setPhoto({ urls: verPhotos, idx: i })}
                            style={{ width: 52, height: 52, objectFit: 'cover', borderRadius: 8, cursor: 'pointer', border: '1px solid #e2e8f0' }} />
                    ))}
                    {!verified && (
                        <label style={{ width: 52, height: 52, border: '1.5px dashed #cbd5e1', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 20, color: '#94a3b8' }}>
                            +<input type="file" accept="image/*" hidden onChange={e => {
                                const f = e.target.files[0]; if (!f) return;
                                const r = new FileReader();
                                r.onload = ev => addVerifyPhoto(ev.target.result);
                                r.readAsDataURL(f); e.target.value = '';
                            }} />
                        </label>
                    )}
                </div>
            </div>

            {/* progress + verify */}
            <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                {missingPhotos > 0 && (
                    <span style={{ fontSize: 11, color: '#f59e0b' }}>⚠️ {missingPhotos} checked item(s) missing photo</span>
                )}
                {!verified ? (
                    <button className="mq-btn-primary" style={{ marginLeft: 'auto' }}
                        disabled={!canVerify()} onClick={verify}>
                        ✓ Verify Packing List
                    </button>
                ) : (
                    <span style={{ marginLeft: 'auto', fontSize: 13, fontWeight: 700, color: '#10b981' }}>✓ Packing Verified</span>
                )}
            </div>
        </div>
    );
}

function PackingItem({ name, data, required, verified, onToggle, onPhoto, onView, onRemove }) {
    const checked = data.checked || false;
    const photos  = data.photos  || [];
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '6px 0', borderBottom: '1px solid #f8fafc' }}>
            <input type="checkbox" checked={checked} onChange={onToggle} disabled={verified}
                style={{ width: 16, height: 16, cursor: 'pointer', accentColor: '#6366f1' }} />
            <span style={{ fontSize: 12, flex: 1, color: checked ? '#1e293b' : '#94a3b8', fontWeight: checked ? 600 : 400 }}>
                {name}{required && <span style={{ color: '#ef4444', marginLeft: 2 }}>*</span>}
            </span>
            {/* photo upload for checked items */}
            {checked && (
                <div style={{ display: 'flex', gap: 4, alignItems: 'center' }}>
                    {photos.map((url, i) => (
                        <img key={i} src={url} alt="" onClick={() => onView(i)}
                            style={{ width: 32, height: 32, objectFit: 'cover', borderRadius: 5, cursor: 'pointer', border: '1px solid #e2e8f0' }} />
                    ))}
                    {!verified && (
                        <label style={{ width: 32, height: 32, border: '1.5px dashed #cbd5e1', borderRadius: 5, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 14, color: '#94a3b8' }}>
                            +<input type="file" accept="image/*" hidden onChange={e => {
                                const f = e.target.files[0]; if (!f) return;
                                const r = new FileReader();
                                r.onload = ev => onPhoto(ev.target.result);
                                r.readAsDataURL(f); e.target.value = '';
                            }} />
                        </label>
                    )}
                    {photos.length === 0 && <span style={{ fontSize: 10, color: '#f59e0b' }}>⚠️ need photo</span>}
                </div>
            )}
            {onRemove && (
                <button onClick={onRemove} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', fontSize: 14, padding: 0 }}>×</button>
            )}
        </div>
    );
}

function computeSummary(items) {
    const vals = Object.values(items);
    const pass = vals.filter(v => v.status === 'PASS').length;
    const fail = vals.filter(v => v.status === 'FAIL').length;
    const pending = vals.filter(v => !v.status).length;
    const total = vals.length;
    const pct = total > 0 ? Math.round((pass + fail) / total * 100) : 0;
    return { pass, fail, pending, pct };
}
