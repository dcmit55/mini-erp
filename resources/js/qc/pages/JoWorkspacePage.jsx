import React, { useEffect, useMemo } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getProject } from '../api/projects';
import { getStageRecords, getStageRejectLogs } from '../api/stageProduction';
import { useApp } from '../context/AppContext';
import { STAGES, STAGE_LABELS, STAGE_COLORS } from '../data/models';
import {
    ArrowLeft, Scissors, Layers, CheckSquare, Check, Building2, FolderOpen, AlertTriangle,
} from 'lucide-react';
import {
    LineChart, Line, PieChart, Pie, Cell,
    XAxis, YAxis, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';

const STAGE_ICONS = { cutting: Scissors, sewing: Layers, finishing: CheckSquare };

const PIE_COLORS = ['#6366f1', '#f97316', '#10b981', '#ef4444', '#06b6d4', '#ec4899'];

// ── Stage state ───────────────────────────────────────────────────────

function getStageState(stage, idx, sp) {
    const pct = sp[stage] ?? 0;
    if (pct >= 100) return 'completed';
    if (pct > 0)    return 'active';
    return 'ready';
}

// ── Badges ────────────────────────────────────────────────────────────

function StatusBadge({ status }) {
    const map = {
        Delivered: { bg: '#d1fae5', text: '#065f46' },
        Rejected:  { bg: '#fee2e2', text: '#991b1b' },
        'On Hold': { bg: '#fef3c7', text: '#92400e' },
    };
    const c = map[status] ?? { bg: '#ede9fe', text: '#5b21b6' };
    return (
        <span style={{ padding: '3px 10px', borderRadius: 999, fontSize: 11, fontWeight: 700, background: c.bg, color: c.text, whiteSpace: 'nowrap' }}>
            {status ?? 'In Progress'}
        </span>
    );
}

// ── Stage Stepper ─────────────────────────────────────────────────────

function StageStepper({ stageProgress, activeStage, onStageClick }) {
    return (
        <div style={{ background: '#fff', borderRadius: 14, padding: '18px 22px', boxShadow: '0 1px 4px rgba(0,0,0,.07)' }}>
            <div style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 16 }}>
                Stages — klik untuk masuk
            </div>
            <div style={{ display: 'flex', alignItems: 'center' }}>
                {STAGES.map((stage, idx) => {
                    const state  = getStageState(stage, idx, stageProgress);
                    const color  = STAGE_COLORS[stage];
                    const Icon   = STAGE_ICONS[stage];
                    const pct    = stageProgress[stage] ?? 0;
                    const isLast = idx === STAGES.length - 1;
                    const isActive = activeStage === stage;

                    const circleStyle = {
                        width: 46, height: 46, borderRadius: '50%',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        flexShrink: 0, cursor: 'pointer', transition: 'all .15s',
                        ...(state === 'completed'
                            ? { background: '#dcfce7', border: '2px solid #22c55e' }
                            : isActive
                                ? { background: color.bg, border: `2px solid ${color.border}`, boxShadow: `0 0 0 4px ${color.border}28` }
                                : { background: '#f8fafc', border: `2px solid ${color.border}` }),
                    };

                    return (
                        <React.Fragment key={stage}>
                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8, flex: '0 0 auto' }}>
                                <div style={circleStyle} onClick={() => onStageClick(stage)}
                                    onMouseEnter={e => { if (!isActive) e.currentTarget.style.boxShadow = `0 0 0 4px ${color.border}18`; }}
                                    onMouseLeave={e => { if (!isActive && state !== 'completed') e.currentTarget.style.boxShadow = 'none'; }}>
                                    {state === 'completed'
                                        ? <Check size={20} color="#16a34a" strokeWidth={2.5} />
                                        : <Icon size={18} color={color.text} />}
                                </div>
                                <div style={{ textAlign: 'center', minWidth: 72 }}>
                                    <div style={{ fontSize: 12, fontWeight: 700, color: color.text }}>{STAGE_LABELS[stage]}</div>
                                    <div style={{ fontSize: 11, color: '#64748b', marginTop: 1 }}>
                                        {state === 'completed' ? 'Done' : `${pct}%`}
                                    </div>
                                    <div style={{ height: 3, background: '#e2e8f0', borderRadius: 999, marginTop: 4, overflow: 'hidden', width: 64 }}>
                                        <div style={{ height: '100%', width: `${pct}%`, background: state === 'completed' ? '#22c55e' : color.border, borderRadius: 999 }} />
                                    </div>
                                </div>
                            </div>
                            {!isLast && (
                                <div style={{ flex: 1, height: 2, margin: '0 8px', marginBottom: 32, borderRadius: 999, background: (stageProgress[stage] ?? 0) >= 100 ? '#22c55e' : '#e2e8f0' }} />
                            )}
                        </React.Fragment>
                    );
                })}
            </div>
        </div>
    );
}

// ── Stage Tab Bar ─────────────────────────────────────────────────────

function StageTabBar({ stageProgress, activeStage, onStageClick }) {
    return (
        <div style={{ display: 'flex', gap: 4, background: 'rgba(255,255,255,.9)', backdropFilter: 'blur(6px)', borderRadius: 12, padding: 4, boxShadow: '0 1px 3px rgba(0,0,0,.06)', border: '1px solid #e2e8f0' }}>
            {STAGES.map((stage, idx) => {
                const state    = getStageState(stage, idx, stageProgress);
                const color    = STAGE_COLORS[stage];
                const Icon     = STAGE_ICONS[stage];
                const isActive = activeStage === stage;
                return (
                    <button key={stage} onClick={() => onStageClick(stage)}
                        style={{
                            display: 'flex', alignItems: 'center', gap: 6,
                            padding: '8px 18px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none',
                            background: isActive ? color.bg : 'transparent',
                            color: isActive ? color.text : '#475569',
                            fontSize: 13, fontWeight: isActive ? 700 : 500,
                            transition: 'background .15s, color .15s',
                        }}
                        onMouseEnter={e => { if (!isActive) e.currentTarget.style.background = '#f8fafc'; }}
                        onMouseLeave={e => { if (!isActive) e.currentTarget.style.background = 'transparent'; }}>
                        <Icon size={14} />
                        {STAGE_LABELS[stage]}
                        {state === 'completed' && <Check size={12} color="#22c55e" />}
                    </button>
                );
            })}
        </div>
    );
}

// ── Project Dashboard (per-project overview) ──────────────────────────

function ProjectDashboard({ project, onStageClick }) {
    const uid = project.uid;

    // Load all 3 stages' data in parallel
    const stages = ['cutting', 'sewing', 'finishing'];
    const recordQueries = stages.map(s => useQuery({
        queryKey: ['stage-records', uid, s],
        queryFn:  () => getStageRecords(uid, s),
        staleTime: 60_000,
    }));
    const logQueries = stages.map(s => useQuery({
        queryKey: ['stage-reject-logs', uid, s],
        queryFn:  () => getStageRejectLogs(uid, s),
        staleTime: 60_000,
    }));

    const allRecords   = useMemo(() => recordQueries.flatMap(q => q.data ?? []), [recordQueries.map(q => q.data)]);
    const allLogs      = useMemo(() => logQueries.flatMap(q => q.data ?? []),    [logQueries.map(q => q.data)]);

    // Production trend by date (all stages combined)
    const trendData = useMemo(() => {
        const byDate = {};
        allRecords.forEach(r => {
            if (!r.date) return;
            if (!byDate[r.date]) byDate[r.date] = { date: r.date, produced: 0, pass: 0, fail: 0 };
            byDate[r.date].produced += r.qty_produced ?? 0;
            byDate[r.date].pass     += r.qty_pass     ?? 0;
            byDate[r.date].fail     += r.qty_fail      ?? 0;
        });
        return Object.values(byDate).sort((a, b) => a.date.localeCompare(b.date));
    }, [allRecords]);

    // Defect breakdown by category (all stages)
    const defectPieData = useMemo(() => {
        const cats = {};
        allLogs.forEach(l => {
            const c = l.defect_category || 'Other';
            cats[c] = (cats[c] ?? 0) + 1;
        });
        return Object.entries(cats).map(([name, value]) => ({ name, value }));
    }, [allLogs]);

    // Defects by stage
    const defectByStage = useMemo(() => stages.map((s, i) => ({
        stage: STAGE_LABELS[s],
        total: (logQueries[i].data ?? []).length,
        open:  (logQueries[i].data ?? []).filter(l => l.rework_status !== 'CLOSED').length,
        color: STAGE_COLORS[s],
    })), [logQueries.map(q => q.data)]);

    const totalProduced = useMemo(() => allRecords.reduce((s, r) => s + (r.qty_produced ?? 0), 0), [allRecords]);
    const totalPass     = useMemo(() => allRecords.reduce((s, r) => s + (r.qty_pass     ?? 0), 0), [allRecords]);
    const openDefects   = useMemo(() => allLogs.filter(l => l.stage === 'finishing' && l.rework_status !== 'CLOSED').length, [allLogs]);

    const sp = project.stage_progress ?? {};

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

            {/* KPI row */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(130px,1fr))', gap: 10 }}>
                {[
                    { label: 'Total Produced', value: totalProduced, color: '#6366f1' },
                    { label: 'Total Pass',     value: totalPass,     color: '#16a34a' },
                    { label: 'Total Units',    value: project.total_unit ?? 0, color: '#0ea5e9' },
                    { label: 'Open Defects',   value: openDefects,   color: openDefects > 0 ? '#ef4444' : '#94a3b8' },
                ].map(k => (
                    <div key={k.label} style={{ background: '#fff', borderRadius: 12, padding: '14px 16px', boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
                        <div style={{ fontSize: 10, color: '#94a3b8', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 4 }}>{k.label}</div>
                        <div style={{ fontSize: 24, fontWeight: 800, color: k.color }}>{k.value}</div>
                    </div>
                ))}
            </div>

            {/* Charts row */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(340px, 1fr))', gap: 16 }}>
                {/* Production trend */}
                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.08)', border: '1px solid #f1f5f9' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#334155', marginBottom: 16 }}>Production Trend</div>
                    {trendData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <LineChart data={trendData} margin={{ top: 6, right: 12, bottom: 4, left: -16 }}>
                                <XAxis dataKey="date" tick={{ fontSize: 10 }} tickLine={false} axisLine={false} />
                                <YAxis allowDecimals={false} tick={{ fontSize: 10 }} tickLine={false} axisLine={false} />
                                <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                                <Legend iconSize={10} wrapperStyle={{ fontSize: 11, paddingTop: 8 }} />
                                <Line type="monotone" dataKey="produced" name="Produced" stroke="#6366f1" strokeWidth={2.5} dot={{ r: 3 }} />
                                <Line type="monotone" dataKey="pass"     name="Pass"     stroke="#22c55e" strokeWidth={2.5} dot={{ r: 3 }} />
                                <Line type="monotone" dataKey="fail"     name="Fail"     stroke="#ef4444" strokeWidth={2.5} dot={{ r: 3 }} strokeDasharray="4 2" />
                            </LineChart>
                        </ResponsiveContainer>
                    ) : (
                        <div style={{ height: 260, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#cbd5e1', fontSize: 13 }}>Belum ada data produksi</div>
                    )}
                </div>

                {/* Defect pie */}
                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.08)', border: '1px solid #f1f5f9' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#334155', marginBottom: 16 }}>Defect by Category</div>
                    {defectPieData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <PieChart>
                                <Pie data={defectPieData} dataKey="value" nameKey="name" cx="50%" cy="45%" outerRadius={90} innerRadius={36} paddingAngle={3}>
                                    {defectPieData.map((_, i) => <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />)}
                                </Pie>
                                <Tooltip contentStyle={{ fontSize: 12 }} />
                                <Legend iconSize={10} wrapperStyle={{ fontSize: 11 }} />
                            </PieChart>
                        </ResponsiveContainer>
                    ) : (
                        <div style={{ height: 260, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#cbd5e1', fontSize: 13 }}>Belum ada defect</div>
                    )}
                </div>
            </div>

            {/* Defects by stage */}
            <div style={{ background: '#fff', borderRadius: 14, padding: '16px 18px', boxShadow: '0 1px 4px rgba(0,0,0,.06)' }}>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#334155', marginBottom: 12 }}>Defect per Stage</div>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                    {defectByStage.map(({ stage, total, open, color }) => (
                        <div key={stage} style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                            <div style={{ width: 64, fontSize: 12, fontWeight: 600, color: color.text, flexShrink: 0 }}>{stage}</div>
                            <div style={{ flex: 1, height: 8, background: '#f1f5f9', borderRadius: 999, overflow: 'hidden' }}>
                                <div style={{ height: '100%', width: total > 0 ? `${Math.min(100, (open / Math.max(total, 1)) * 100)}%` : '0%', background: color.border, borderRadius: 999 }} />
                            </div>
                            <div style={{ fontSize: 11, color: open > 0 ? '#ef4444' : '#94a3b8', fontWeight: 600, minWidth: 60, textAlign: 'right' }}>
                                {open} open / {total} total
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Recent defects table */}
            {allLogs.length > 0 && (
                <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 1px 4px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                    <div style={{ padding: '12px 18px', borderBottom: '1px solid #f1f5f9', fontSize: 13, fontWeight: 700, color: '#1e293b', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span>Defect Log <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400 }}>({allLogs.length})</span></span>
                        {openDefects > 0 && (
                            <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 11, color: '#ef4444', fontWeight: 700 }}>
                                <AlertTriangle size={12} /> {openDefects} open
                            </span>
                        )}
                    </div>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc' }}>
                                    {['Stage', 'Component', 'Category', 'Severity', 'Assigned To', 'Status'].map(h => (
                                        <th key={h} style={{ padding: '8px 14px', textAlign: 'left', fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', whiteSpace: 'nowrap' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {allLogs.slice(0, 20).map((l, i) => {
                                    const sc = l.rework_status === 'CLOSED'
                                        ? { bg: '#dcfce7', color: '#16a34a' }
                                        : l.rework_status === 'IN_REPAIR'
                                            ? { bg: '#fff7ed', color: '#ea580c' }
                                            : { bg: '#fee2e2', color: '#dc2626' };
                                    const sv = l.severity === 'Critical'
                                        ? { bg: '#fee2e2', color: '#dc2626' }
                                        : l.severity === 'Minor'
                                            ? { bg: '#fef9c3', color: '#854d0e' }
                                            : { bg: '#fff7ed', color: '#ea580c' };
                                    const stageColor = STAGE_COLORS[l.stage] ?? STAGE_COLORS.cutting;
                                    return (
                                        <tr key={l.uid ?? i} style={{ borderTop: '1px solid #f1f5f9' }}
                                            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                            onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                                            <td style={{ padding: '8px 14px' }}>
                                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: stageColor.bg, color: stageColor.text }}>{STAGE_LABELS[l.stage] ?? l.stage}</span>
                                            </td>
                                            <td style={{ padding: '8px 14px', color: '#334155', maxWidth: 150, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{l.item_name || '—'}</td>
                                            <td style={{ padding: '8px 14px', color: '#64748b', whiteSpace: 'nowrap' }}>{l.defect_category || '—'}</td>
                                            <td style={{ padding: '8px 14px' }}>
                                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: sv.bg, color: sv.color }}>{l.severity || '—'}</span>
                                            </td>
                                            <td style={{ padding: '8px 14px', color: '#64748b', whiteSpace: 'nowrap' }}>{l.rework_assigned_to || '—'}</td>
                                            <td style={{ padding: '8px 14px' }}>
                                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: sc.bg, color: sc.color }}>{l.rework_status || '—'}</span>
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

// ── Spinner ───────────────────────────────────────────────────────────

function Spinner() {
    return <div style={{ width: 22, height: 22, border: '3px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 1s linear infinite', marginRight: 10 }} />;
}

const iconBtn  = { background: '#f1f5f9', border: 'none', borderRadius: 8, padding: '8px 10px', cursor: 'pointer', display: 'flex', alignItems: 'center', color: '#475569', outline: 'none' };
const linkBtn  = { background: 'none', border: 'none', cursor: 'pointer', color: '#6366f1', fontSize: 13, padding: 0, outline: 'none' };

// ── Main Page ─────────────────────────────────────────────────────────

export default function JoWorkspacePage() {
    const { joUID }  = useParams();
    const navigate   = useNavigate();
    const location   = useLocation();
    const { setActiveJo, setActiveStage } = useApp();

    const { data: project, isLoading, isError } = useQuery({
        queryKey: ['project', joUID],
        queryFn: () => getProject(joUID),
    });

    useEffect(() => {
        if (joUID) setActiveJo(joUID);
        return () => setActiveJo(null);
    }, [joUID, setActiveJo]);

    const pathParts = location.pathname.split('/').filter(Boolean);
    const urlStage  = pathParts[2] ?? null;

    const handleStageClick = (stage) => {
        setActiveStage(stage);
        navigate(`/projects/${joUID}/${stage}/dashboard`);
    };

    if (isLoading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 240, color: '#94a3b8' }}>
            <Spinner /> Loading workspace…
        </div>
    );

    if (isError || !project) return (
        <div style={{ padding: 24, color: '#ef4444', background: '#fef2f2', borderRadius: 12 }}>
            Failed to load project. <button style={linkBtn} onClick={() => navigate('/projects')}>← Back</button>
        </div>
    );

    const sp = project.stage_progress ?? { cutting: 0, sewing: 0, finishing: 0 };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

            {/* ── Header ── */}
            <div style={{ background: '#fff', borderRadius: 14, padding: '16px 20px', boxShadow: '0 1px 4px rgba(0,0,0,.07)' }}>
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 12 }}>
                    <button onClick={() => navigate('/projects')} style={iconBtn}>
                        <ArrowLeft size={16} />
                    </button>
                    <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                            <h2 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#0f172a', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                {project.project_name}
                            </h2>
                            <StatusBadge status={project.status} />
                        </div>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, marginTop: 5 }}>
                            {project.actual_project_name && project.actual_project_name !== project.project_name && (
                                <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 11, color: '#6366f1', fontWeight: 600 }}>
                                    <FolderOpen size={12} /> {project.actual_project_name}
                                </span>
                            )}
                            {project.department_name && (
                                <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 11, color: '#64748b', fontWeight: 500 }}>
                                    <Building2 size={12} /> {project.department_name}
                                </span>
                            )}
                        </div>
                        <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 3 }}>
                            JO #{project.job_number ?? project.uid?.slice(0, 8)}
                            {' · '}{project.total_unit ?? '—'} units
                            {' · '}{project.mascot_type}
                            {project.deadline && <> · Due: <strong style={{ color: '#64748b' }}>{project.deadline}</strong></>}
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Stage Stepper ── */}
            <StageStepper stageProgress={sp} activeStage={urlStage} onStageClick={handleStageClick} />

            {/* ── Stage Tab Bar ── */}
            <StageTabBar stageProgress={sp} activeStage={urlStage} onStageClick={handleStageClick} />

            {/* ── Per-project Dashboard ── */}
            <ProjectDashboard project={project} onStageClick={handleStageClick} />
        </div>
    );
}
