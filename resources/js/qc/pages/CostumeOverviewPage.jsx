import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { getDashboard } from '../api/dashboard';
import { useNavigate } from 'react-router-dom';
import {
    Scissors, Layers, Plus, TrendingUp,
    Package, AlertTriangle, CheckCircle2, ArrowRight, Star, Shirt,
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
            <div style={{ width: 22, height: 22, border: '3px solid #e2e8f0', borderTopColor: '#0ea5e9', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
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

// ── Costume-specific constants & components ───────────────────────────────────

const COSTUME_STAGE = {
    cutting:  { label: 'Cutting / Pola', color: '#0ea5e9', bg: '#f0f9ff', Icon: Scissors },
    sewing:   { label: 'Sewing',         color: '#ec4899', bg: '#fdf2f8', Icon: Layers   },
    finishing:{ label: 'Finishing',      color: '#14b8a6', bg: '#f0fdfa', Icon: Shirt    },
};
const COSTUME_COLORS = ['#0ea5e9', '#ec4899', '#14b8a6', '#f43f5e', '#8b5cf6', '#f97316'];

function CostumeTimeline({ kpi }) {
    const stages = [
        { key: 'cutting',  ...COSTUME_STAGE.cutting,  pct: kpi.avg_cutting   ?? 0 },
        { key: 'sewing',   ...COSTUME_STAGE.sewing,   pct: kpi.avg_sewing    ?? 0 },
        { key: 'finishing',...COSTUME_STAGE.finishing, pct: kpi.avg_finishing ?? 0 },
    ];
    return (
        <div style={{ background: '#fff', borderRadius: 14, padding: '18px 20px', boxShadow: '0 1px 4px rgba(0,0,0,.06)', display: 'flex', flexDirection: 'column', gap: 14 }}>
            <div style={{ fontSize: 12, fontWeight: 700, color: '#334155' }}>Stage Progress (Rata-rata WIP)</div>
            {stages.map(({ key, label, color, bg, Icon, pct }, idx) => (
                <div key={key}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 7 }}>
                        <div style={{ width: 30, height: 30, borderRadius: 8, background: bg, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                            <Icon size={14} color={color} />
                        </div>
                        <span style={{ fontSize: 12, fontWeight: 600, color: '#334155', flex: 1 }}>{label}</span>
                        <span style={{ fontSize: 14, fontWeight: 800, color }}>{pct}%</span>
                    </div>
                    <div style={{ height: 7, background: '#f1f5f9', borderRadius: 999, overflow: 'hidden' }}>
                        <div style={{ height: '100%', width: `${pct}%`, background: `linear-gradient(90deg, ${color}aa, ${color})`, borderRadius: 999, transition: 'width .5s ease', minWidth: pct > 0 ? 8 : 0 }} />
                    </div>
                    {idx < stages.length - 1 && <div style={{ marginLeft: 14, marginTop: 4, width: 2, height: 8, background: '#f1f5f9' }} />}
                </div>
            ))}
        </div>
    );
}

function CostumeMiniStageBar({ sp }) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            {Object.entries(COSTUME_STAGE).map(([s, { color }]) => {
                const val = sp?.[s] ?? 0;
                return (
                    <div key={s} style={{ display: 'flex', alignItems: 'center', gap: 5 }}>
                        <div style={{ width: 52, height: 4, background: '#f1f5f9', borderRadius: 999, overflow: 'hidden', flexShrink: 0 }}>
                            <div style={{ height: '100%', width: `${val}%`, background: color, borderRadius: 999 }} />
                        </div>
                        <span style={{ fontSize: 9, color: val > 0 ? color : '#e2e8f0', fontWeight: 700, width: 22, textAlign: 'right' }}>{val}%</span>
                    </div>
                );
            })}
        </div>
    );
}

function CostumeDashboard({ data, onNew, nav }) {
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
                background: 'linear-gradient(135deg, #0284c7 0%, #be185d 100%)',
                padding: '20px 24px',
                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                boxShadow: '0 8px 24px rgba(2,132,199,.25)',
                flexWrap: 'wrap', gap: 12,
            }}>
                <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                        <Star size={18} color="#fde68a" />
                        <span style={{ fontSize: 18, fontWeight: 800, color: '#fff', letterSpacing: '-.02em' }}>QC Costume</span>
                        <span style={{ fontSize: 12, fontWeight: 500, color: 'rgba(255,255,255,.6)', marginLeft: 4 }}>Garment Quality Control</span>
                    </div>
                    <div style={{ fontSize: 12, color: 'rgba(255,255,255,.75)' }}>
                        {kpi.total_projects} projects · {kpi.wip} aktif · {kpi.closure_rate}% closure rate
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
                    onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,.28)'}
                    onMouseLeave={e => e.currentTarget.style.background = 'rgba(255,255,255,.15)'}
                >
                    <Plus size={15} /> New Project
                </button>
            </div>

            {/* KPI grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: 12 }}>
                <GradientKpiCard label="Total Projects" value={kpi.total_projects} sub={`${kpi.closure_rate}% closed`}                         gradient="linear-gradient(135deg,#0ea5e9,#6366f1)" icon={Package}      />
                <GradientKpiCard label="Aktif (WIP)"    value={kpi.wip}           sub={`${kpi.avg_progress}% progress`}                        gradient="linear-gradient(135deg,#ec4899,#f43f5e)" icon={TrendingUp}    />
                <GradientKpiCard label="Delivered"      value={kpi.delivered}                                                                   gradient="linear-gradient(135deg,#14b8a6,#10b981)" icon={CheckCircle2}  />
                <GradientKpiCard label="Open Defects"   value={kpi.active_defects} sub={`${kpi.total_defects} total · ${kpi.rejected} rejected`} gradient="linear-gradient(135deg,#f97316,#ef4444)" icon={AlertTriangle} />
            </div>

            {/* Stage timeline */}
            <CostumeTimeline kpi={kpi} />

            {/* Charts 2×2 */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 14 }}>

                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.07)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', marginBottom: 14 }}>Status Project</div>
                    {hasStatus ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <PieChart>
                                <Pie data={statusData} cx="50%" cy="46%" outerRadius={88} innerRadius={40} dataKey="value" paddingAngle={4}>
                                    {statusData.map((_, i) => <Cell key={i} fill={COSTUME_COLORS[i % COSTUME_COLORS.length]} />)}
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
                                <Tooltip cursor={{ fill: '#fdf2f8' }} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                                <Bar dataKey="value" fill="#ec4899" radius={[0, 5, 5, 0]} maxBarSize={18} />
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
                                <Line type="monotone" dataKey="count" name="Defects" stroke="#ec4899" strokeWidth={2.5} dot={{ r: 4, fill: '#ec4899' }} activeDot={{ r: 6 }} />
                            </LineChart>
                        </ResponsiveContainer>
                    ) : <EmptyChart height={260} />}
                </div>

                <div style={{ background: '#fff', borderRadius: 16, padding: '20px 22px', boxShadow: '0 2px 8px rgba(0,0,0,.07)' }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', marginBottom: 14 }}>Avg Progress per Stage</div>
                    <ResponsiveContainer width="100%" height={260}>
                        <BarChart
                            data={[
                                { name: 'Cutting',  value: kpi.avg_cutting   ?? 0 },
                                { name: 'Sewing',   value: kpi.avg_sewing    ?? 0 },
                                { name: 'Finishing',value: kpi.avg_finishing ?? 0 },
                            ]}
                            margin={{ left: -10, right: 8, top: 8, bottom: 4 }}
                        >
                            <XAxis dataKey="name" tick={{ fontSize: 12 }} tickLine={false} axisLine={false} />
                            <YAxis domain={[0, 100]} tickFormatter={v => `${v}%`} tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                            <Tooltip formatter={v => [`${v}%`, 'Avg Progress']} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                            <Bar dataKey="value" radius={[6, 6, 0, 0]} maxBarSize={54}>
                                {[{ fill: '#0ea5e9' }, { fill: '#ec4899' }, { fill: '#14b8a6' }].map((entry, i) => (
                                    <Cell key={i} fill={entry.fill} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Projects mini cards */}
            <div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                    <span style={{ fontSize: 13, fontWeight: 700, color: '#1e293b' }}>Recent Projects <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400 }}>({projects.length})</span></span>
                    <button onClick={() => nav('/projects')} style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 12, color: '#0ea5e9', fontWeight: 600, background: 'none', border: 'none', cursor: 'pointer', outline: 'none', padding: 0 }}>
                        Lihat semua <ArrowRight size={12} />
                    </button>
                </div>

                {projects.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '40px 0', color: '#94a3b8', fontSize: 13, background: '#fff', borderRadius: 14, border: '2px dashed #e2e8f0' }}>
                        Belum ada project.{' '}
                        <button onClick={onNew} style={{ color: '#0ea5e9', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}>Buat sekarang →</button>
                    </div>
                ) : (
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: 10 }}>
                        {projects.slice(0, 6).map(p => {
                            const sc = p.status;
                            return (
                                <div key={p.uid} onClick={() => nav(`/projects/${p.uid}`)}
                                    style={{ background: '#fff', borderRadius: 12, padding: '12px 14px', boxShadow: '0 1px 4px rgba(0,0,0,.06)', cursor: 'pointer', transition: 'all .15s', border: '1px solid #f1f5f9' }}
                                    onMouseEnter={e => { e.currentTarget.style.boxShadow = '0 4px 16px rgba(14,165,233,.15)'; e.currentTarget.style.borderColor = '#bae6fd'; }}
                                    onMouseLeave={e => { e.currentTarget.style.boxShadow = '0 1px 4px rgba(0,0,0,.06)';       e.currentTarget.style.borderColor = '#f1f5f9';  }}
                                >
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 8 }}>
                                        <div style={{ fontSize: 12, fontWeight: 700, color: '#1e293b', flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', paddingRight: 6 }}>{p.project_name}</div>
                                        <span style={{ fontSize: 9, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: statusBg(sc), color: statusColor(sc), whiteSpace: 'nowrap', flexShrink: 0 }}>{sc ?? 'Active'}</span>
                                    </div>
                                    <div style={{ fontSize: 10, color: '#94a3b8', marginBottom: 8 }}>{p.mascot_type} · {p.total_unit ?? '?'} units</div>
                                    <CostumeMiniStageBar sp={p.stage_progress} />
                                    {p.open_defects > 0 && (
                                        <div style={{ fontSize: 10, color: '#ef4444', fontWeight: 700, marginTop: 6, display: 'flex', alignItems: 'center', gap: 3 }}>
                                            <AlertTriangle size={9} /> {p.open_defects} open defect{p.open_defects > 1 ? 's' : ''}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}

// ── Entry ─────────────────────────────────────────────────────────────────────

export default function CostumeOverviewPage() {
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
            <CostumeDashboard data={data} onNew={() => setShowNew(true)} nav={nav} />
            {showNew && <NewJobOrderDialog onClose={() => setShowNew(false)} />}
        </>
    );
}
