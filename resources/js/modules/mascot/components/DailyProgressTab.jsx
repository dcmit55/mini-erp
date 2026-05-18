import React, { useState, useRef } from 'react';
import { useMQ } from '../MascotQC';
import {
    DAILY_SECTIONS, DEFAULT_PARTS,
    todayStr, fmtDt, buildDP, rStatus, rCat, rItem, rCreated,
} from '../constants/mascotConstants';

/* ── helpers ── */
const partKey = (op, part) => `${op}::${part}`;

function getItemStatus(itemData) {
    if (!itemData) return null;
    if (itemData.finalized) {
        const allParts = Object.entries(itemData.opParts || {}).flatMap(([op, parts]) =>
            parts.map(p => itemData.partData?.[partKey(op, p)]?.status)
        );
        if (allParts.length === 0) return null;
        return allParts.every(s => s === 'PASS') ? 'PASS' : 'FAIL';
    }
    return null;
}

function StatusCircle({ status, size = 28 }) {
    const color = status === 'PASS' ? '#10b981' : status === 'FAIL' ? '#ef4444' : '#cbd5e1';
    const label = status === 'PASS' ? '✓' : status === 'FAIL' ? '✗' : '?';
    return (
        <div style={{
            width: size, height: size, borderRadius: '50%', flexShrink: 0,
            background: color + '22', border: `2px solid ${color}`,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontSize: size * 0.42, fontWeight: 700, color,
        }}>{label}</div>
    );
}

/* ── main component ── */
export default function DailyProgressTab({ project: p }) {
    const { updateProject, setDpFail, setPhoto, dailyDate, setDailyDate, operators: ctxOps } = useMQ();
    const OPERATORS = ctxOps?.length ? ctxOps : ['—'];
    const [addPartOpen, setAddPartOpen] = useState(null); // 'itemId::op'
    const [customPart, setCustomPart] = useState('');
    const addPartRef = useRef(null);

    /* ensure dp entry */
    const ensureEntry = (date) => {
        updateProject(p.id, proj => {
            if (!proj.dailyProgress) proj.dailyProgress = {};
            if (!proj.dailyProgress[date]) {
                proj.dailyProgress[date] = { sessionNote: '', items: buildDP() };
            }
        });
    };

    const handleDateChange = (d) => {
        setDailyDate(d);
        ensureEntry(d);
    };

    const dp = p.dailyProgress?.[dailyDate] || { sessionNote: '', items: {} };
    const dpItems = dp.items || {};

    /* session note */
    const setSessionNote = (note) => updateProject(p.id, proj => {
        if (proj.dailyProgress?.[dailyDate]) proj.dailyProgress[dailyDate].sessionNote = note;
    });

    /* operators for item */
    const getItemOps = (itemId) => dpItems[itemId]?.operators || [];

    const toggleItemOp = (itemId, op) => {
        ensureEntry(dailyDate);
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item) return;
            const ops = item.operators || [];
            const idx = ops.indexOf(op);
            if (idx >= 0) {
                ops.splice(idx, 1);
                if (item.opParts) delete item.opParts[op];
            } else {
                item.operators = [...ops, op];
                if (!item.opParts) item.opParts = {};
                if (!item.opParts[op]) item.opParts[op] = [];
            }
        });
    };

    /* parts for an operator on an item */
    const getOpParts = (itemId, op) => dpItems[itemId]?.opParts?.[op] || [];

    const addPartToOp = (itemId, op, part) => {
        if (!part.trim()) return;
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item) return;
            if (!item.opParts) item.opParts = {};
            if (!item.opParts[op]) item.opParts[op] = [];
            if (!item.opParts[op].includes(part)) item.opParts[op] = [...item.opParts[op], part];
        });
        setAddPartOpen(null);
        setCustomPart('');
    };

    const removePart = (itemId, op, part) => {
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item?.opParts?.[op]) return;
            item.opParts[op] = item.opParts[op].filter(x => x !== part);
            if (item.partData) delete item.partData[partKey(op, part)];
        });
    };

    /* part result */
    const getPartData = (itemId, op, part) => dpItems[itemId]?.partData?.[partKey(op, part)] || {};

    const setPartStatus = (itemId, op, part, status) => {
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item) return;
            if (!item.partData) item.partData = {};
            const k = partKey(op, part);
            if (!item.partData[k]) item.partData[k] = { status: null, note: '', photos: [] };
            item.partData[k].status = status;
            item.history = [...(item.history || []), {
                type: 'part', op, part, status, at: new Date().toISOString(),
            }];
        });
        if (status === 'FAIL') {
            const sItem = DAILY_SECTIONS.flatMap(s => s.items).find(i => i.id === itemId);
            setDpFail({ projectId: p.id, date: dailyDate, itemId, partName: part, op, itemLabel: sItem?.l || itemId });
        }
    };

    const setPartNote = (itemId, op, part, note) => {
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item?.partData) return;
            const k = partKey(op, part);
            if (item.partData[k]) item.partData[k].note = note;
        });
    };

    const addPartPhoto = (itemId, op, part, url) => {
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item) return;
            if (!item.partData) item.partData = {};
            const k = partKey(op, part);
            if (!item.partData[k]) item.partData[k] = { status: null, note: '', photos: [] };
            item.partData[k].photos = [...(item.partData[k].photos || []), url];
        });
    };

    /* check if all parts for item have status */
    const allPartsRated = (itemId) => {
        const itemData = dpItems[itemId];
        if (!itemData?.opParts) return false;
        const allParts = Object.entries(itemData.opParts).flatMap(([op, parts]) =>
            parts.map(part => itemData.partData?.[partKey(op, part)]?.status)
        );
        return allParts.length > 0 && allParts.every(s => s !== null && s !== undefined);
    };

    /* finalize */
    const addFinalizePhoto = (itemId, url) => {
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item) return;
            item.finalizePhotos = [...(item.finalizePhotos || []), url];
        });
    };

    const finalizeItem = (itemId) => {
        updateProject(p.id, proj => {
            const item = proj.dailyProgress?.[dailyDate]?.items?.[itemId];
            if (!item) return;
            item.finalized = true;
            item.finalizedAt = new Date().toISOString();
            const allParts = Object.entries(item.opParts || {}).flatMap(([op, parts]) =>
                parts.map(part => item.partData?.[partKey(op, part)]?.status)
            );
            item.status = allParts.every(s => s === 'PASS') ? 'PASS' : 'FAIL';
            item.history = [...(item.history || []), { type: 'finalize', status: item.status, at: item.finalizedAt }];
        });
    };

    /* daily summary */
    const summary = computeSummary(dpItems);

    /* defects for this date */
    const dateDefects = (p.rejectLog || []).filter(d => d.dailyDate === dailyDate);

    return (
        <div>
            {/* ── Date + note header ── */}
            <div style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', marginBottom: 14, boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
                <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                        <label style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>Date</label>
                        <input className="mq-input" type="date" value={dailyDate} style={{ width: 150 }}
                            onChange={e => handleDateChange(e.target.value)} />
                        <button className="mq-btn-ghost" style={{ fontSize: 11, padding: '4px 8px' }}
                            onClick={() => handleDateChange(todayStr())}>Today</button>
                    </div>
                    <div style={{ flex: 1, minWidth: 180 }}>
                        <input className="mq-input" placeholder="Session note…" value={dp.sessionNote || ''}
                            onChange={e => setSessionNote(e.target.value)} />
                    </div>
                </div>
            </div>

            {/* ── Daily summary ── */}
            <div style={{ display: 'flex', gap: 10, marginBottom: 14, flexWrap: 'wrap' }}>
                {[
                    { label: 'Pass',    val: summary.pass,    color: '#10b981' },
                    { label: 'Fail',    val: summary.fail,    color: '#ef4444' },
                    { label: 'Pending', val: summary.pending, color: '#94a3b8' },
                    { label: 'Pass Rate', val: `${summary.pct}%`, color: '#6366f1' },
                ].map(k => (
                    <div key={k.label} style={{ background: '#fff', borderRadius: 10, padding: '8px 14px', boxShadow: '0 1px 3px rgba(0,0,0,.06)', display: 'flex', gap: 6, alignItems: 'baseline' }}>
                        <span style={{ fontSize: 18, fontWeight: 800, color: k.color }}>{k.val}</span>
                        <span style={{ fontSize: 11, color: '#94a3b8' }}>{k.label}</span>
                    </div>
                ))}
            </div>

            {/* ── Sections ── */}
            {DAILY_SECTIONS.map(section => (
                <div key={section.id} style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', marginBottom: 12, boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 12, paddingBottom: 8, borderBottom: '1px solid #f1f5f9' }}>
                        <span style={{ fontSize: 18 }}>{section.icon}</span>
                        <span style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>{section.title}</span>
                    </div>

                    {section.items.map(item => {
                        const itemData = dpItems[item.id] || {};
                        const itemOps  = itemData.operators || [];
                        const status   = itemData.finalized ? itemData.status : null;
                        const isPast   = dailyDate < todayStr();

                        return (
                            <div key={item.id} style={{
                                borderRadius: 10, border: `1.5px solid ${status === 'PASS' ? '#bbf7d0' : status === 'FAIL' ? '#fecaca' : '#f1f5f9'}`,
                                background: status === 'PASS' ? '#f0fdf4' : status === 'FAIL' ? '#fef2f2' : '#fafafa',
                                padding: '10px 14px', marginBottom: 8,
                            }}>
                                {/* item header */}
                                <div style={{ display: 'flex', gap: 10, alignItems: 'flex-start' }}>
                                    <StatusCircle status={status} />
                                    <div style={{ flex: 1 }}>
                                        <div style={{ fontSize: 13, fontWeight: 600, color: '#1e293b', marginBottom: 6 }}>{item.l}</div>
                                        {/* operator picker */}
                                        {!itemData.finalized && (
                                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                                                {OPERATORS.map(op => (
                                                    <button key={op}
                                                        className={`mq-op-chip${itemOps.includes(op) ? ' active' : ''}`}
                                                        style={{ fontSize: 11 }}
                                                        onClick={() => toggleItemOp(item.id, op)}>
                                                        {op}
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                        {itemData.finalized && (
                                            <span style={{ fontSize: 11, color: '#10b981', fontWeight: 600 }}>✓ Finalized at {fmtDt(itemData.finalizedAt)}</span>
                                        )}
                                    </div>
                                </div>

                                {/* per-operator rows */}
                                {itemOps.map(op => {
                                    const parts = getOpParts(item.id, op);
                                    const toggleKey = `${item.id}::${op}`;
                                    const isAddOpen = addPartOpen === toggleKey;
                                    const usedParts = new Set(parts);
                                    const availParts = [...DEFAULT_PARTS, ...(p.customParts || [])].filter(pt => !usedParts.has(pt));

                                    return (
                                        <div key={op} style={{ marginTop: 10, paddingTop: 10, borderTop: '1px solid #f1f5f9' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                                                <span style={{ fontSize: 12, fontWeight: 700, color: '#6366f1' }}>👤 {op}</span>
                                                {!itemData.finalized && (
                                                    <div style={{ position: 'relative' }}>
                                                        <button className="mq-btn-ghost" style={{ fontSize: 11, padding: '3px 8px' }}
                                                            onClick={() => setAddPartOpen(isAddOpen ? null : toggleKey)}>
                                                            + Add Part
                                                        </button>
                                                        {isAddOpen && (
                                                            <div ref={addPartRef} style={{
                                                                position: 'absolute', top: '100%', left: 0, zIndex: 100,
                                                                background: '#fff', borderRadius: 10, boxShadow: '0 4px 16px rgba(0,0,0,.12)',
                                                                border: '1px solid #e2e8f0', padding: 10, minWidth: 200,
                                                            }}>
                                                                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4, marginBottom: 8 }}>
                                                                    {availParts.map(pt => (
                                                                        <button key={pt}
                                                                            onClick={() => addPartToOp(item.id, op, pt)}
                                                                            style={{ padding: '3px 8px', borderRadius: 999, fontSize: 11, border: '1px solid #e2e8f0', background: '#f8fafc', cursor: 'pointer' }}>
                                                                            {pt}
                                                                        </button>
                                                                    ))}
                                                                </div>
                                                                <div style={{ display: 'flex', gap: 5 }}>
                                                                    <input className="mq-input" placeholder="Custom part…" value={customPart}
                                                                        style={{ fontSize: 11, padding: '4px 8px' }}
                                                                        onChange={e => setCustomPart(e.target.value)}
                                                                        onKeyDown={e => e.key === 'Enter' && addPartToOp(item.id, op, customPart)} />
                                                                    <button className="mq-btn-primary" style={{ fontSize: 11, padding: '4px 8px' }}
                                                                        onClick={() => addPartToOp(item.id, op, customPart)}>Add</button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>

                                            {/* parts list */}
                                            {parts.map(part => {
                                                const pd = getPartData(item.id, op, part);
                                                const ps = pd.status || null;
                                                return (
                                                    <div key={part} style={{
                                                        border: `1px solid ${ps === 'PASS' ? '#bbf7d0' : ps === 'FAIL' ? '#fecaca' : '#e2e8f0'}`,
                                                        background: ps === 'PASS' ? '#f0fdf4' : ps === 'FAIL' ? '#fff5f5' : '#f8fafc',
                                                        borderRadius: 8, padding: '8px 10px', marginBottom: 6,
                                                    }}>
                                                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
                                                            <span style={{ fontSize: 12, fontWeight: 600, color: '#334155', flex: 1 }}>
                                                                {part}
                                                                {ps && (
                                                                    <span style={{ marginLeft: 6, fontSize: 10, padding: '1px 6px', borderRadius: 999, background: ps === 'PASS' ? '#d1fae5' : '#fee2e2', color: ps === 'PASS' ? '#065f46' : '#991b1b', fontWeight: 700 }}>{ps}</span>
                                                                )}
                                                            </span>
                                                            {!itemData.finalized && (
                                                                <>
                                                                    <button className={`mq-bpass${ps === 'PASS' ? ' active' : ''}`} style={{ fontSize: 11, padding: '2px 7px' }}
                                                                        onClick={() => setPartStatus(item.id, op, part, ps === 'PASS' ? null : 'PASS')}>
                                                                        ✓ Pass
                                                                    </button>
                                                                    <button className={`mq-bfail${ps === 'FAIL' ? ' active' : ''}`} style={{ fontSize: 11, padding: '2px 7px' }}
                                                                        onClick={() => setPartStatus(item.id, op, part, ps === 'FAIL' ? null : 'FAIL')}>
                                                                        ✗ Fail
                                                                    </button>
                                                                    <button style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', fontSize: 14, padding: '0 2px', lineHeight: 1 }}
                                                                        onClick={() => removePart(item.id, op, part)}>×</button>
                                                                </>
                                                            )}
                                                        </div>
                                                        <input className="mq-input" placeholder="Note…" value={pd.note || ''}
                                                            style={{ fontSize: 11, marginBottom: 6 }}
                                                            disabled={!!itemData.finalized}
                                                            onChange={e => setPartNote(item.id, op, part, e.target.value)} />
                                                        <PhotoRow
                                                            photos={pd.photos || []}
                                                            disabled={!!itemData.finalized}
                                                            onAdd={url => addPartPhoto(item.id, op, part, url)}
                                                            onView={idx => setPhoto({ urls: pd.photos, idx })} />
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    );
                                })}

                                {/* finalize area */}
                                {!itemData.finalized && allPartsRated(item.id) && itemOps.length > 0 && (
                                    <FinalizeArea
                                        photos={itemData.finalizePhotos || []}
                                        onAdd={url => addFinalizePhoto(item.id, url)}
                                        onConfirm={() => finalizeItem(item.id)}
                                        onView={idx => setPhoto({ urls: itemData.finalizePhotos, idx })} />
                                )}
                            </div>
                        );
                    })}
                </div>
            ))}

            {/* ── Defect log for this date ── */}
            {dateDefects.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', marginBottom: 12, boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#ef4444', marginBottom: 10 }}>⚠️ Defects — {dailyDate} ({dateDefects.length})</div>
                    {dateDefects.map(d => (
                        <div key={d.id} style={{ padding: '8px 0', borderBottom: '1px solid #f1f5f9', fontSize: 12 }}>
                            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                                <span style={{ fontWeight: 600, color: '#334155' }}>{rItem(d)}</span>
                                <span style={{ fontSize: 10, padding: '1px 6px', borderRadius: 999, background: '#fee2e2', color: '#991b1b', fontWeight: 700 }}>{rCat(d)}</span>
                                <span style={{ marginLeft: 'auto', fontSize: 10, color: rStatus(d) === 'OPEN' ? '#f59e0b' : '#10b981', fontWeight: 700 }}>{rStatus(d)}</span>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* ── Work log timeline ── */}
            <WorkLog dpItems={dpItems} />
        </div>
    );
}

/* ── Finalize area ── */
function FinalizeArea({ photos, onAdd, onConfirm, onView }) {
    return (
        <div style={{ marginTop: 12, padding: '10px 14px', background: '#fffbeb', borderRadius: 10, border: '1.5px solid #fde68a' }}>
            <div style={{ fontSize: 12, fontWeight: 700, color: '#92400e', marginBottom: 8 }}>All parts rated — Finalize this item</div>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
                <PhotoRow photos={photos} onAdd={onAdd} onView={onView} label="Bukti foto (min. 1)" />
                <button className="mq-btn-primary" style={{ fontSize: 12 }}
                    disabled={photos.length === 0}
                    onClick={onConfirm}>
                    ✓ Confirm Finalized
                </button>
            </div>
        </div>
    );
}

/* ── Photo row ── */
function PhotoRow({ photos, onAdd, onView, disabled, label }) {
    const handleFile = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => onAdd(ev.target.result);
        reader.readAsDataURL(file);
        e.target.value = '';
    };
    return (
        <div>
            {label && <div style={{ fontSize: 10, color: '#94a3b8', marginBottom: 4 }}>{label}</div>}
            <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap', alignItems: 'center' }}>
                {(photos || []).map((url, i) => (
                    <img key={i} src={url} alt="" onClick={() => onView(i)}
                        style={{ width: 40, height: 40, objectFit: 'cover', borderRadius: 6, cursor: 'pointer', border: '1px solid #e2e8f0' }} />
                ))}
                {!disabled && (
                    <label style={{ width: 40, height: 40, border: '1.5px dashed #cbd5e1', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 16, color: '#94a3b8' }}>
                        +
                        <input type="file" accept="image/*" hidden onChange={handleFile} />
                    </label>
                )}
            </div>
        </div>
    );
}

/* ── Work log timeline ── */
function WorkLog({ dpItems }) {
    const entries = [];
    Object.entries(dpItems).forEach(([id, item]) => {
        (item.history || []).forEach(h => {
            entries.push({ ...h, itemId: id });
        });
    });
    entries.sort((a, b) => new Date(b.at) - new Date(a.at));
    if (entries.length === 0) return null;

    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
            <div style={{ fontSize: 13, fontWeight: 700, color: '#334155', marginBottom: 10 }}>📋 Work Log</div>
            {entries.slice(0, 20).map((e, i) => (
                <div key={i} style={{ display: 'flex', gap: 8, alignItems: 'center', padding: '5px 0', borderBottom: '1px solid #f8fafc', fontSize: 11 }}>
                    <span style={{ color: '#94a3b8', minWidth: 90 }}>{fmtDt(e.at)}</span>
                    {e.type === 'finalize' ? (
                        <span style={{ fontWeight: 600, color: '#6366f1' }}>Finalized <span style={{ color: e.status === 'PASS' ? '#10b981' : '#ef4444' }}>{e.status}</span></span>
                    ) : e.type === 'part' ? (
                        <span><b>{e.op}</b> — {e.part}: <span style={{ color: e.status === 'PASS' ? '#10b981' : '#ef4444', fontWeight: 700 }}>{e.status}</span></span>
                    ) : (
                        <span>{e.status}</span>
                    )}
                    <span style={{ marginLeft: 'auto', color: '#94a3b8', fontSize: 10 }}>{e.itemId}</span>
                </div>
            ))}
        </div>
    );
}

function computeSummary(items) {
    let pass = 0, fail = 0, pending = 0;
    Object.values(items).forEach(it => {
        const s = it.finalized ? it.status : null;
        if (s === 'PASS') pass++;
        else if (s === 'FAIL') fail++;
        else pending++;
    });
    const total = pass + fail + pending;
    const pct = total > 0 ? Math.round(pass / total * 100) : 0;
    return { pass, fail, pending, pct };
}
