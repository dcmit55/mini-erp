import React, { useEffect, useRef, useMemo } from 'react';
import {
    Chart, ArcElement, BarElement, LineElement, PointElement, RadialLinearScale,
    CategoryScale, LinearScale, Tooltip, Legend, Filler,
} from 'chart.js';
import { useMQ } from '../MascotQC';
import { fmtDate, rStatus, rCat } from '../constants/mascotConstants';
import * as XLSX from 'xlsx';

Chart.register(ArcElement, BarElement, LineElement, PointElement, RadialLinearScale,
    CategoryScale, LinearScale, Tooltip, Legend, Filler);

/* ── canvas chart hook ── */
function useChart(id, type, data, options) {
    const ref = useRef(null);
    const chartRef = useRef(null);
    useEffect(() => {
        if (!ref.current) return;
        if (chartRef.current) chartRef.current.destroy();
        chartRef.current = new Chart(ref.current, { type, data, options });
        return () => { if (chartRef.current) chartRef.current.destroy(); };
    });
    return ref;
}

/* ── KPI card ── */
function KpiCard({ label, value, color, icon }) {
    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '14px 18px', boxShadow: '0 1px 4px rgba(0,0,0,.07)', display: 'flex', alignItems: 'center', gap: 14 }}>
            <div style={{ width: 40, height: 40, borderRadius: 10, background: color + '18', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20 }}>{icon}</div>
            <div>
                <div style={{ fontSize: 22, fontWeight: 800, color, lineHeight: 1 }}>{value}</div>
                <div style={{ fontSize: 11, color: '#64748b', marginTop: 2 }}>{label}</div>
            </div>
        </div>
    );
}

export default function MascotOverview() {
    const { projects, setView, openDetail, setModal } = useMQ();

    /* ── stats ── */
    const stats = useMemo(() => {
        const total     = projects.length;
        const wip       = projects.filter(p => p.status === 'WIP').length;
        const delivered = projects.filter(p => p.status === 'Delivered').length;
        const defects   = projects.flatMap(p => p.rejectLog || []);
        const active    = defects.filter(d => rStatus(d) === 'OPEN' || rStatus(d) === 'IN_PROGRESS').length;
        const closed    = defects.filter(d => rStatus(d) === 'CLOSED').length;
        const closureRate = defects.length > 0 ? Math.round(closed / defects.length * 100) : 0;
        const avgProgress = total > 0 ? Math.round(projects.reduce((s, p) => s + (p.progress || 0), 0) / total) : 0;
        return { total, wip, delivered, active, closureRate, avgProgress };
    }, [projects]);

    /* chart data */
    const statusData = useMemo(() => {
        const wip = projects.filter(p => p.status === 'WIP').length;
        const del = projects.filter(p => p.status === 'Delivered').length;
        const rej = projects.filter(p => p.status === 'Rejected').length;
        return { labels: ['WIP', 'Delivered', 'Rejected'], datasets: [{ data: [wip, del, rej], backgroundColor: ['#f59e0b', '#10b981', '#ef4444'], borderWidth: 0 }] };
    }, [projects]);

    const stackedBarData = useMemo(() => {
        const sliced = projects.slice(0, 8);
        return {
            labels: sliced.map(p => p.projectName.length > 12 ? p.projectName.slice(0, 12) + '…' : p.projectName),
            datasets: [
                { label: 'Pass',    data: sliced.map(p => Object.values(p.checklistItems || {}).filter(i => i.status === 'PASS').length), backgroundColor: '#10b981' },
                { label: 'Fail',    data: sliced.map(p => Object.values(p.checklistItems || {}).filter(i => i.status === 'FAIL').length), backgroundColor: '#ef4444' },
                { label: 'Pending', data: sliced.map(p => Object.values(p.checklistItems || {}).filter(i => !i.status).length), backgroundColor: '#e2e8f0' },
            ],
        };
    }, [projects]);

    const defectCatData = useMemo(() => {
        const map = {};
        projects.flatMap(p => p.rejectLog || []).forEach(d => { const c = rCat(d); map[c] = (map[c] || 0) + 1; });
        const sorted = Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, 6);
        return {
            labels: sorted.map(([k]) => k),
            datasets: [{ data: sorted.map(([, v]) => v), backgroundColor: '#ef4444', borderRadius: 4 }],
        };
    }, [projects]);

    const trendData = useMemo(() => {
        const monthMap = {};
        projects.flatMap(p => p.rejectLog || []).forEach(d => {
            const m = (d.failDate || d.createdAt || '').slice(0, 7);
            if (m) monthMap[m] = (monthMap[m] || 0) + 1;
        });
        const sorted = Object.entries(monthMap).sort((a, b) => a[0].localeCompare(b[0])).slice(-6);
        return {
            labels: sorted.map(([k]) => k),
            datasets: [{ label: 'Defects', data: sorted.map(([, v]) => v), borderColor: '#6366f1', backgroundColor: '#6366f110', fill: true, tension: 0.4, pointRadius: 4 }],
        };
    }, [projects]);

    const progressData = useMemo(() => {
        const sliced = projects.slice(0, 8);
        return {
            labels: sliced.map(p => p.projectName.length > 14 ? p.projectName.slice(0, 14) + '…' : p.projectName),
            datasets: [{ label: 'Progress %', data: sliced.map(p => p.progress || 0), backgroundColor: '#6366f1', borderRadius: 4 }],
        };
    }, [projects]);

    const radarData = useMemo(() => {
        const cats = ['Structural', 'Stitching', 'Wrapping', 'Painting', 'Assembly', 'Other'];
        const vals = cats.map(c => projects.flatMap(p => p.rejectLog || []).filter(d => rCat(d) === c).length);
        return {
            labels: cats,
            datasets: [{ label: 'Defects', data: vals, backgroundColor: '#6366f130', borderColor: '#6366f1', pointBackgroundColor: '#6366f1' }],
        };
    }, [projects]);

    /* canvas refs */
    const donutRef  = useChart('status-donut', 'doughnut', statusData, { plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } }, cutout: '55%' });
    const stackRef  = useChart('stacked-bar', 'bar', stackedBarData, { plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 10 } } }, scales: { x: { stacked: true, ticks: { font: { size: 9 } } }, y: { stacked: true, ticks: { font: { size: 9 } } } } });
    const catRef    = useChart('defect-cat', 'bar', defectCatData, { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 9 } } } } });
    const trendRef  = useChart('defect-trend', 'line', trendData, { plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 9 } } } } });
    const progRef   = useChart('proj-progress', 'bar', progressData, { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { max: 100, ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 9 } } } } });
    const radarRef  = useChart('defect-radar', 'radar', radarData, { plugins: { legend: { display: false } }, scales: { r: { ticks: { font: { size: 9 }, stepSize: 1 }, pointLabels: { font: { size: 9 } } } } });

    /* export excel */
    const exportExcel = () => {
        const rows = projects.map(p => ({
            'Job #':        p.jobNumber || '',
            'Project':      p.projectName,
            'Type':         p.mascotType,
            'Status':       p.status,
            'Progress %':   p.progress || 0,
            'Pass':         Object.values(p.checklistItems || {}).filter(i => i.status === 'PASS').length,
            'Fail':         Object.values(p.checklistItems || {}).filter(i => i.status === 'FAIL').length,
            'Open Defects': (p.rejectLog || []).filter(d => rStatus(d) === 'OPEN').length,
            'Deadline':     p.deadline || '',
            'Supervisor':   p.supervisor || '',
        }));
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Overview');
        XLSX.writeFile(wb, 'mascot-qc-overview.xlsx');
    };

    return (
        <div style={{ padding: '20px 24px', maxWidth: 1200 }}>
            {/* header */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 20, flexWrap: 'wrap', gap: 10 }}>
                <div>
                    <h2 style={{ margin: 0, fontSize: 20, fontWeight: 700, color: '#1e293b' }}>🧸 Mascot QC Overview</h2>
                    <p style={{ margin: '2px 0 0', fontSize: 13, color: '#64748b' }}>Quality control dashboard for all mascot projects</p>
                </div>
                <div style={{ display: 'flex', gap: 8 }}>
                    <button className="mq-btn-ghost" onClick={exportExcel}>⬇ Export Excel</button>
                    <button className="mq-btn-ghost" onClick={() => setView('jobs')}>View All Jobs →</button>
                    <button className="mq-btn-primary" onClick={() => setModal('newProject')}>+ New Project</button>
                </div>
            </div>

            {/* KPI cards */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(160px,1fr))', gap: 12, marginBottom: 24 }}>
                <KpiCard label="Total Projects" value={stats.total}        icon="🗂️" color="#6366f1" />
                <KpiCard label="In Progress"    value={stats.wip}          icon="⚙️" color="#f59e0b" />
                <KpiCard label="Delivered"      value={stats.delivered}    icon="📦" color="#10b981" />
                <KpiCard label="Active Defects" value={stats.active}       icon="⚠️" color="#ef4444" />
                <KpiCard label="Closure Rate"   value={`${stats.closureRate}%`} icon="✅" color="#06b6d4" />
                <KpiCard label="Avg Progress"   value={`${stats.avgProgress}%`} icon="📊" color="#8b5cf6" />
            </div>

            {/* charts row 1 */}
            {projects.length > 0 && (
                <>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 16, marginBottom: 16 }}>
                        <div className="mq-card">
                            <div className="mq-card-title">Status Distribution</div>
                            <canvas ref={donutRef} height={180} />
                        </div>
                        <div className="mq-card">
                            <div className="mq-card-title">Pass / Fail / Pending per Project</div>
                            <canvas ref={stackRef} height={160} />
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
                        <div className="mq-card">
                            <div className="mq-card-title">Top Defect Categories</div>
                            <canvas ref={catRef} height={180} />
                        </div>
                        <div className="mq-card">
                            <div className="mq-card-title">Monthly Defect Trend</div>
                            <canvas ref={trendRef} height={180} />
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 16, marginBottom: 16 }}>
                        <div className="mq-card">
                            <div className="mq-card-title">Progress per Project</div>
                            <canvas ref={progRef} height={180} />
                        </div>
                        <div className="mq-card">
                            <div className="mq-card-title">Defect Profile by Category</div>
                            <canvas ref={radarRef} height={180} />
                        </div>
                    </div>
                </>
            )}

            {/* project summary table */}
            <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 1px 4px rgba(0,0,0,.07)', overflow: 'hidden' }}>
                <div style={{ padding: '12px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>Projects Summary</span>
                    <span style={{ fontSize: 12, color: '#94a3b8' }}>{projects.length} total</span>
                </div>
                {projects.length === 0 ? (
                    <div style={{ padding: 32, textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>
                        No projects yet. <button className="mq-btn-ghost" style={{ fontSize: 13 }} onClick={() => setModal('newProject')}>Create one →</button>
                    </div>
                ) : (
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc' }}>
                                    {['Job #', 'Project', 'Type', 'Status', 'Progress', 'Pass', 'Fail', 'Open Defects', 'Deadline', 'Supervisor', ''].map(h => (
                                        <th key={h} style={{ padding: '8px 14px', textAlign: 'left', fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.04em', whiteSpace: 'nowrap' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {projects.map(p => {
                                    const pass = Object.values(p.checklistItems || {}).filter(i => i.status === 'PASS').length;
                                    const fail = Object.values(p.checklistItems || {}).filter(i => i.status === 'FAIL').length;
                                    const openDef = (p.rejectLog || []).filter(d => rStatus(d) === 'OPEN').length;
                                    const stColor = p.status === 'Delivered' ? '#10b981' : p.status === 'Rejected' ? '#ef4444' : '#f59e0b';
                                    return (
                                        <tr key={p.id} style={{ borderTop: '1px solid #f1f5f9', cursor: 'pointer' }}
                                            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                            onMouseLeave={e => e.currentTarget.style.background = '#fff'}
                                            onClick={() => openDetail(p.id)}>
                                            <td style={{ padding: '8px 14px', color: '#64748b' }}>{p.jobNumber || '—'}</td>
                                            <td style={{ padding: '8px 14px', fontWeight: 600, color: '#1e293b', maxWidth: 160, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{p.projectName}</td>
                                            <td style={{ padding: '8px 14px', color: '#64748b' }}>{p.mascotType}</td>
                                            <td style={{ padding: '8px 14px' }}>
                                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: stColor + '22', color: stColor }}>{p.status}</span>
                                            </td>
                                            <td style={{ padding: '8px 14px' }}>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                                                    <div style={{ width: 60, height: 5, background: '#e2e8f0', borderRadius: 999, overflow: 'hidden' }}>
                                                        <div style={{ height: '100%', width: `${p.progress || 0}%`, background: '#6366f1' }} />
                                                    </div>
                                                    <span style={{ color: '#64748b' }}>{p.progress || 0}%</span>
                                                </div>
                                            </td>
                                            <td style={{ padding: '8px 14px', color: '#10b981', fontWeight: 600 }}>{pass}</td>
                                            <td style={{ padding: '8px 14px', color: fail > 0 ? '#ef4444' : '#94a3b8', fontWeight: fail > 0 ? 600 : 400 }}>{fail}</td>
                                            <td style={{ padding: '8px 14px', color: openDef > 0 ? '#ef4444' : '#94a3b8', fontWeight: openDef > 0 ? 700 : 400 }}>{openDef}</td>
                                            <td style={{ padding: '8px 14px', color: '#64748b', whiteSpace: 'nowrap' }}>{fmtDate(p.deadline)}</td>
                                            <td style={{ padding: '8px 14px', color: '#64748b' }}>{p.supervisor || '—'}</td>
                                            <td style={{ padding: '8px 14px' }}>
                                                <button className="mq-btn-ghost" style={{ fontSize: 11, padding: '3px 8px' }}
                                                    onClick={e => { e.stopPropagation(); openDetail(p.id); }}>Open →</button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
