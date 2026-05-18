import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { submitFinalDecision } from '../../api/projects';
import {
    BarChart, Bar, PieChart, Pie, Cell,
    XAxis, YAxis, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import { Download, CheckCircle2, XCircle, FileText, Award, X } from 'lucide-react';
import * as XLSX from 'xlsx';

// ── Checklist section definitions (same order as ChecklistTab) ─────────
const CL_SECTIONS = [
    { id: 1,  name: 'Body & Structure',   items: [1,2,3,4,5] },
    { id: 2,  name: 'Head & Face',        items: [6,7,8] },
    { id: 3,  name: 'Fabric & Color',     items: [9,10,11,12,13] },
    { id: 4,  name: 'Accessories',        items: [14,15,16] },
    { id: 5,  name: 'Hands & Feet',       items: [17,18,19,20,21] },
    { id: 6,  name: 'Wearability',        items: [22,23,24] },
    { id: 7,  name: 'Harness & Comfort',  items: [25,26,27] },
    { id: 8,  name: 'Final Visual',       items: [28,29,30,31] },
    { id: 10, name: 'Tail & Extras',      items: [36,37,38,39] },
];

const COLORS = { pass: '#22c55e', fail: '#ef4444', pending: '#e2e8f0' };
const CHART_PALETTE = ['#6366f1','#f59e0b','#ef4444','#10b981','#3b82f6','#8b5cf6','#ec4899','#14b8a6'];

const REWORK_COLORS = {
    OPEN: '#ef4444', IN_REPAIR: '#f59e0b', 'REPAIRED-PQC': '#3b82f6', CLOSED: '#22c55e',
};

// ── Helpers ───────────────────────────────────────────────────────────

function buildChecklistChartData(checklistItems) {
    const map = {};
    (checklistItems ?? []).forEach(ci => { map[ci.item_id] = ci.status; });
    return CL_SECTIONS.map(sec => {
        const pass    = sec.items.filter(id => map[id] === 'PASS').length;
        const fail    = sec.items.filter(id => map[id] === 'FAIL').length;
        const pending = sec.items.length - pass - fail;
        return { name: sec.name.replace(' & ', ' &\n'), pass, fail, pending };
    });
}

function buildDefectCatData(rejectLogs) {
    const counts = {};
    (rejectLogs ?? []).forEach(r => {
        counts[r.defect_category] = (counts[r.defect_category] ?? 0) + 1;
    });
    return Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .map(([name, value]) => ({ name, value }));
}

function buildReworkStatusData(rejectLogs) {
    const counts = {};
    (rejectLogs ?? []).forEach(r => {
        counts[r.rework_status] = (counts[r.rework_status] ?? 0) + 1;
    });
    return Object.entries(counts).map(([name, value]) => ({ name, value }));
}

// ── Excel Export ──────────────────────────────────────────────────────

function exportExcel(project) {
    const wb = XLSX.utils.book_new();

    // Sheet 1: Summary
    const clTotal   = CL_SECTIONS.reduce((a, s) => a + s.items.length, 0);
    const clPass    = (project.checklist_items ?? []).filter(c => c.status === 'PASS').length;
    const clFail    = (project.checklist_items ?? []).filter(c => c.status === 'FAIL').length;
    const clPending = clTotal - clPass - clFail;
    const ws1 = XLSX.utils.aoa_to_sheet([
        ['QC Project Report'],
        ['Project', project.project_name],
        ['Job #', project.job_number],
        ['Type', project.mascot_type],
        ['Total Units', project.total_unit],
        ['Inspection Date', project.inspection_date ?? '—'],
        ['Deadline', project.deadline ?? '—'],
        ['Status', project.status],
        ['Progress', `${project.progress}%`],
        [],
        ['Checklist Summary'],
        ['Pass', clPass], ['Fail', clFail], ['Pending', clPending], ['Total', clTotal],
        ['Pass Rate', clTotal > 0 ? `${Math.round((clPass / clTotal) * 100)}%` : '—'],
        [],
        ['Defects Summary'],
        ['Total Defects', (project.reject_logs ?? []).filter(r => r.stage === 'finishing').length],
        ['Open Defects', (project.reject_logs ?? []).filter(r => r.stage === 'finishing' && r.rework_status === 'OPEN').length],
        ['Closed', (project.reject_logs ?? []).filter(r => r.stage === 'finishing' && r.rework_status === 'CLOSED').length],
    ]);
    XLSX.utils.book_append_sheet(wb, ws1, 'Summary');

    // Sheet 2: Checklist items
    const ws2 = XLSX.utils.aoa_to_sheet([
        ['Item ID', 'Section', 'Item Name', 'Status'],
        ...CL_SECTIONS.flatMap(sec =>
            sec.items.map(id => {
                const ci = (project.checklist_items ?? []).find(c => c.item_id === id);
                return [id, sec.name, `Item ${id}`, ci?.status ?? 'Pending'];
            })
        ),
    ]);
    XLSX.utils.book_append_sheet(wb, ws2, 'Checklist');

    // Sheet 3: Defect log
    const ws3 = XLSX.utils.aoa_to_sheet([
        ['Item', 'Category', 'Severity', 'Source', 'Fail Note', 'Operator', 'Assigned To', 'Target Date', 'Status', 'Closed Date'],
        ...(project.reject_logs ?? []).map(r => [
            r.item_name, r.defect_category, r.severity, r.source,
            r.fail_note, r.fail_operator ?? '', r.rework_assigned_to ?? '',
            r.target_completion_date ?? '', r.rework_status, r.closed_date ?? '',
        ]),
    ]);
    XLSX.utils.book_append_sheet(wb, ws3, 'Defect Log');

    // Sheet 4: Packing
    const ws4 = XLSX.utils.aoa_to_sheet([
        ['Item', 'Type', 'Checked'],
        ...(project.packing_items ?? [])
            .filter(pi => !pi.is_hidden)
            .map(pi => [pi.name, pi.type, pi.is_checked ? 'Yes' : 'No']),
    ]);
    XLSX.utils.book_append_sheet(wb, ws4, 'Packing');

    XLSX.writeFile(wb, `QC_Report_${project.job_number}_${project.project_name.replace(/\s+/g, '_')}.xlsx`);
}

// ── Stat card ─────────────────────────────────────────────────────────

function Stat({ label, value, color, bg }) {
    return (
        <div style={{ background: bg ?? '#f8fafc', borderRadius: 10, padding: '10px 14px' }}>
            <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 4 }}>{label}</div>
            <div style={{ fontSize: 22, fontWeight: 700, color: color ?? '#1e293b' }}>{value}</div>
        </div>
    );
}

// ── Final Decision ────────────────────────────────────────────────────

function FinalDecisionSection({ project }) {
    const qc = useQueryClient();
    const fd = project.final_decision;

    const [form, setForm] = useState({ result: 'PASS', grade: '', decision: '', inspector: '', manager: '', note: '' });
    const [open, setOpen] = useState(false);
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: () => submitFinalDecision(project.uid, form),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['project', project.uid] }); setOpen(false); },
    });

    const inputStyle = {
        width: '100%', border: '1px solid #e2e8f0', borderRadius: 8,
        padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box', background: '#fff',
    };

    if (fd) {
        const isPass = fd.result === 'PASS';
        return (
            <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                <div style={{ padding: '12px 16px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', gap: 8 }}>
                    <Award size={15} style={{ color: '#6366f1' }} />
                    <span style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>Final Decision</span>
                </div>
                <div style={{ padding: 16 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 12 }}>
                        <div style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '6px 16px', borderRadius: 999, fontWeight: 700, fontSize: 16, background: isPass ? '#f0fdf4' : '#fef2f2', color: isPass ? '#16a34a' : '#dc2626' }}>
                            {isPass ? <CheckCircle2 size={18} /> : <XCircle size={18} />}
                            {fd.result}
                        </div>
                        {fd.grade && (
                            <span style={{ padding: '4px 12px', borderRadius: 999, background: '#eef2ff', color: '#6366f1', fontWeight: 600, fontSize: 13 }}>
                                Grade {fd.grade}
                            </span>
                        )}
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, fontSize: 12 }}>
                        {fd.inspector && <div><span style={{ color: '#94a3b8' }}>Inspector: </span><strong>{fd.inspector}</strong></div>}
                        {fd.manager   && <div><span style={{ color: '#94a3b8' }}>Manager: </span><strong>{fd.manager}</strong></div>}
                        {fd.ts        && <div><span style={{ color: '#94a3b8' }}>Date: </span><strong>{new Date(fd.ts).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</strong></div>}
                    </div>
                    {fd.decision && (
                        <div style={{ marginTop: 10, fontSize: 12, color: '#475569', background: '#f8fafc', borderRadius: 8, padding: '8px 10px', lineHeight: 1.6 }}>
                            {fd.decision}
                        </div>
                    )}
                    {fd.note && (
                        <div style={{ marginTop: 8, fontSize: 12, color: '#94a3b8', fontStyle: 'italic' }}>{fd.note}</div>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
            <div style={{ padding: '12px 16px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <Award size={15} style={{ color: '#6366f1' }} />
                    <span style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>Final Decision</span>
                </div>
                {!open && (
                    <button onClick={() => setOpen(true)}
                        style={{ padding: '5px 14px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, background: '#6366f1', color: '#fff' }}>
                        Submit Decision
                    </button>
                )}
            </div>

            {open ? (
                <div style={{ padding: 16, display: 'flex', flexDirection: 'column', gap: 12 }}>
                    {/* Result */}
                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 8 }}>Result *</div>
                        <div style={{ display: 'flex', gap: 8 }}>
                            {['PASS', 'FAIL'].map(r => (
                                <button key={r} onClick={() => set('result', r)}
                                    style={{ flex: 1, padding: '10px', borderRadius: 10, border: `2px solid ${form.result === r ? (r === 'PASS' ? '#22c55e' : '#ef4444') : '#e2e8f0'}`, cursor: 'pointer', outline: 'none', fontSize: 14, fontWeight: 700, background: form.result === r ? (r === 'PASS' ? '#f0fdf4' : '#fef2f2') : '#fff', color: form.result === r ? (r === 'PASS' ? '#16a34a' : '#dc2626') : '#94a3b8', transition: 'all .15s' }}>
                                    {r === 'PASS' ? '✓ PASS' : '✗ FAIL'}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Grade</div>
                            <input type="text" style={inputStyle} placeholder="e.g. A, B, C"
                                value={form.grade} onChange={e => set('grade', e.target.value)} />
                        </div>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Inspector</div>
                            <input type="text" style={inputStyle} placeholder="Inspector name"
                                value={form.inspector} onChange={e => set('inspector', e.target.value)} />
                        </div>
                        <div>
                            <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Manager</div>
                            <input type="text" style={inputStyle} placeholder="Manager name"
                                value={form.manager} onChange={e => set('manager', e.target.value)} />
                        </div>
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Decision Note</div>
                        <textarea rows={3} style={{ ...inputStyle, resize: 'none' }}
                            placeholder="Formal decision statement…"
                            value={form.decision} onChange={e => set('decision', e.target.value)} />
                    </div>

                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Internal Note</div>
                        <input type="text" style={inputStyle} placeholder="Internal note (optional)"
                            value={form.note} onChange={e => set('note', e.target.value)} />
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                        <button onClick={() => setOpen(false)}
                            style={{ padding: '6px 14px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, background: '#f1f5f9', color: '#475569' }}>
                            Cancel
                        </button>
                        <button onClick={() => mut.mutate()} disabled={mut.isPending}
                            style={{ padding: '6px 16px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, background: form.result === 'PASS' ? '#16a34a' : '#dc2626', color: '#fff', opacity: mut.isPending ? 0.6 : 1 }}>
                            {mut.isPending ? 'Submitting…' : `Submit ${form.result}`}
                        </button>
                    </div>
                </div>
            ) : (
                <div style={{ padding: 16, color: '#94a3b8', fontSize: 13, textAlign: 'center' }}>
                    No final decision submitted yet. Complete all inspections before submitting.
                </div>
            )}
        </div>
    );
}

// ── Custom bar label ──────────────────────────────────────────────────

const StackedBarTooltip = ({ active, payload, label }) => {
    if (!active || !payload?.length) return null;
    return (
        <div style={{ background: '#fff', borderRadius: 8, boxShadow: '0 4px 12px rgba(0,0,0,.1)', padding: '8px 12px', fontSize: 12 }}>
            <div style={{ fontWeight: 600, color: '#334155', marginBottom: 4 }}>{label}</div>
            {payload.map((p, i) => (
                <div key={i} style={{ color: p.color, lineHeight: 1.6 }}>{p.name}: {p.value}</div>
            ))}
        </div>
    );
};

// ── Main ──────────────────────────────────────────────────────────────

export default function ProjectReportTab({ project }) {
    const clItems    = project.checklist_items ?? [];
    const rejectLogs = (project.reject_logs ?? []).filter(r => r.stage === 'finishing');
    const clTotal    = CL_SECTIONS.reduce((a, s) => a + s.items.length, 0);
    const clPass     = clItems.filter(c => c.status === 'PASS').length;
    const clFail     = clItems.filter(c => c.status === 'FAIL').length;
    const clPending  = clTotal - clPass - clFail;
    const clPassRate = clTotal > 0 ? Math.round((clPass / clTotal) * 100) : 0;

    const openDefects   = rejectLogs.filter(r => r.rework_status === 'OPEN').length;
    const closedDefects = rejectLogs.filter(r => r.rework_status === 'CLOSED').length;

    const clChartData       = buildChecklistChartData(clItems);
    const defectCatData     = buildDefectCatData(rejectLogs);
    const reworkStatusData  = buildReworkStatusData(rejectLogs);

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

            {/* Top bar: title + export */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <FileText size={15} style={{ color: '#6366f1' }} />
                    <span style={{ fontSize: 14, fontWeight: 600, color: '#1e293b' }}>Project Report</span>
                </div>
                <button onClick={() => exportExcel(project)}
                    style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '6px 14px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, background: '#f0fdf4', color: '#16a34a' }}>
                    <Download size={13} /> Export Excel
                </button>
            </div>

            {/* KPI row */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(120px, 1fr))', gap: 8 }}>
                <Stat label="Checklist Pass"  value={clPass}       color="#16a34a" bg="#f0fdf4" />
                <Stat label="Checklist Fail"  value={clFail}       color="#dc2626" bg="#fef2f2" />
                <Stat label="Pending"         value={clPending}    color="#64748b" bg="#f8fafc" />
                <Stat label="Pass Rate"       value={`${clPassRate}%`} color="#6366f1" bg="#eef2ff" />
                <Stat label="Total Defects"   value={rejectLogs.length} />
                <Stat label="Open Defects"    value={openDefects}  color="#f59e0b" bg="#fffbeb" />
                <Stat label="Closed Defects"  value={closedDefects} color="#16a34a" bg="#f0fdf4" />
                <Stat label="Progress"        value={`${project.progress}%`} color="#3b82f6" bg="#eff6ff" />
            </div>

            {/* Charts row */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 12 }}>

                {/* Checklist by section */}
                <div style={{ background: '#fff', borderRadius: 12, padding: 16, boxShadow: '0 1px 3px rgba(0,0,0,.06)', gridColumn: 'span 2' }}>
                    <div style={{ fontSize: 13, fontWeight: 600, color: '#334155', marginBottom: 12 }}>Checklist — by Section</div>
                    <ResponsiveContainer width="100%" height={200}>
                        <BarChart data={clChartData} layout="vertical" barSize={10}>
                            <XAxis type="number" tick={{ fontSize: 10 }} />
                            <YAxis type="category" dataKey="name" width={110} tick={{ fontSize: 10 }} />
                            <Tooltip content={<StackedBarTooltip />} />
                            <Legend iconSize={8} wrapperStyle={{ fontSize: 11 }} />
                            <Bar dataKey="pass"    name="Pass"    stackId="a" fill={COLORS.pass}    radius={[0,0,0,0]} />
                            <Bar dataKey="fail"    name="Fail"    stackId="a" fill={COLORS.fail}    radius={[0,0,0,0]} />
                            <Bar dataKey="pending" name="Pending" stackId="a" fill={COLORS.pending} radius={[0,3,3,0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                {/* Defect by category */}
                {defectCatData.length > 0 && (
                    <div style={{ background: '#fff', borderRadius: 12, padding: 16, boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
                        <div style={{ fontSize: 13, fontWeight: 600, color: '#334155', marginBottom: 12 }}>Defects by Category</div>
                        <ResponsiveContainer width="100%" height={180}>
                            <BarChart data={defectCatData} layout="vertical">
                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 10 }} />
                                <YAxis type="category" dataKey="name" width={70} tick={{ fontSize: 10 }} />
                                <Tooltip />
                                <Bar dataKey="value" fill="#f59e0b" radius={[0,3,3,0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                )}

                {/* Rework status */}
                {reworkStatusData.length > 0 && (
                    <div style={{ background: '#fff', borderRadius: 12, padding: 16, boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
                        <div style={{ fontSize: 13, fontWeight: 600, color: '#334155', marginBottom: 12 }}>Rework Status</div>
                        <ResponsiveContainer width="100%" height={180}>
                            <PieChart>
                                <Pie data={reworkStatusData} cx="50%" cy="50%" innerRadius={40} outerRadius={70} dataKey="value"
                                    label={({ name, value }) => `${name}: ${value}`} labelLine={false}>
                                    {reworkStatusData.map((entry, i) => (
                                        <Cell key={i} fill={REWORK_COLORS[entry.name] ?? CHART_PALETTE[i]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                )}
            </div>

            {/* Defect log table */}
            {rejectLogs.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                    <div style={{ padding: '12px 16px', borderBottom: '1px solid #f1f5f9', fontSize: 13, fontWeight: 600, color: '#1e293b' }}>
                        Defect Log — {rejectLogs.length} entries
                    </div>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc' }}>
                                    {['Item', 'Category', 'Severity', 'Operator', 'Status', 'Assigned To'].map(h => (
                                        <th key={h} style={{ padding: '8px 12px', textAlign: 'left', fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.04em', whiteSpace: 'nowrap' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {rejectLogs.map((r, i) => {
                                    const sColor = REWORK_COLORS[r.rework_status] ?? '#94a3b8';
                                    const sevColor = r.severity === 'Critical' ? '#dc2626' : '#d97706';
                                    return (
                                        <tr key={r.uid} style={{ borderTop: '1px solid #f1f5f9', background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                                            <td style={{ padding: '8px 12px', color: '#334155', fontWeight: 500 }}>{r.item_name}</td>
                                            <td style={{ padding: '8px 12px', color: '#64748b' }}>{r.defect_category}</td>
                                            <td style={{ padding: '8px 12px' }}>
                                                <span style={{ padding: '1px 7px', borderRadius: 999, fontSize: 10, fontWeight: 700, background: sevColor + '18', color: sevColor }}>{r.severity}</span>
                                            </td>
                                            <td style={{ padding: '8px 12px', color: '#64748b' }}>{r.fail_operator ?? '—'}</td>
                                            <td style={{ padding: '8px 12px' }}>
                                                <span style={{ padding: '1px 7px', borderRadius: 999, fontSize: 10, fontWeight: 700, background: sColor + '18', color: sColor }}>{r.rework_status}</span>
                                            </td>
                                            <td style={{ padding: '8px 12px', color: '#64748b' }}>{r.rework_assigned_to ?? '—'}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Final decision */}
            <FinalDecisionSection project={project} />
        </div>
    );
}
