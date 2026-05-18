import React, { useState } from 'react';
import { useMQ } from '../MascotQC';
import { DEFECT_CATS, DAILY_DEFECT_CATS, SECTIONS, DAILY_SECTIONS, todayStr } from '../constants/mascotConstants';

export default function DefectLogModal({ type }) {
    const { _fail, setFail, _dpFail, setDpFail, updateProject, operators: ctxOps } = useMQ();
    const OPERATORS = ctxOps?.length ? ctxOps : [];

    const isDaily = type === 'daily';
    const trigger = isDaily ? _dpFail : _fail;
    const closeFn = isDaily ? () => setDpFail(null) : () => setFail(null);
    const CATS    = isDaily ? DAILY_DEFECT_CATS : DEFECT_CATS;

    /* item label */
    const itemLabel = (() => {
        if (trigger?.itemLabel) return trigger.itemLabel;
        if (!trigger?.itemId) return trigger?.itemId || '—';
        const all = isDaily
            ? DAILY_SECTIONS.flatMap(s => s.items)
            : SECTIONS.flatMap(s => s.items);
        return all.find(i => i.id === trigger.itemId)?.l || String(trigger.itemId);
    })();

    const partLabel = trigger?.partName ? ` › ${trigger.op}: ${trigger.partName}` : '';

    const [form, setForm] = useState({
        defectCategory: CATS[0],
        severity: 'Major',
        note: '',
        assignedTo: '',
        targetDate: '',
        photos: [],
    });
    const f = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const addPhoto = (url) => setForm(p => ({ ...p, photos: [...p.photos, url] }));
    const removePhoto = (idx) => setForm(p => ({ ...p, photos: p.photos.filter((_, i) => i !== idx) }));

    const canSave = form.note.trim() && form.assignedTo && form.targetDate;

    const save = () => {
        if (!trigger?.projectId || !canSave) return;
        updateProject(trigger.projectId, proj => {
            const log = {
                id: Date.now() + '',
                defectCategory: form.defectCategory,
                itemName: itemLabel + (trigger.partName ? ` (${trigger.op}: ${trigger.partName})` : ''),
                failNote: form.note,
                severity: form.severity,
                failDate: new Date().toISOString(),
                reworkStatus: 'OPEN',
                reworkAssignedTo: form.assignedTo,
                targetDate: form.targetDate,
                reworkNote: '',
                photos: form.photos,
                origin: isDaily ? 'daily_progress' : 'finishing',
                source: isDaily ? 'daily_progress' : 'finishing',
                ...(isDaily ? { dailyDate: trigger.date } : {}),
            };
            proj.rejectLog = [log, ...(proj.rejectLog || [])];
        });
        closeFn();
    };

    if (!trigger) return null;

    return (
        <div className="mq-overlay" onClick={closeFn}>
            <div className="mq-mbox" style={{ maxWidth: 480 }} onClick={e => e.stopPropagation()}>
                <div className="mq-mbox-hd">
                    <span>Log Defect</span>
                    <button className="mq-close" onClick={closeFn}>✕</button>
                </div>

                <div style={{ marginBottom: 12, padding: '8px 10px', background: '#fef9c3', borderRadius: 8, border: '1px solid #fde68a' }}>
                    <div style={{ fontSize: 11, color: '#92400e', fontWeight: 600 }}>{itemLabel}{partLabel}</div>
                </div>

                <Row label="Defect Category">
                    <select className="mq-input" value={form.defectCategory}
                        onChange={e => f('defectCategory', e.target.value)}>
                        {CATS.map(c => <option key={c}>{c}</option>)}
                    </select>
                </Row>

                <Row label="Severity">
                    <div style={{ display: 'flex', gap: 6 }}>
                        {['Critical', 'Major'].map(s => (
                            <button key={s}
                                className={`mq-tab-pill${form.severity === s ? ' active' : ''}`}
                                style={{ fontSize: 12 }}
                                onClick={() => f('severity', s)}>
                                {s}
                            </button>
                        ))}
                    </div>
                </Row>

                <Row label="Defect Description *">
                    <textarea className="mq-input" rows={3} style={{ resize: 'vertical' }}
                        value={form.note}
                        placeholder="Describe the defect… (required)"
                        onChange={e => f('note', e.target.value)} />
                </Row>

                <Row label="Assign Rework To *">
                    <select className="mq-input" value={form.assignedTo}
                        onChange={e => f('assignedTo', e.target.value)}>
                        <option value="">— Select operator —</option>
                        {OPERATORS.map(o => <option key={o}>{o}</option>)}
                    </select>
                </Row>

                <Row label="Target Completion Date *">
                    <input className="mq-input" type="date" value={form.targetDate}
                        min={todayStr()}
                        onChange={e => f('targetDate', e.target.value)} />
                </Row>

                <Row label="Photo Evidence *">
                    <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', alignItems: 'center' }}>
                        {form.photos.map((url, i) => (
                            <div key={i} style={{ position: 'relative' }}>
                                <img src={url} alt="" style={{ width: 48, height: 48, objectFit: 'cover', borderRadius: 6, border: '1px solid #e2e8f0' }} />
                                <button onClick={() => removePhoto(i)}
                                    style={{ position: 'absolute', top: -4, right: -4, width: 16, height: 16, borderRadius: '50%', background: '#ef4444', color: '#fff', border: 'none', cursor: 'pointer', fontSize: 10, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>×</button>
                            </div>
                        ))}
                        <label style={{ width: 48, height: 48, border: '1.5px dashed #cbd5e1', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 20, color: '#94a3b8' }}>
                            +
                            <input type="file" accept="image/*" multiple hidden onChange={e => {
                                [...e.target.files].forEach(file => {
                                    const r = new FileReader();
                                    r.onload = ev => addPhoto(ev.target.result);
                                    r.readAsDataURL(file);
                                });
                                e.target.value = '';
                            }} />
                        </label>
                        {form.photos.length === 0 && (
                            <span style={{ fontSize: 11, color: '#f59e0b' }}>⚠️ min. 1 photo required</span>
                        )}
                    </div>
                </Row>

                <div style={{ display: 'flex', gap: 8, marginTop: 16, justifyContent: 'flex-end' }}>
                    <button className="mq-btn-ghost" onClick={closeFn}>Cancel</button>
                    <button className="mq-btn-primary" onClick={save}
                        disabled={!canSave || form.photos.length === 0}
                        style={{ opacity: (!canSave || form.photos.length === 0) ? 0.5 : 1 }}>
                        Save Defect
                    </button>
                </div>
            </div>
        </div>
    );
}

function Row({ label, children }) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4, marginBottom: 10 }}>
            <label style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>{label}</label>
            {children}
        </div>
    );
}
