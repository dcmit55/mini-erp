import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createRejectLog, updateRejectLog } from '../../api/rejectLogs';
import { AlertTriangle, Plus, ChevronDown, ChevronRight, Wrench, X } from 'lucide-react';

const STATUS_CFG = {
    'OPEN':          { label: 'Open',          bg: '#fef2f2', color: '#dc2626' },
    'IN_REPAIR':     { label: 'In Repair',     bg: '#fffbeb', color: '#d97706' },
    'REPAIRED-PQC':  { label: 'Repaired-PQC', bg: '#eff6ff', color: '#2563eb' },
    'CLOSED':        { label: 'Closed',        bg: '#f0fdf4', color: '#16a34a' },
};

const SEVERITY_CFG = {
    Critical: { bg: '#fef2f2', color: '#dc2626' },
    Major:    { bg: '#fff7ed', color: '#ea580c' },
};

const SOURCE_CFG = {
    finishing:      { label: 'Finishing',       bg: '#eef2ff', color: '#6366f1' },
    daily_progress: { label: 'Daily Progress',  bg: '#f0fdf4', color: '#16a34a' },
};

const DEFECT_CATS = ['Sewing', 'Fabric', 'Color', 'Shape', 'Accessories', 'Wearability', 'Finish', 'Other'];

const inputStyle = {
    width: '100%', border: '1px solid #e2e8f0', borderRadius: 8,
    padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box', background: '#fff',
};

const btnStyle = (bg, color = '#fff') => ({
    padding: '6px 12px', borderRadius: 8, border: 'none', cursor: 'pointer',
    background: bg, color, fontSize: 12, fontWeight: 600, outline: 'none',
    display: 'inline-flex', alignItems: 'center', gap: 5,
});

const Badge = ({ bg, color, children }) => (
    <span style={{ padding: '2px 8px', borderRadius: 999, fontSize: 11, fontWeight: 600, background: bg, color }}>
        {children}
    </span>
);

// ── New Log Dialog ────────────────────────────────────────────────────

function NewLogDialog({ projectUid, onClose }) {
    const qc = useQueryClient();
    const [form, setForm] = useState({
        source: 'finishing', item_name: '', defect_category: '', severity: 'Major',
        fail_note: '', fail_operator: '', rework_assigned_to: '', target_completion_date: '',
    });
    const [err, setErr] = useState(null);
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: () => createRejectLog(projectUid, form),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['project', projectUid] }); onClose(); },
        onError: e => setErr(e.message),
    });

    const canSubmit = form.item_name.trim() && form.defect_category && form.fail_note.trim();

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.4)', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.18)', width: '100%', maxWidth: 460, maxHeight: '90vh', display: 'flex', flexDirection: 'column' }}>
                <div style={{ padding: '14px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                    <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b', display: 'flex', alignItems: 'center', gap: 6 }}>
                        <AlertTriangle size={15} style={{ color: '#ef4444' }} /> Add Reject Log
                    </div>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 4, outline: 'none', display: 'flex' }}>
                        <X size={16} />
                    </button>
                </div>

                <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 12, overflowY: 'auto' }}>
                    {err && (
                        <div style={{ fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 10px' }}>{err}</div>
                    )}

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Source *</div>
                            <select style={inputStyle} value={form.source} onChange={e => set('source', e.target.value)}>
                                <option value="finishing">Finishing</option>
                                <option value="daily_progress">Daily Progress</option>
                            </select>
                        </div>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Severity *</div>
                            <div style={{ display: 'flex', gap: 14, paddingTop: 9 }}>
                                {['Critical', 'Major'].map(s => (
                                    <label key={s} style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 13, cursor: 'pointer' }}>
                                        <input type="radio" value={s} checked={form.severity === s}
                                            onChange={() => set('severity', s)} style={{ accentColor: '#6366f1' }} />
                                        <span style={{ color: SEVERITY_CFG[s].color, fontWeight: 500 }}>{s}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Item Name *</div>
                        <input type="text" style={inputStyle} placeholder="Which item/part failed?"
                            value={form.item_name} onChange={e => set('item_name', e.target.value)} />
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Defect Category *</div>
                        <select style={inputStyle} value={form.defect_category} onChange={e => set('defect_category', e.target.value)}>
                            <option value="">Select…</option>
                            {DEFECT_CATS.map(c => <option key={c} value={c}>{c}</option>)}
                        </select>
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Fail Note *</div>
                        <textarea rows={3} style={{ ...inputStyle, resize: 'none' }}
                            placeholder="Describe the defect…"
                            value={form.fail_note} onChange={e => set('fail_note', e.target.value)} />
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Found By</div>
                            <input type="text" style={inputStyle} placeholder="Operator name"
                                value={form.fail_operator} onChange={e => set('fail_operator', e.target.value)} />
                        </div>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Assigned To</div>
                            <input type="text" style={inputStyle} placeholder="Who will repair?"
                                value={form.rework_assigned_to} onChange={e => set('rework_assigned_to', e.target.value)} />
                        </div>
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Target Completion</div>
                        <input type="date" style={inputStyle}
                            value={form.target_completion_date} onChange={e => set('target_completion_date', e.target.value)} />
                    </div>
                </div>

                <div style={{ padding: '12px 18px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8, flexShrink: 0 }}>
                    <button onClick={onClose} style={btnStyle('#f1f5f9', '#475569')}>Cancel</button>
                    <button onClick={() => mut.mutate()} disabled={!canSubmit || mut.isPending}
                        style={{ ...btnStyle('#ef4444'), opacity: (!canSubmit || mut.isPending) ? 0.5 : 1 }}>
                        {mut.isPending ? 'Saving…' : 'Add Log'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Update Status Dialog ──────────────────────────────────────────────

function UpdateDialog({ log, projectUid, onClose }) {
    const qc = useQueryClient();
    const [form, setForm] = useState({
        rework_status: log.rework_status,
        operator: '',
        note: '',
        rework_assigned_to: log.rework_assigned_to ?? '',
        target_completion_date: log.target_completion_date ?? '',
    });
    const [err, setErr] = useState(null);
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: () => updateRejectLog(log.uid, form),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['project', projectUid] }); onClose(); },
        onError: e => setErr(e.message),
    });

    const canSubmit = form.operator.trim() && form.note.trim();

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.4)', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.18)', width: '100%', maxWidth: 420 }}>
                <div style={{ padding: '14px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', flex: 1 }}>
                        Update — {log.item_name}
                    </div>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 4, outline: 'none', display: 'flex', flexShrink: 0, marginLeft: 8 }}>
                        <X size={16} />
                    </button>
                </div>

                <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 12 }}>
                    {err && (
                        <div style={{ fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 10px' }}>{err}</div>
                    )}

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 8 }}>New Status *</div>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                            {Object.entries(STATUS_CFG).map(([s, c]) => (
                                <button key={s} onClick={() => set('rework_status', s)}
                                    style={{ padding: '5px 12px', borderRadius: 999, border: `2px solid ${form.rework_status === s ? c.color : '#e2e8f0'}`, cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, background: form.rework_status === s ? c.bg : '#fff', color: form.rework_status === s ? c.color : '#94a3b8', transition: 'all .12s' }}>
                                    {c.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Operator *</div>
                        <input type="text" style={inputStyle} placeholder="Your name"
                            value={form.operator} onChange={e => set('operator', e.target.value)} />
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Note *</div>
                        <textarea rows={3} style={{ ...inputStyle, resize: 'none' }}
                            placeholder="Update note…"
                            value={form.note} onChange={e => set('note', e.target.value)} />
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Assigned To</div>
                            <input type="text" style={inputStyle} placeholder="—"
                                value={form.rework_assigned_to} onChange={e => set('rework_assigned_to', e.target.value)} />
                        </div>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Target Completion</div>
                            <input type="date" style={inputStyle}
                                value={form.target_completion_date} onChange={e => set('target_completion_date', e.target.value)} />
                        </div>
                    </div>
                </div>

                <div style={{ padding: '12px 18px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                    <button onClick={onClose} style={btnStyle('#f1f5f9', '#475569')}>Cancel</button>
                    <button onClick={() => mut.mutate()} disabled={!canSubmit || mut.isPending}
                        style={{ ...btnStyle('#6366f1'), opacity: (!canSubmit || mut.isPending) ? 0.5 : 1 }}>
                        {mut.isPending ? 'Saving…' : 'Update'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Log Card ──────────────────────────────────────────────────────────

function LogCard({ log, projectUid }) {
    const [expanded, setExpanded] = useState(false);
    const [updateOpen, setUpdateOpen] = useState(false);

    const sCfg  = STATUS_CFG[log.rework_status] ?? STATUS_CFG.OPEN;
    const sevCfg = SEVERITY_CFG[log.severity] ?? SEVERITY_CFG.Major;
    const srcCfg = SOURCE_CFG[log.source] ?? SOURCE_CFG.finishing;
    const isClosed = log.rework_status === 'CLOSED';

    return (
        <div style={{ borderRadius: 12, border: '1px solid #f1f5f9', background: '#fff', overflow: 'hidden', opacity: isClosed ? 0.72 : 1 }}>
            <div style={{ padding: '12px 14px' }}>
                {/* Badges row */}
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap', marginBottom: 8 }}>
                    <Badge bg={srcCfg.bg} color={srcCfg.color}>{srcCfg.label}</Badge>
                    <Badge bg={sevCfg.bg} color={sevCfg.color}>{log.severity}</Badge>
                    <span style={{ flex: 1 }} />
                    <Badge bg={sCfg.bg} color={sCfg.color}>{sCfg.label}</Badge>
                </div>

                {/* Item name + category */}
                <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b', marginBottom: 2 }}>{log.item_name}</div>
                <div style={{ fontSize: 12, color: '#64748b', marginBottom: 8 }}>{log.defect_category}</div>

                {/* Fail note */}
                <div style={{ fontSize: 12, color: '#475569', background: '#f8fafc', borderRadius: 8, padding: '8px 10px', marginBottom: 8, lineHeight: 1.55 }}>
                    {log.fail_note}
                </div>

                {/* Meta */}
                <div style={{ display: 'flex', gap: 14, fontSize: 11, color: '#94a3b8', flexWrap: 'wrap' }}>
                    {log.fail_operator && (
                        <span>Found by: <strong style={{ color: '#64748b' }}>{log.fail_operator}</strong></span>
                    )}
                    {log.rework_assigned_to && (
                        <span>Assigned: <strong style={{ color: '#64748b' }}>{log.rework_assigned_to}</strong></span>
                    )}
                    {log.target_completion_date && (
                        <span>Target: <strong style={{ color: '#64748b' }}>{log.target_completion_date}</strong></span>
                    )}
                    {log.closed_date && (
                        <span>Closed: <strong style={{ color: '#16a34a' }}>{new Date(log.closed_date).toLocaleDateString('id-ID')}</strong></span>
                    )}
                </div>
            </div>

            {/* Footer */}
            <div style={{ padding: '8px 14px', borderTop: '1px solid #f8fafc', background: '#fafafa', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <button onClick={() => setExpanded(o => !o)}
                    style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 11, color: '#94a3b8', background: 'none', border: 'none', cursor: 'pointer', outline: 'none', padding: 0, fontWeight: 500 }}>
                    {expanded ? <ChevronDown size={12} /> : <ChevronRight size={12} />}
                    History ({log.rework_history?.length ?? 0})
                </button>
                {!isClosed && (
                    <button onClick={() => setUpdateOpen(true)} style={btnStyle('#6366f1')}>
                        <Wrench size={12} /> Update
                    </button>
                )}
            </div>

            {/* Rework history timeline */}
            {expanded && (
                <div style={{ padding: '10px 14px 14px', display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {(log.rework_history ?? []).map((h, i) => {
                        const hCfg = STATUS_CFG[h.new_status] ?? STATUS_CFG.OPEN;
                        return (
                            <div key={i} style={{ display: 'flex', gap: 10 }}>
                                <div style={{ width: 3, background: hCfg.color, borderRadius: 2, flexShrink: 0, marginTop: 3 }} />
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap', marginBottom: 3 }}>
                                        <Badge bg={hCfg.bg} color={hCfg.color}>{hCfg.label}</Badge>
                                        <span style={{ fontSize: 11, fontWeight: 500, color: '#475569' }}>{h.operator}</span>
                                        <span style={{ fontSize: 10, color: '#94a3b8', marginLeft: 'auto' }}>
                                            {new Date(h.timestamp).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })}
                                        </span>
                                    </div>
                                    <div style={{ fontSize: 12, color: '#64748b', lineHeight: 1.5 }}>{h.note}</div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {updateOpen && <UpdateDialog log={log} projectUid={projectUid} onClose={() => setUpdateOpen(false)} />}
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────

export default function RejectLogTab({ project }) {
    const [newOpen, setNewOpen] = useState(false);
    const logs = project.reject_logs ?? [];

    const openCount    = logs.filter(l => l.rework_status === 'OPEN').length;
    const inRepairCount = logs.filter(l => l.rework_status === 'IN_REPAIR').length;
    const closedCount  = logs.filter(l => l.rework_status === 'CLOSED').length;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {/* Summary row */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8 }}>
                <div style={{ display: 'flex', gap: 12, fontSize: 12 }}>
                    {openCount > 0 && <span style={{ color: '#dc2626', fontWeight: 600 }}>{openCount} Open</span>}
                    {inRepairCount > 0 && <span style={{ color: '#d97706', fontWeight: 600 }}>{inRepairCount} In Repair</span>}
                    {closedCount > 0 && <span style={{ color: '#16a34a', fontWeight: 600 }}>{closedCount} Closed</span>}
                    <span style={{ color: '#94a3b8' }}>{logs.length} Total</span>
                </div>
                <button onClick={() => setNewOpen(true)} style={btnStyle('#ef4444')}>
                    <Plus size={14} /> Add Log
                </button>
            </div>

            {/* List */}
            {logs.length === 0 ? (
                <div style={{ textAlign: 'center', color: '#94a3b8', padding: '48px 0', fontSize: 13 }}>
                    No reject logs yet.
                </div>
            ) : (
                logs.map(log => <LogCard key={log.uid} log={log} projectUid={project.uid} />)
            )}

            {newOpen && <NewLogDialog projectUid={project.uid} onClose={() => setNewOpen(false)} />}
        </div>
    );
}
