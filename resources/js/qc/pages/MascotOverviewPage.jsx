import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { getDashboard } from '../api/dashboard';
import { useNavigate } from 'react-router-dom';
import {
    Layers, Scissors, CheckSquare, Plus, TrendingUp,
    Package, AlertTriangle, CheckCircle2, ArrowRight, Zap,
} from 'lucide-react';
import {
    BarChart, Bar, PieChart, Pie, Cell, LineChart, Line,
    XAxis, YAxis, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import NewJobOrderDialog from '../components/NewJobOrderDialog';

// ── Helpers ───────────────────────────────────────────────────────────────────

function Spinner() {
    return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 260, gap: 12, color: '#94a3b8' }}>
            <div style={{ width: 22, height: 22, border: '3px solid #e2e8f0', borderTopColor: '#7c3aed', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
            Loading…
        </div>
    );
}

function EmptyChart({ height = 180 }) {
    return (
        <div style={{ height, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 6 }}>
            <div style={{ width: 36, height: 36, borderRadius: '50%', border: '2px dashed #e2e8f0', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <TrendingUp size={16} color="#cbd5e1" />
            </div>
            <div style={{ fontSize: 11, color: '#cbd5e1', fontWeight: 500 }}>Belum ada data</div>
        </div>
    );
}

const statusColor = (s) =>
    s === 'Delivered' ? '#10b981' :
        s === 'Rejected' ? '#ef4444' :
            s === 'On Hold' ? '#f59e0b' : '#6366f1';

const statusBg = (s) =>
    s === 'Delivered' ? '#dcfce7' :
        s === 'Rejected' ? '#fee2e2' :
            s === 'On Hold' ? '#fef9c3' : '#eef2ff';

function GradientKpiCard({ label, value, sub, gradient, icon: Icon }) {
    return (
        <div style={{
            borderRadius: 14,
            padding: '22px 24px',
            background: gradient,
            position: 'relative',
            overflow: 'hidden',
            boxShadow: '0 4px 14px rgba(0,0,0,.13)',
            minHeight: '110px',
        }}>
            {Icon && <Icon size={42} color="rgba(255,255,255,0.15)" style={{ position: 'absolute', right: 12, bottom: 8 }} />}
            <div style={{ fontSize: 10, fontWeight: 700, color: 'rgba(255,255,255,.8)', textTransform: 'uppercase', letterSpacing: '.07em', marginBottom: 5 }}>{label}</div>
            <div style={{ fontSize: 32, fontWeight: 800, color: '#fff', lineHeight: 1.2 }}>{value}</div>
            {sub && <div style={{ fontSize: 12, color: 'rgba(255,255,255,.75)', marginTop: 6 }}>{sub}</div>}
        </div>
    );
}

// ── Mascot-specific constants & components ────────────────────────────────────
// Backend keys (cutting/sewing/finishing) are reused as storage keys;
// labels reflect the actual mascot production pipeline.
const MASCOT_STAGE = {
    cutting:  { label: 'Struktur & Material',  color: '#f97316', bg: '#fff7ed', Icon: Layers     },
    sewing:   { label: 'Wrapping & Surface',   color: '#8b5cf6', bg: '#f5f3ff', Icon: Scissors   },
    finishing:{ label: 'Assembly & Komponen',  color: '#06b6d4', bg: '#ecfeff', Icon: CheckSquare },
};
const MASCOT_COLORS = ['#7c3aed', '#f97316', '#10b981', '#ef4444', '#06b6d4', '#ec4899'];

function MascotStageCard({ stage, pct }) {
    const { label, color, bg, Icon } = MASCOT_STAGE[stage];
    return (
        <div style={{ background: bg, borderRadius: 12, padding: '14px 16px', border: `1.5px solid ${color}22` }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 10 }}>
                <div style={{ width: 32, height: 32, borderRadius: 9, background: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: `0 1px 4px ${color}22` }}>
                    <Icon size={15} color={color} />
                </div>
                <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#334155' }}>{label}</div>
                    <div style={{ fontSize: 10, color: '#94a3b8' }}>Rata-rata WIP</div>
                </div>
                <div style={{ fontSize: 18, fontWeight: 800, color }}>{pct}%</div>
            </div>
            <div style={{ height: 8, background: '#fff', borderRadius: 999, overflow: 'hidden' }}>
                <div style={{ height: '100%', width: `${pct}%`, background: color, borderRadius: 999, transition: 'width .5s ease', minWidth: pct > 0 ? 8 : 0 }} />
            </div>
        </div>
    );
}

function MascotMiniStageBar({ sp }) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            {Object.entries(MASCOT_STAGE).map(([s, { label, color }]) => {
                const val = sp?.[s] ?? 0;
                return (
                    <div key={s} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                        <div style={{ width: 48, height: 4, background: '#f1f5f9', borderRadius: 999, overflow: 'hidden', flexShrink: 0 }}>
                            <div style={{ height: '100%', width: `${val}%`, background: color, borderRadius: 999 }} />
                        </div>
                        <span style={{ fontSize: 9, color: val > 0 ? color : '#cbd5e1', fontWeight: 700, width: 20, textAlign: 'right' }}>{val}%</span>
                    </div>
                );
            })}
        </div>
    );
}

function MascotDashboard({ data, onNew, nav }) {
    const { kpi, charts, projects } = data;

    const statusData = Object.entries(charts.status_dist ?? {}).map(([name, value]) => ({ name, value })).filter(d => d.value > 0);
    const catData    = Object.entries(charts.defect_cats  ?? {}).map(([name, value]) => ({ name, value })).slice(0, 6);
    const trendData  = Object.entries(charts.monthly_trend ?? {}).map(([month, count]) => ({ month, count }));
    const hasStatus  = statusData.length > 0;
    const hasCat     = catData.length > 0;
    const hasTrend   = trendData.some(d => d.count > 0);

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

            {/* Header */}
            <div style={{
                borderRadius: 16,
                background: 'linear-gradient(135deg, #3730a3 0%, #7c3aed 60%, #9d174d 100%)',
                padding: '20px 24px',
                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                boxShadow: '0 8px 24px rgba(124,58,237,.3)',
                flexWrap: 'wrap', gap: 12,
            }}>
                <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                        <Zap size={18} color="#fbbf24" />
                        <span style={{ fontSize: 18, fontWeight: 800, color: '#fff', letterSpacing: '-.02em' }}>QC Mascot</span>
                        <span style={{ fontSize: 12, fontWeight: 500, color: 'rgba(255,255,255,.6)', marginLeft: 4 }}>Production Dashboard</span>
                    </div>
                    <div style={{ fontSize: 12, color: 'rgba(255,255,255,.7)' }}>
                        {kpi.total_projects} projects · {kpi.wip} aktif · Closure {kpi.closure_rate}%
                    </div>
                </div>
                <button onClick={onNew}
                    style={{
                        display: 'flex', alignItems: 'center', gap: 7,
                        padding: '9px 20px', borderRadius: 10, border: '1.5px solid rgba(255,255,255,.3)',
                        background: 'rgba(255,255,255,.15)', backdropFilter: 'blur(4px)',
                        color: '#fff', fontSize: 13, fontWeight: 600, cursor: 'pointer', outline: 'none',
                        transition: 'background .15s',
                    }}
                    onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,.25)'}
                    onMouseLeave={e => e.currentTarget.style.background = 'rgba(255,255,255,.15)'}
                >
                    <Plus size={15} /> New Project
                </button>
            </div>

            {/* KPI grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: 12 }}>
                <GradientKpiCard label="Total Projects" value={kpi.total_projects} sub={`${kpi.closure_rate}% closed`}   gradient="linear-gradient(135deg,#7c3aed,#6366f1)" icon={Package}      />
                <GradientKpiCard label="Aktif (WIP)"    value={kpi.wip}           sub={`${kpi.avg_progress}% progress`}  gradient="linear-gradient(135deg,#f97316,#ef4444)" icon={TrendingUp}    />
                <GradientKpiCard label="Delivered"      value={kpi.delivered}                                            gradient="linear-gradient(135deg,#10b981,#14b8a6)" icon={CheckCircle2}  />
                <GradientKpiCard label="Rejected"       value={kpi.rejected}                                             gradient="linear-gradient(135deg,#ef4444,#dc2626)" icon={AlertTriangle} />
                <GradientKpiCard label="Open Defects"   value={kpi.active_defects} sub={`${kpi.total_defects} total`}   gradient="linear-gradient(135deg,#f59e0b,#ea580c)" />
            </div>

            {/* Stage progress */}
            <div>
                <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.07em', marginBottom: 10 }}>Mascot Pipeline — Rata-rata Progress WIP</div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', gap: 10 }}>
                    <MascotStageCard stage="cutting"   pct={kpi.avg_cutting   ?? 0} />
                    <MascotStageCard stage="sewing"    pct={kpi.avg_sewing    ?? 0} />
                    <MascotStageCard stage="finishing" pct={kpi.avg_finishing ?? 0} />
                </div>
            </div>

            {/* Charts 2×2 */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 14 }}>

                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.07)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', marginBottom: 14 }}>Status Project</div>
                    {hasStatus ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <PieChart>
                                <Pie data={statusData} cx="50%" cy="46%" outerRadius={88} innerRadius={40} dataKey="value" paddingAngle={4}>
                                    {statusData.map((_, i) => <Cell key={i} fill={MASCOT_COLORS[i % MASCOT_COLORS.length]} />)}
                                </Pie>
                                <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                                <Legend iconType="circle" iconSize={9} wrapperStyle={{ fontSize: 12 }} />
                            </PieChart>
                        </ResponsiveContainer>
                    ) : <EmptyChart height={260} />}
                </div>

                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.07)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', marginBottom: 14 }}>Top Defect Categories</div>
                    {hasCat ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <BarChart data={catData} layout="vertical" margin={{ left: 4, right: 10, top: 4, bottom: 4 }}>
                                <XAxis type="number" tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                                <YAxis type="category" dataKey="name" width={100} tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                                <Tooltip cursor={{ fill: '#f5f3ff' }} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                                <Bar dataKey="value" fill="#7c3aed" radius={[0, 5, 5, 0]} maxBarSize={18} />
                            </BarChart>
                        </ResponsiveContainer>
                    ) : <EmptyChart height={260} />}
                </div>

                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.07)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', marginBottom: 14 }}>Monthly Defect Trend</div>
                    {hasTrend ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <LineChart data={trendData} margin={{ left: 0, right: 8, top: 8, bottom: 4 }}>
                                <XAxis dataKey="month" tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                                <YAxis allowDecimals={false} tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                                <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                                <Line type="monotone" dataKey="count" name="Defects" stroke="#7c3aed" strokeWidth={2.5} dot={{ r: 4, fill: '#7c3aed' }} activeDot={{ r: 6 }} />
                            </LineChart>
                        </ResponsiveContainer>
                    ) : <EmptyChart height={260} />}
                </div>

                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.07)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', marginBottom: 14 }}>Avg Progress per Pipeline</div>
                    <ResponsiveContainer width="100%" height={260}>
                        <BarChart
                            data={[
                                { name: 'Struktur',  value: kpi.avg_cutting   ?? 0 },
                                { name: 'Wrapping',  value: kpi.avg_sewing    ?? 0 },
                                { name: 'Assembly',  value: kpi.avg_finishing ?? 0 },
                            ]}
                            margin={{ left: -10, right: 8, top: 8, bottom: 4 }}
                        >
                            <XAxis dataKey="name" tick={{ fontSize: 12 }} tickLine={false} axisLine={false} />
                            <YAxis domain={[0, 100]} tickFormatter={v => `${v}%`} tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                            <Tooltip formatter={v => [`${v}%`, 'Avg Progress']} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                            <Bar dataKey="value" radius={[6, 6, 0, 0]} maxBarSize={54}>
                                {[{ fill: '#f97316' }, { fill: '#8b5cf6' }, { fill: '#06b6d4' }].map((entry, i) => (
                                    <Cell key={i} fill={entry.fill} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Projects table */}
            <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 1px 4px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                <div style={{ padding: '13px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontSize: 13, fontWeight: 700, color: '#1e293b' }}>Projects <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400 }}>({projects.length})</span></span>
                    <button onClick={() => nav('/projects')} style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 12, color: '#7c3aed', fontWeight: 600, background: 'none', border: 'none', cursor: 'pointer', outline: 'none', padding: 0 }}>
                        Lihat semua <ArrowRight size={12} />
                    </button>
                </div>
                <div style={{ overflowX: 'auto' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                        <thead>
                            <tr style={{ background: '#faf5ff' }}>
                                {['Project', 'Type', 'Status', 'Stage', 'Defects', 'Deadline'].map(h => (
                                    <th key={h} style={{ padding: '8px 14px', textAlign: 'left', fontSize: 9, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.07em', whiteSpace: 'nowrap' }}>{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {projects.map(p => (
                                <tr key={p.uid} onClick={() => nav(`/projects/${p.uid}`)}
                                    style={{ cursor: 'pointer', borderTop: '1px solid #f1f5f9', transition: 'background .1s' }}
                                    onMouseEnter={e => e.currentTarget.style.background = '#faf5ff'}
                                    onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                                    <td style={{ padding: '10px 14px' }}>
                                        <div style={{ fontWeight: 600, color: '#1e293b', maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{p.project_name}</div>
                                    </td>
                                    <td style={{ padding: '10px 14px', color: '#7c3aed', fontSize: 11, fontWeight: 600, whiteSpace: 'nowrap' }}>{p.mascot_type ?? '—'}</td>
                                    <td style={{ padding: '10px 14px' }}>
                                        <span style={{ padding: '2px 8px', borderRadius: 999, fontSize: 10, fontWeight: 700, background: statusBg(p.status), color: statusColor(p.status) }}>{p.status ?? 'Active'}</span>
                                    </td>
                                    <td style={{ padding: '10px 14px' }}><MascotMiniStageBar sp={p.stage_progress} /></td>
                                    <td style={{ padding: '10px 14px', color: p.open_defects > 0 ? '#ef4444' : '#94a3b8', fontWeight: p.open_defects > 0 ? 700 : 400, whiteSpace: 'nowrap' }}>{p.open_defects}/{p.total_defects}</td>
                                    <td style={{ padding: '10px 14px', color: '#64748b', fontSize: 12, whiteSpace: 'nowrap' }}>{p.deadline ?? '—'}</td>
                                </tr>
                            ))}
                            {projects.length === 0 && (
                                <tr><td colSpan={6} style={{ padding: '36px 14px', textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>
                                    Belum ada project. <button onClick={onNew} style={{ color: '#7c3aed', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}>Buat sekarang →</button>
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

// ── Entry ─────────────────────────────────────────────────────────────────────

export default function MascotOverviewPage() {
    const nav = useNavigate();
    const [showNew, setShowNew] = useState(false);

    const { data, isLoading, isError } = useQuery({ queryKey: ['dashboard'], queryFn: getDashboard });

    if (isLoading) return <Spinner />;
    if (isError) return (
        <div style={{ padding: 20, color: '#dc2626', background: '#fef2f2', borderRadius: 14, fontSize: 13, border: '1px solid #fca5a5' }}>
            Gagal memuat dashboard. Coba refresh halaman.
        </div>
    );

    return (
        <>
            <MascotDashboard data={data} onNew={() => setShowNew(true)} nav={nav} />
            {showNew && <NewJobOrderDialog onClose={() => setShowNew(false)} />}
        </>
    );
}
