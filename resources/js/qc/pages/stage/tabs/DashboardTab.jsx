import React, { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { getStageRecords, getStageRejectLogs } from '../../../api/stageProduction';
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { STAGE_COLORS } from '../../../data/models';

const PIE_COLORS = ['#ef4444', '#f97316', '#eab308', '#8b5cf6', '#06b6d4', '#10b981', '#6366f1', '#64748b'];

const STATUS_CHIP = {
    OPEN:         { bg: '#fee2e2', color: '#dc2626' },
    IN_REPAIR:    { bg: '#fff7ed', color: '#ea580c' },
    'REPAIRED-PQC': { bg: '#eff6ff', color: '#2563eb' },
    CLOSED:       { bg: '#dcfce7', color: '#16a34a' },
};
const SEV_CHIP = {
    Critical: { bg: '#fee2e2', color: '#dc2626' },
    Major:    { bg: '#fff7ed', color: '#ea580c' },
    Minor:    { bg: '#fef9c3', color: '#854d0e' },
};

function StatCard({ label, value, color, sub }) {
    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '16px 18px', boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
            <div style={{ fontSize: 10, color: '#94a3b8', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 6 }}>{label}</div>
            <div style={{ fontSize: 26, fontWeight: 700, color: color ?? '#1e293b' }}>{value}</div>
            {sub && <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>{sub}</div>}
        </div>
    );
}

export default function DashboardTab({ projectUid, stage }) {
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const { data: records = [], isLoading: rLoad } = useQuery({
        queryKey: ['stage-records', projectUid, stage],
        queryFn: () => getStageRecords(projectUid, stage),
        staleTime: 30_000,
    });

    const { data: rejectLogs = [], isLoading: lLoad } = useQuery({
        queryKey: ['stage-reject-logs', projectUid, stage],
        queryFn: () => getStageRejectLogs(projectUid, stage),
        staleTime: 30_000,
    });

    // ── Stats ─────────────────────────────────────────────────────────

    const totalProduced = useMemo(() => records.reduce((s, r) => s + (r.qty_produced ?? 0), 0), [records]);
    const totalPass     = useMemo(() => records.reduce((s, r) => s + (r.qty_pass ?? 0), 0), [records]);
    const totalFail     = useMemo(() => records.reduce((s, r) => s + (r.qty_fail ?? 0), 0), [records]);
    const openRework    = useMemo(() => rejectLogs.filter(l => l.rework_status !== 'CLOSED').length, [rejectLogs]);

    // ── Line chart: qty produced per date ─────────────────────────────

    const lineData = useMemo(() => {
        const byDate = {};
        records.forEach(r => {
            if (!r.date) return;
            if (!byDate[r.date]) byDate[r.date] = { date: r.date, produced: 0, pass: 0, fail: 0 };
            byDate[r.date].produced += r.qty_produced ?? 0;
            byDate[r.date].pass     += r.qty_pass ?? 0;
            byDate[r.date].fail     += r.qty_fail ?? 0;
        });
        return Object.values(byDate).sort((a, b) => a.date.localeCompare(b.date));
    }, [records]);

    // ── Pie chart: defect categories ──────────────────────────────────

    const pieData = useMemo(() => {
        const cats = {};
        rejectLogs.forEach(l => {
            const c = l.defect_category || 'Other';
            cats[c] = (cats[c] ?? 0) + 1;
        });
        return Object.entries(cats).map(([name, value]) => ({ name, value }));
    }, [rejectLogs]);

    if (rLoad || lLoad) return <LoadingSpinner />;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
            {/* Stats */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))', gap: 12 }}>
                <StatCard label="Total Produced" value={totalProduced} color={color.text} />
                <StatCard label="Total Passed"   value={totalPass}     color="#16a34a" />
                <StatCard label="Total Failed"   value={totalFail}     color={totalFail > 0 ? '#dc2626' : '#94a3b8'} />
                <StatCard label="Rework Open"    value={openRework}    color={openRework > 0 ? '#d97706' : '#94a3b8'} />
            </div>

            {/* Line Chart */}
            <div style={{ background: '#fff', borderRadius: 14, padding: '18px 20px', boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
                <div style={{ fontSize: 13, fontWeight: 600, color: '#1e293b', marginBottom: 16 }}>Daily Production</div>
                {lineData.length === 0 ? (
                    <EmptyChart label="No production records yet" />
                ) : (
                    <ResponsiveContainer width="100%" height={200}>
                        <LineChart data={lineData} margin={{ top: 4, right: 8, bottom: 0, left: -20 }}>
                            <XAxis dataKey="date" tick={{ fontSize: 10, fill: '#94a3b8' }} tickLine={false} axisLine={false} />
                            <YAxis tick={{ fontSize: 10, fill: '#94a3b8' }} tickLine={false} axisLine={false} />
                            <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #e2e8f0', fontSize: 12 }} />
                            <Line type="monotone" dataKey="produced" name="Produced" stroke={color.border} strokeWidth={2} dot={{ r: 3 }} />
                            <Line type="monotone" dataKey="pass"     name="Pass"     stroke="#22c55e" strokeWidth={2} dot={{ r: 3 }} />
                            <Line type="monotone" dataKey="fail"     name="Fail"     stroke="#ef4444" strokeWidth={2} dot={{ r: 3 }} strokeDasharray="4 2" />
                        </LineChart>
                    </ResponsiveContainer>
                )}
            </div>

            {/* Pie Chart */}
            {pieData.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 14, padding: '18px 20px', boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
                    <div style={{ fontSize: 13, fontWeight: 600, color: '#1e293b', marginBottom: 16 }}>Defect Breakdown</div>
                    <ResponsiveContainer width="100%" height={200}>
                        <PieChart>
                            <Pie data={pieData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={70} label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`} labelLine={false}>
                                {pieData.map((_, i) => <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />)}
                            </Pie>
                            <Legend iconSize={10} wrapperStyle={{ fontSize: 11 }} />
                            <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
            )}

            {/* Production Records Table */}
            {records.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                    <div style={{ padding: '13px 18px', borderBottom: '1px solid #f1f5f9', fontSize: 13, fontWeight: 600, color: '#1e293b' }}>
                        Production Records <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400 }}>({records.length})</span>
                    </div>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc' }}>
                                    {['Date', 'Operators', 'Produced', 'Pass', 'Fail', 'Status'].map(h => (
                                        <th key={h} style={{ padding: '7px 12px', textAlign: 'left', fontWeight: 700, fontSize: 10, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', whiteSpace: 'nowrap' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {records.map((r, i) => (
                                    <tr key={r.uid ?? i} style={{ borderTop: '1px solid #f1f5f9' }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                        onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                                        <td style={{ padding: '7px 12px', color: '#334155', whiteSpace: 'nowrap' }}>{r.date ?? '—'}</td>
                                        <td style={{ padding: '7px 12px', color: '#64748b', maxWidth: 160, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                            {Array.isArray(r.operators) && r.operators.length > 0 ? r.operators.join(', ') : '—'}
                                        </td>
                                        <td style={{ padding: '7px 12px', fontWeight: 600, color: color.text }}>{r.qty_produced ?? 0}</td>
                                        <td style={{ padding: '7px 12px', fontWeight: 600, color: '#16a34a' }}>{r.qty_pass ?? 0}</td>
                                        <td style={{ padding: '7px 12px', fontWeight: 600, color: (r.qty_fail ?? 0) > 0 ? '#dc2626' : '#94a3b8' }}>{r.qty_fail ?? 0}</td>
                                        <td style={{ padding: '7px 12px' }}>
                                            <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 8px', borderRadius: 999, background: r.is_finalized ? '#dcfce7' : '#f1f5f9', color: r.is_finalized ? '#16a34a' : '#64748b' }}>
                                                {r.is_finalized ? 'Finalized' : 'Draft'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Defect Log Table */}
            {rejectLogs.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                    <div style={{ padding: '13px 18px', borderBottom: '1px solid #f1f5f9', fontSize: 13, fontWeight: 600, color: '#1e293b' }}>
                        Defect Log <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400 }}>({rejectLogs.length})</span>
                    </div>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc' }}>
                                    {['Component / Part', 'Category', 'Severity', 'Qty', 'Assigned To', 'Status'].map(h => (
                                        <th key={h} style={{ padding: '7px 12px', textAlign: 'left', fontWeight: 700, fontSize: 10, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', whiteSpace: 'nowrap' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {rejectLogs.map((r, i) => {
                                    const sc = STATUS_CHIP[r.rework_status] ?? { bg: '#f1f5f9', color: '#64748b' };
                                    const sv = SEV_CHIP[r.severity] ?? { bg: '#f1f5f9', color: '#64748b' };
                                    return (
                                        <tr key={r.uid ?? i} style={{ borderTop: '1px solid #f1f5f9' }}
                                            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                            onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                                            <td style={{ padding: '7px 12px', color: '#334155', maxWidth: 160, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{r.item_name || '—'}</td>
                                            <td style={{ padding: '7px 12px', color: '#64748b', whiteSpace: 'nowrap' }}>{r.defect_category || '—'}</td>
                                            <td style={{ padding: '7px 12px' }}>
                                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: sv.bg, color: sv.color }}>{r.severity || '—'}</span>
                                            </td>
                                            <td style={{ padding: '7px 12px', color: '#334155', fontWeight: 600 }}>{r.qty_reject ?? '—'}</td>
                                            <td style={{ padding: '7px 12px', color: '#64748b', whiteSpace: 'nowrap' }}>{r.rework_assigned_to || '—'}</td>
                                            <td style={{ padding: '7px 12px' }}>
                                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: sc.bg, color: sc.color }}>{r.rework_status || '—'}</span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}

function LoadingSpinner() {
    return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 180, color: '#94a3b8', gap: 8 }}>
            <div style={{ width: 18, height: 18, border: '2px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
            Loading…
        </div>
    );
}

function EmptyChart({ label }) {
    return (
        <div style={{ height: 120, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#cbd5e1', fontSize: 13 }}>{label}</div>
    );
}
