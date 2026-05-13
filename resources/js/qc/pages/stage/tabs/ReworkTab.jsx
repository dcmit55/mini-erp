import React, { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getStageRejectLogs, updateStageRejectLog } from '../../../api/stageProduction';
import { STAGE_COLORS, DEFECT_CATEGORIES } from '../../../data/models';
import { Wrench, X, AlertTriangle, ChevronDown } from 'lucide-react';

const STATUS_CFG = {
    'OPEN':         { label: 'Open',        bg: '#fef2f2', text: '#dc2626' },
    'IN_REPAIR':    { label: 'In Repair',   bg: '#fff7ed', text: '#ea580c' },
    'REPAIRED-PQC': { label: 'PQC Check',  bg: '#eff6ff', text: '#2563eb' },
    'CLOSED':       { label: 'Closed',      bg: '#f0fdf4', text: '#16a34a' },
};

const SEV_CFG = {
    Critical: { bg: '#fef2f2', text: '#dc2626' },
    Major:    { bg: '#fff7ed', text: '#d97706' },
};

const inp = {
    width: '100%', border: '1px solid #e2e8f0', borderRadius: 8,
    padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box', background: '#fff',
};
const lbl = { fontSize: 11, fontWeight: 600, color: '#64748b', display: 'block', marginBottom: 4 };

function Badge({ cfg, children }) {
    return <span style={{ padding: '2px 8px', borderRadius: 999, fontSize: 11, fontWeight: 700, background: cfg.bg, color: cfg.text }}>{children}</span>;
}

// ── Edit Modal ────────────────────────────────────────────────────────

function EditModal({ log, projectUid, stage, onClose }) {
    const qc = useQueryClient();
    const [form, setForm] = useState({
        root_cause: '',
        corrective_action: '',
        rework_assigned_to: log.rework_assigned_to ?? '',
        target_completion_date: log.target_completion_date ?? '',
        rework_status: log.rework_status,
        note: '',
    });
    const [err, setErr] = useState(null);
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: () => updateStageRejectLog(projectUid, stage, log.uid, form),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, stage] });
            onClose();
        },
        onError: e => setErr(e.message),
    });

    const statusOptions = Object.entries(STATUS_CFG);

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, background: 'rgba(0,0,0,.45)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.18)', width: '100%', maxWidth: 480, maxHeight: '90vh', display: 'flex', flexDirection: 'column' }}>
                {/* Header */}
                <div style={{ padding: '14px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                    <div>
                        <div style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>{log.reject_id}</div>
                        <div style={{ fontSize: 11, color: '#94a3b8' }}>{log.item_name} · {log.defect_category}</div>
                    </div>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 4, outline: 'none', display: 'flex' }}>
                        <X size={16} />
                    </button>
                </div>

                <div style={{ padding: 18, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: 12 }}>
                    {err && <div style={{ fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '7px 10px' }}>{err}</div>}

                    <div>
                        <span style={lbl}>Root Cause</span>
                        <textarea rows={2} style={{ ...inp, resize: 'vertical' }}
                            value={form.root_cause} onChange={e => set('root_cause', e.target.value)}
                            placeholder="Describe root cause…" />
                    </div>
                    <div>
                        <span style={lbl}>Corrective Action</span>
                        <textarea rows={2} style={{ ...inp, resize: 'vertical' }}
                            value={form.corrective_action} onChange={e => set('corrective_action', e.target.value)}
                            placeholder="Corrective action to take…" />
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                        <div>
                            <span style={lbl}>Assigned To</span>
                            <input type="text" style={inp} value={form.rework_assigned_to}
                                onChange={e => set('rework_assigned_to', e.target.value)} placeholder="Operator name…" />
                        </div>
                        <div>
                            <span style={lbl}>Target Date</span>
                            <input type="date" style={inp} value={form.target_completion_date}
                                onChange={e => set('target_completion_date', e.target.value)} />
                        </div>
                    </div>
                    <div>
                        <span style={lbl}>Status</span>
                        <select style={inp} value={form.rework_status} onChange={e => set('rework_status', e.target.value)}>
                            {statusOptions.map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
                        </select>
                    </div>
                    <div>
                        <span style={lbl}>Note (for history)</span>
                        <input type="text" style={inp} value={form.note} onChange={e => set('note', e.target.value)} placeholder="Optional update note…" />
                    </div>

                    {/* Rework history */}
                    {log.rework_history?.length > 0 && (
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 8 }}>History</div>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                                {[...log.rework_history].reverse().map((h, i) => (
                                    <div key={i} style={{ fontSize: 11, color: '#64748b', background: '#f8fafc', borderRadius: 7, padding: '6px 10px' }}>
                                        <span style={{ fontWeight: 600, color: '#1e293b' }}>{h.event}</span>
                                        {h.new_status && <> → <span style={{ color: STATUS_CFG[h.new_status]?.text }}>{h.new_status}</span></>}
                                        {h.note && <span style={{ color: '#94a3b8' }}> — {h.note}</span>}
                                        <span style={{ float: 'right', color: '#cbd5e1' }}>{new Date(h.timestamp).toLocaleDateString()}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div style={{ padding: '12px 18px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8, flexShrink: 0 }}>
                    <button onClick={onClose} style={{ padding: '7px 14px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#64748b', fontSize: 13, cursor: 'pointer', outline: 'none' }}>Cancel</button>
                    <button onClick={() => mut.mutate()} disabled={mut.isPending}
                        style={{ padding: '7px 16px', borderRadius: 8, border: 'none', cursor: 'pointer', background: '#6366f1', color: '#fff', fontSize: 13, fontWeight: 600, outline: 'none', opacity: mut.isPending ? 0.6 : 1 }}>
                        {mut.isPending ? 'Saving…' : 'Save Changes'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Log Row ───────────────────────────────────────────────────────────

function LogRow({ log, onClick }) {
    const sc  = STATUS_CFG[log.rework_status] ?? STATUS_CFG.OPEN;
    const sev = SEV_CFG[log.severity] ?? SEV_CFG.Major;
    return (
        <tr onClick={onClick} style={{ cursor: 'pointer', borderTop: '1px solid #f1f5f9' }}
            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
            onMouseLeave={e => e.currentTarget.style.background = ''}>
            <td style={{ padding: '10px 12px', fontWeight: 600, color: '#6366f1', fontSize: 12, whiteSpace: 'nowrap' }}>{log.reject_id}</td>
            <td style={{ padding: '10px 12px', fontSize: 12, color: '#334155' }}>{log.item_name}</td>
            <td style={{ padding: '10px 12px', fontSize: 12, color: '#334155' }}>{log.defect_category}</td>
            <td style={{ padding: '10px 12px' }}><Badge cfg={sev}>{log.severity}</Badge></td>
            <td style={{ padding: '10px 12px', fontSize: 12, color: '#334155', textAlign: 'center' }}>{log.qty_fail ?? '—'}</td>
            <td style={{ padding: '10px 12px', fontSize: 11, color: '#64748b' }}>{log.fail_operator ?? '—'}</td>
            <td style={{ padding: '10px 12px', fontSize: 11, color: '#64748b' }}>{log.rework_assigned_to ?? '—'}</td>
            <td style={{ padding: '10px 12px', fontSize: 11, color: '#64748b', whiteSpace: 'nowrap' }}>{log.target_completion_date ?? '—'}</td>
            <td style={{ padding: '10px 12px' }}><Badge cfg={sc}>{sc.label}</Badge></td>
        </tr>
    );
}

const TH = ({ children }) => (
    <th style={{ padding: '9px 12px', textAlign: 'left', fontSize: 10, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '.04em', whiteSpace: 'nowrap' }}>{children}</th>
);

// ── Main ──────────────────────────────────────────────────────────────

export default function ReworkTab({ projectUid, stage }) {
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const { data: logs = [], isLoading } = useQuery({
        queryKey: ['stage-reject-logs', projectUid, stage],
        queryFn: () => getStageRejectLogs(projectUid, stage),
        staleTime: 30_000,
    });

    const [editing, setEditing] = useState(null);

    const active = logs.filter(l => l.rework_status !== 'CLOSED');
    const closed = logs.filter(l => l.rework_status === 'CLOSED');

    if (isLoading) return <div style={{ padding: '40px 0', textAlign: 'center', color: '#94a3b8' }}>Loading…</div>;

    if (logs.length === 0) return (
        <div style={{ textAlign: 'center', padding: '60px 0', color: '#94a3b8', fontSize: 13 }}>
            No reject logs for this stage.
        </div>
    );

    const Table = ({ rows }) => (
        <div style={{ overflowX: 'auto', background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                <thead style={{ background: '#f8fafc' }}>
                    <tr>
                        <TH>Reject ID</TH><TH>Part</TH><TH>Defect Category</TH><TH>Severity</TH>
                        <TH>Qty</TH><TH>Reported By</TH><TH>Assigned To</TH><TH>Target</TH><TH>Status</TH>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(l => <LogRow key={l.uid} log={l} onClick={() => setEditing(l)} />)}
                </tbody>
            </table>
        </div>
    );

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
            {/* Active */}
            <div>
                <div style={{ fontSize: 13, fontWeight: 700, color: color.text, marginBottom: 10, display: 'flex', alignItems: 'center', gap: 6 }}>
                    <AlertTriangle size={14} /> Active ({active.length})
                </div>
                {active.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '20px 0', color: '#94a3b8', fontSize: 13, background: '#fff', borderRadius: 12 }}>No active reject logs.</div>
                ) : <Table rows={active} />}
            </div>

            {/* Closed */}
            {closed.length > 0 && (
                <div>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#94a3b8', marginBottom: 10, display: 'flex', alignItems: 'center', gap: 6 }}>
                        <Wrench size={14} /> Closed ({closed.length})
                    </div>
                    <Table rows={closed} />
                </div>
            )}

            {editing && (
                <EditModal log={editing} projectUid={projectUid} stage={stage} onClose={() => setEditing(null)} />
            )}
        </div>
    );
}
