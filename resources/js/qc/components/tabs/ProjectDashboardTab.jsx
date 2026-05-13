import React from 'react';
import { useNavigate } from 'react-router-dom';
import { AlertTriangle, CheckCircle2, Clock, XCircle, Award } from 'lucide-react';

const statusColor = (s) =>
    s === 'CLOSED'       ? '#10b981' :
    s === 'REPAIRED-PQC' ? '#3b82f6' :
    s === 'IN_REPAIR'    ? '#f59e0b' : '#ef4444';

const statusLabel = (s) =>
    s === 'CLOSED'       ? 'Closed' :
    s === 'REPAIRED-PQC' ? 'Repaired (PQC)' :
    s === 'IN_REPAIR'    ? 'In Repair' : 'Open';

const severityColor = (s) => s === 'Critical' ? '#dc2626' : '#d97706';

function StatCard({ label, value, color }) {
    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '14px 16px', boxShadow: '0 1px 3px rgba(0,0,0,.06)', textAlign: 'center' }}>
            <div style={{ fontSize: 28, fontWeight: 700, color: color ?? '#1e293b' }}>{value}</div>
            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>{label}</div>
        </div>
    );
}

export default function ProjectDashboardTab({ project }) {
    const rejectLogs = project.reject_logs ?? [];
    const openLogs   = rejectLogs.filter(r => r.rework_status !== 'CLOSED');
    const closedLogs = rejectLogs.filter(r => r.rework_status === 'CLOSED');

    const dailyDone = (project.daily_progress ?? []).reduce((sum, dp) => {
        return sum + (dp.items ?? []).filter(i => i.is_finalized).length;
    }, 0);
    const totalDailyItems = (project.daily_progress ?? []).length * 16;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Stats */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(120px, 1fr))', gap: 10 }}>
                <StatCard label="Total Units"      value={project.total_unit}      color="#6366f1" />
                <StatCard label="Checklist Pass"   value={project.checklist_pass}  color="#10b981" />
                <StatCard label="Checklist Fail"   value={project.checklist_fail}  color="#ef4444" />
                <StatCard label="Open Defects"     value={openLogs.length}         color="#f59e0b" />
                <StatCard label="Closed Defects"   value={closedLogs.length}       color="#10b981" />
                <StatCard label="Packing Verified" value={project.packing_verified ? 'Yes' : 'No'} color={project.packing_verified ? '#10b981' : '#94a3b8'} />
            </div>

            {/* Project info */}
            <div style={{ background: '#fff', borderRadius: 12, padding: 16, boxShadow: '0 1px 3px rgba(0,0,0,.06)', display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))', gap: 12 }}>
                {[
                    { label: 'Type',            value: project.mascot_type },
                    { label: 'Inspection Date', value: project.inspection_date ?? '—' },
                    { label: 'Deadline',        value: project.deadline ?? '—' },
                    { label: 'Created By',      value: project.created_by ?? '—' },
                ].map(f => (
                    <div key={f.label}>
                        <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 600, marginBottom: 2 }}>{f.label}</div>
                        <div style={{ fontSize: 13, color: '#334155', fontWeight: 500 }}>{f.value}</div>
                    </div>
                ))}
            </div>

            {/* Active defects */}
            {openLogs.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                    <div style={{ padding: '12px 16px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', gap: 6 }}>
                        <AlertTriangle size={14} style={{ color: '#f59e0b' }} />
                        <span style={{ fontSize: 13, fontWeight: 600, color: '#334155' }}>Active Defects ({openLogs.length})</span>
                    </div>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc' }}>
                                    {['Item', 'Category', 'Severity', 'Status', 'Assigned To'].map(h => (
                                        <th key={h} style={{ padding: '7px 12px', textAlign: 'left', fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.04em' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {openLogs.map((r, i) => (
                                    <tr key={r.uid} style={{ borderTop: '1px solid #f1f5f9', background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                                        <td style={{ padding: '8px 12px', color: '#334155', fontWeight: 500 }}>{r.item_name}</td>
                                        <td style={{ padding: '8px 12px', color: '#64748b' }}>{r.defect_category}</td>
                                        <td style={{ padding: '8px 12px' }}>
                                            <span style={{ padding: '2px 6px', borderRadius: 999, fontSize: 10, fontWeight: 600, background: severityColor(r.severity) + '20', color: severityColor(r.severity) }}>
                                                {r.severity}
                                            </span>
                                        </td>
                                        <td style={{ padding: '8px 12px' }}>
                                            <span style={{ padding: '2px 6px', borderRadius: 999, fontSize: 10, fontWeight: 600, background: statusColor(r.rework_status) + '20', color: statusColor(r.rework_status) }}>
                                                {statusLabel(r.rework_status)}
                                            </span>
                                        </td>
                                        <td style={{ padding: '8px 12px', color: '#64748b' }}>{r.rework_assigned_to ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {openLogs.length === 0 && (
                <div style={{ background: '#f0fdf4', borderRadius: 12, padding: '16px 20px', display: 'flex', alignItems: 'center', gap: 8 }}>
                    <CheckCircle2 size={16} style={{ color: '#16a34a' }} />
                    <span style={{ fontSize: 13, color: '#15803d', fontWeight: 500 }}>No open defects — great work!</span>
                </div>
            )}

            {/* Final Decision Card */}
            <FinalDecisionCard fd={project.final_decision} />
        </div>
    );
}

// ── Final Decision Card (read-only) ───────────────────────────────────

function FinalDecisionCard({ fd }) {
    const nav = useNavigate();

    return (
        <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
            <div style={{ padding: '12px 16px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <Award size={15} style={{ color: '#6366f1' }} />
                    <span style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>Final Decision</span>
                </div>
                {!fd && (
                    <button onClick={() => nav('?tab=report')}
                        style={{ padding: '4px 12px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 11, fontWeight: 600, background: '#eef2ff', color: '#6366f1' }}>
                        Submit via Report Tab
                    </button>
                )}
            </div>

            {fd ? (
                <div style={{ padding: 16 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 12 }}>
                        <div style={{
                            display: 'inline-flex', alignItems: 'center', gap: 8,
                            padding: '8px 20px', borderRadius: 999, fontWeight: 800, fontSize: 18,
                            background: fd.result === 'PASS' ? '#f0fdf4' : '#fef2f2',
                            color: fd.result === 'PASS' ? '#16a34a' : '#dc2626',
                            letterSpacing: '.02em',
                        }}>
                            {fd.result === 'PASS'
                                ? <CheckCircle2 size={20} />
                                : <XCircle size={20} />}
                            {fd.result}
                        </div>
                        {fd.grade && (
                            <span style={{ padding: '6px 14px', borderRadius: 999, background: '#eef2ff', color: '#6366f1', fontWeight: 700, fontSize: 14 }}>
                                Grade {fd.grade}
                            </span>
                        )}
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))', gap: 10, fontSize: 12, marginBottom: fd.decision || fd.note ? 12 : 0 }}>
                        {fd.inspector && (
                            <div style={{ background: '#f8fafc', borderRadius: 8, padding: '8px 10px' }}>
                                <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 2 }}>QC Inspector</div>
                                <div style={{ fontWeight: 600, color: '#334155' }}>{fd.inspector}</div>
                            </div>
                        )}
                        {fd.manager && (
                            <div style={{ background: '#f8fafc', borderRadius: 8, padding: '8px 10px' }}>
                                <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 2 }}>Manager</div>
                                <div style={{ fontWeight: 600, color: '#334155' }}>{fd.manager}</div>
                            </div>
                        )}
                        {fd.ts && (
                            <div style={{ background: '#f8fafc', borderRadius: 8, padding: '8px 10px' }}>
                                <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 2 }}>Submitted</div>
                                <div style={{ fontWeight: 600, color: '#334155' }}>
                                    {new Date(fd.ts).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                                </div>
                            </div>
                        )}
                    </div>

                    {fd.decision && (
                        <div style={{ fontSize: 12, color: '#475569', background: '#f8fafc', borderRadius: 8, padding: '8px 10px', lineHeight: 1.6, marginBottom: fd.note ? 8 : 0 }}>
                            {fd.decision}
                        </div>
                    )}
                    {fd.note && (
                        <div style={{ fontSize: 11, color: '#94a3b8', fontStyle: 'italic' }}>{fd.note}</div>
                    )}
                </div>
            ) : (
                <div style={{ padding: '18px 16px', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6, color: '#94a3b8' }}>
                    <Clock size={24} style={{ color: '#cbd5e1' }} />
                    <span style={{ fontSize: 13 }}>Belum ada final decision.</span>
                    <span style={{ fontSize: 11 }}>Selesaikan semua inspeksi lalu submit dari tab <strong style={{ color: '#6366f1' }}>Report</strong>.</span>
                </div>
            )}
        </div>
    );
}
