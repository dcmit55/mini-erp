import React, { useState } from 'react';
import { useMQ } from '../MascotQC';
import { OPERATORS, fmtDt, rStatus, rCat, rItem, rNote, rAssigned, rSeverity } from '../constants/mascotConstants';

const STATUSES = ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];

export default function ReworkModal() {
    const { _rework, setRework, updateProject } = useMQ();

    const { projectId, defect } = _rework || {};

    const [form, setForm] = useState({
        status: rStatus(defect || {}),
        assignedTo: rAssigned(defect || {}) === '—' ? '' : rAssigned(defect || {}),
        note: '',
    });
    const f = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const save = () => {
        updateProject(projectId, proj => {
            const log = proj.rejectLog || [];
            const idx = log.findIndex(d => d.id === defect.id);
            if (idx < 0) return;
            const entry = { ...log[idx] };
            entry.reworkStatus    = form.status;
            entry.reworkAssignedTo = form.assignedTo;
            if (!entry.reworkHistory) entry.reworkHistory = [];
            if (form.note.trim()) {
                entry.reworkHistory.push({
                    status: form.status,
                    note:   form.note.trim(),
                    by:     form.assignedTo || '—',
                    at:     new Date().toISOString(),
                });
            }
            log[idx] = entry;
            proj.rejectLog = log;
        });
        setRework(null);
    };

    if (!defect) return null;

    const history = defect.reworkHistory || [];

    return (
        <div className="mq-overlay" onClick={() => setRework(null)}>
            <div className="mq-mbox" style={{ maxWidth: 480 }} onClick={e => e.stopPropagation()}>
                <div className="mq-mbox-hd">
                    <span>Rework Update</span>
                    <button className="mq-close" onClick={() => setRework(null)}>✕</button>
                </div>

                {/* defect info */}
                <div className="mq-card" style={{ marginBottom: 14, background: '#f8fafc' }}>
                    <div style={{ fontSize: 12, color: '#94a3b8', marginBottom: 2 }}>{rCat(defect)} · {rSeverity(defect)}</div>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b' }}>{rItem(defect)}</div>
                    <div style={{ fontSize: 12, color: '#64748b', marginTop: 2 }}>{rNote(defect)}</div>
                </div>

                <Row label="New Status">
                    <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                        {STATUSES.map(s => (
                            <button key={s}
                                className={`mq-tab-pill${form.status === s ? ' active' : ''}`}
                                style={{ fontSize: 11 }}
                                onClick={() => f('status', s)}>
                                {s}
                            </button>
                        ))}
                    </div>
                </Row>

                <Row label="Assigned To">
                    <select className="mq-input" value={form.assignedTo}
                        onChange={e => f('assignedTo', e.target.value)}>
                        <option value="">— Not assigned —</option>
                        {OPERATORS.map(o => <option key={o}>{o}</option>)}
                    </select>
                </Row>

                <Row label="Note (optional)">
                    <textarea className="mq-input" rows={2} style={{ resize: 'vertical' }}
                        value={form.note}
                        placeholder="What was done / what needs to be done…"
                        onChange={e => f('note', e.target.value)} />
                </Row>

                {/* history */}
                {history.length > 0 && (
                    <div style={{ marginBottom: 14 }}>
                        <div style={{ fontSize: 12, fontWeight: 600, color: '#64748b', marginBottom: 6 }}>History</div>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, maxHeight: 160, overflowY: 'auto' }}>
                            {history.map((h, i) => (
                                <div key={i} style={{ fontSize: 12, padding: '6px 10px', background: '#f1f5f9', borderRadius: 7 }}>
                                    <div style={{ display: 'flex', gap: 6, marginBottom: 2 }}>
                                        <span className="mq-badge-neutral" style={{ fontSize: 10 }}>{h.status}</span>
                                        <span style={{ color: '#94a3b8' }}>{h.by}</span>
                                        <span style={{ color: '#94a3b8', marginLeft: 'auto' }}>{fmtDt(h.at)}</span>
                                    </div>
                                    {h.note && <div style={{ color: '#475569' }}>{h.note}</div>}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
                    <button className="mq-btn-ghost" onClick={() => setRework(null)}>Cancel</button>
                    <button className="mq-btn-primary" onClick={save}>Save</button>
                </div>
            </div>
        </div>
    );
}

function Row({ label, children }) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4, marginBottom: 12 }}>
            <label style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>{label}</label>
            {children}
        </div>
    );
}
