import React, { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { getProjects, deleteProject } from '../api/projects';
import { STAGES, STAGE_LABELS, STAGE_COLORS } from '../data/models';
import { Plus, Trash2, Search, X, Filter, ChevronDown, Layers, AlertTriangle } from 'lucide-react';
import { useApp } from '../context/AppContext';
import { useLocalStorage } from '../hooks/useLocalStorage';
import NewJobOrderDialog from '../components/NewJobOrderDialog';

// ── Status helpers ────────────────────────────────────────────────────────────

const STATUS_OPTIONS = ['In Progress', 'Delivered', 'Rejected', 'On Hold'];

const STATUS_STYLE = {
    Delivered:   { bg: '#dcfce7', text: '#166534' },
    Rejected:    { bg: '#fee2e2', text: '#991b1b' },
    'On Hold':   { bg: '#fef9c3', text: '#713f12' },
    'In Progress':{ bg: '#ede9fe', text: '#5b21b6' },
};
const getStatusStyle = (s) => STATUS_STYLE[s] ?? STATUS_STYLE['In Progress'];

// ── Stage mini bars ───────────────────────────────────────────────────────────

function StageProgressRow({ stage, value = 0 }) {
    const color = STAGE_COLORS[stage];
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
            <span style={{ fontSize: 9, fontWeight: 700, color: color.text, width: 44, flexShrink: 0, textTransform: 'uppercase', letterSpacing: '.4px' }}>
                {STAGE_LABELS[stage]}
            </span>
            <div style={{ flex: 1, height: 5, background: '#f1f5f9', borderRadius: 999, overflow: 'hidden' }}>
                <div style={{ height: '100%', width: `${value}%`, background: color.border, borderRadius: 999, transition: 'width .4s ease' }} />
            </div>
            <span style={{ fontSize: 10, color: value > 0 ? color.text : '#cbd5e1', fontWeight: 700, width: 24, textAlign: 'right', flexShrink: 0 }}>
                {value}%
            </span>
        </div>
    );
}

// ── Project Card ──────────────────────────────────────────────────────────────

function ProjectCard({ p, onNavigate, onDelete }) {
    const sp  = p.stage_progress ?? {};
    const sc  = getStatusStyle(p.status);
    const uid = p.uid?.slice(0, 8).toUpperCase();

    return (
        <div
            onClick={() => onNavigate(p.uid)}
            style={{
                background: '#fff', borderRadius: 16,
                boxShadow: '0 1px 4px rgba(0,0,0,.07)',
                border: '1px solid rgba(226,232,240,.7)',
                overflow: 'hidden', cursor: 'pointer',
                transition: 'box-shadow .18s, transform .18s',
                display: 'flex', flexDirection: 'column',
            }}
            onMouseEnter={e => { e.currentTarget.style.boxShadow = '0 6px 20px rgba(0,0,0,.12)'; e.currentTarget.style.transform = 'translateY(-1px)'; }}
            onMouseLeave={e => { e.currentTarget.style.boxShadow = '0 1px 4px rgba(0,0,0,.07)'; e.currentTarget.style.transform = 'none'; }}
        >
            {/* Cover gradient */}
            <div style={{ height: 72, background: p.cover_gradient ?? 'linear-gradient(135deg,#667eea,#764ba2)', position: 'relative', flexShrink: 0 }}>
                {p.cover_image_url
                    ? <img src={p.cover_image_url} style={{ width: '100%', height: '100%', objectFit: 'cover' }} alt="" />
                    : <span style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, fontWeight: 800, color: 'rgba(255,255,255,.75)' }}>
                        {p.project_name?.[0] ?? 'Q'}
                      </span>
                }
                {/* UID chip */}
                <span style={{ position: 'absolute', top: 8, left: 10, background: 'rgba(0,0,0,.32)', backdropFilter: 'blur(4px)', color: '#fff', fontSize: 9, fontWeight: 800, padding: '2px 7px', borderRadius: 6, letterSpacing: '.6px' }}>
                    #{uid}
                </span>
                {/* Status badge */}
                <span style={{ position: 'absolute', top: 8, right: 10, background: sc.bg, color: sc.text, fontSize: 9, fontWeight: 800, padding: '2px 8px', borderRadius: 999 }}>
                    {p.status ?? 'Active'}
                </span>
            </div>

            {/* Body */}
            <div style={{ padding: '12px 14px', flex: 1, display: 'flex', flexDirection: 'column', gap: 10 }}>
                <div>
                    <div style={{ fontWeight: 700, fontSize: 13, color: '#1e293b', lineHeight: 1.35, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {p.project_name}
                    </div>
                    <div style={{ fontSize: 10, color: '#94a3b8', marginTop: 2 }}>
                        {p.mascot_type ?? '—'}{p.total_unit ? ` · ${p.total_unit} units` : ''}
                    </div>
                </div>

                {/* Stage bars */}
                <div style={{ background: '#f8fafc', borderRadius: 9, padding: '8px 10px', display: 'flex', flexDirection: 'column', gap: 5 }}>
                    {STAGES.map(s => <StageProgressRow key={s} stage={s} value={sp[s] ?? 0} />)}
                </div>

                {/* Footer */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 'auto' }}>
                    <span style={{ fontSize: 11, display: 'flex', alignItems: 'center', gap: 4, color: p.open_defects > 0 ? '#ef4444' : '#94a3b8', fontWeight: p.open_defects > 0 ? 700 : 400 }}>
                        {p.open_defects > 0 && <AlertTriangle size={11} />}
                        {p.open_defects} defect{p.open_defects !== 1 ? 's' : ''}
                        {p.deadline && <span style={{ color: '#cbd5e1', fontWeight: 400 }}> · {p.deadline}</span>}
                    </span>
                    <button
                        onClick={e => { e.stopPropagation(); onDelete(p); }}
                        style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#e2e8f0', padding: '4px', borderRadius: 6, outline: 'none', display: 'flex', transition: 'color .15s' }}
                        onMouseEnter={e => e.currentTarget.style.color = '#ef4444'}
                        onMouseLeave={e => e.currentTarget.style.color = '#e2e8f0'}
                    >
                        <Trash2 size={13} />
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Filter Bar ────────────────────────────────────────────────────────────────

function FilterBar({ search, onSearch, status, onStatus, stage, onStage, onClear, hasFilters, totalShown, totalAll }) {
    const [expanded, setExpanded] = useState(false);
    const inputBase = {
        border: '1px solid #e2e8f0', borderRadius: 8, padding: '7px 10px',
        fontSize: 12, outline: 'none', background: '#fff', color: '#475569',
        cursor: 'pointer',
    };
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            {/* Row 1: search + filter toggle */}
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                <div style={{ position: 'relative', flex: 1 }}>
                    <Search size={13} style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                    <input
                        type="text" placeholder="Cari project…" value={search}
                        onChange={e => onSearch(e.target.value)}
                        style={{ ...inputBase, width: '100%', boxSizing: 'border-box', paddingLeft: 30 }}
                    />
                    {search && (
                        <button onClick={() => onSearch('')}
                            style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8', display: 'flex', padding: 0 }}>
                            <X size={12} />
                        </button>
                    )}
                </div>
                <button
                    onClick={() => setExpanded(x => !x)}
                    style={{ ...inputBase, display: 'flex', alignItems: 'center', gap: 6, whiteSpace: 'nowrap', flexShrink: 0, fontWeight: 500 }}
                >
                    <Filter size={12} /> Filters
                    {(status || stage) && <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#6366f1', flexShrink: 0 }} />}
                    <ChevronDown size={11} style={{ transform: expanded ? 'rotate(180deg)' : 'none', transition: 'transform .15s' }} />
                </button>
                {hasFilters && (
                    <button onClick={onClear}
                        style={{ ...inputBase, background: '#fef2f2', color: '#dc2626', border: '1px solid #fca5a5', display: 'flex', alignItems: 'center', gap: 5, fontWeight: 600, whiteSpace: 'nowrap', flexShrink: 0 }}>
                        <X size={11} /> Clear
                    </button>
                )}
            </div>

            {/* Row 2: expanded filters */}
            {expanded && (
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, padding: '10px 12px', background: '#f8fafc', borderRadius: 10, border: '1px solid #f1f5f9' }}>
                    <div style={{ position: 'relative' }}>
                        <select value={status} onChange={e => onStatus(e.target.value)} style={{ ...inputBase, paddingRight: 26, appearance: 'none', minWidth: 130 }}>
                            <option value="">All Status</option>
                            {STATUS_OPTIONS.map(s => <option key={s} value={s}>{s}</option>)}
                        </select>
                        <ChevronDown size={11} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                    </div>
                    <div style={{ position: 'relative' }}>
                        <select value={stage} onChange={e => onStage(e.target.value)} style={{ ...inputBase, paddingRight: 26, appearance: 'none', minWidth: 130 }}>
                            <option value="">All Stages</option>
                            {STAGES.map(s => <option key={s} value={s}>{STAGE_LABELS[s]}</option>)}
                        </select>
                        <ChevronDown size={11} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                    </div>
                </div>
            )}

            {hasFilters && (
                <div style={{ fontSize: 11, color: '#6366f1', fontWeight: 600 }}>
                    Menampilkan {totalShown} dari {totalAll} project
                </div>
            )}
        </div>
    );
}

// ── Delete dialog ─────────────────────────────────────────────────────────────

function DeleteDialog({ project, onConfirm, onCancel, pending }) {
    return (
        <div onClick={onCancel} style={{ position: 'fixed', inset: 0, zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(15,23,42,.45)', padding: 16 }}>
            <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 16, boxShadow: '0 24px 64px rgba(0,0,0,.2)', width: '100%', maxWidth: 360, padding: '24px 24px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>
                <div style={{ width: 44, height: 44, borderRadius: 12, background: '#fee2e2', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <Trash2 size={20} color="#dc2626" />
                </div>
                <div>
                    <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 6 }}>Hapus Project?</div>
                    <div style={{ fontSize: 13, color: '#64748b' }}>
                        <strong style={{ color: '#1e293b' }}>{project.project_name}</strong> dan semua datanya akan dihapus permanen.
                    </div>
                </div>
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                    <button onClick={onCancel}
                        style={{ padding: '9px 18px', borderRadius: 9, border: '1px solid #e2e8f0', background: '#f8fafc', color: '#475569', fontSize: 13, fontWeight: 500, cursor: 'pointer', outline: 'none' }}>
                        Cancel
                    </button>
                    <button onClick={onConfirm} disabled={pending}
                        style={{ padding: '9px 18px', borderRadius: 9, border: 'none', background: '#ef4444', color: '#fff', fontSize: 13, fontWeight: 600, cursor: pending ? 'not-allowed' : 'pointer', outline: 'none', opacity: pending ? .6 : 1 }}>
                        {pending ? 'Menghapus…' : 'Hapus'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function JobsPage() {
    const nav      = useNavigate();
    const qc       = useQueryClient();
    const { context } = useApp();

    const [showNew, setShowNew]   = useState(false);
    const [deleting, setDeleting] = useState(null);

    const [search,       setSearch]       = useLocalStorage(`qc:${context}:search`, '');
    const [filterStatus, setFilterStatus] = useLocalStorage(`qc:${context}:status`, '');
    const [filterStage,  setFilterStage]  = useLocalStorage(`qc:${context}:stage`,  '');

    const { data: projects = [], isLoading } = useQuery({ queryKey: ['projects'], queryFn: getProjects });

    const delMut = useMutation({
        mutationFn: deleteProject,
        onSuccess:  () => { qc.invalidateQueries({ queryKey: ['projects'] }); setDeleting(null); },
    });

    const hasFilters = !!(search || filterStatus || filterStage);

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return projects.filter(p => {
            if (q && !p.project_name?.toLowerCase().includes(q) && !p.uid?.toLowerCase().includes(q)) return false;
            if (filterStatus && p.status !== filterStatus)  return false;
            if (filterStage  && !(p.stage_progress?.[filterStage] > 0)) return false;
            return true;
        });
    }, [projects, search, filterStatus, filterStage]);

    const clearFilters = () => { setSearch(''); setFilterStatus(''); setFilterStage(''); };

    if (isLoading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 260, gap: 12, color: '#94a3b8' }}>
            <div style={{ width: 22, height: 22, border: '3px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
            Memuat projects…
        </div>
    );

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Header */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 10 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <div style={{ width: 34, height: 34, borderRadius: 9, background: '#eef2ff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <Layers size={16} color="#6366f1" />
                    </div>
                    <div>
                        <h2 style={{ margin: 0, fontSize: 16, fontWeight: 800, color: '#1e293b' }}>QC Projects</h2>
                        <div style={{ fontSize: 11, color: '#94a3b8' }}>{projects.length} total</div>
                    </div>
                </div>
                <button onClick={() => setShowNew(true)}
                    style={{
                        display: 'flex', alignItems: 'center', gap: 7,
                        padding: '9px 18px', borderRadius: 10, border: 'none',
                        background: 'linear-gradient(135deg,#6366f1,#8b5cf6)',
                        color: '#fff', fontSize: 13, fontWeight: 600,
                        cursor: 'pointer', outline: 'none',
                        boxShadow: '0 4px 12px rgba(99,102,241,.3)',
                        transition: 'opacity .15s',
                    }}
                    onMouseEnter={e => e.currentTarget.style.opacity = '.88'}
                    onMouseLeave={e => e.currentTarget.style.opacity = '1'}
                >
                    <Plus size={15} /> New Project
                </button>
            </div>

            {/* Filter Bar */}
            <FilterBar
                search={search}       onSearch={setSearch}
                status={filterStatus} onStatus={setFilterStatus}
                stage={filterStage}   onStage={setFilterStage}
                onClear={clearFilters} hasFilters={hasFilters}
                totalShown={filtered.length} totalAll={projects.length}
            />

            {/* Card Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(268px, 1fr))', gap: 14 }}>
                {filtered.map(p => (
                    <ProjectCard
                        key={p.uid}
                        p={p}
                        onNavigate={uid => nav(`/projects/${uid}`)}
                        onDelete={setDeleting}
                    />
                ))}

                {filtered.length === 0 && (
                    <div style={{ gridColumn: '1 / -1', textAlign: 'center', padding: '64px 0', color: '#94a3b8' }}>
                        <div style={{ width: 56, height: 56, borderRadius: 16, background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 14px' }}>
                            <Layers size={24} color="#cbd5e1" />
                        </div>
                        <div style={{ fontSize: 14, fontWeight: 600, color: '#334155', marginBottom: 6 }}>
                            {hasFilters ? 'Tidak ada project yang cocok' : 'Belum ada QC project'}
                        </div>
                        <div style={{ fontSize: 12, color: '#94a3b8', marginBottom: 16 }}>
                            {hasFilters ? 'Coba ubah atau hapus filter.' : 'Mulai dengan membuat project pertama.'}
                        </div>
                        {hasFilters
                            ? <button onClick={clearFilters} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#6366f1', fontSize: 13, fontWeight: 600 }}>Hapus filter</button>
                            : <button onClick={() => setShowNew(true)} style={{ border: 'none', background: 'linear-gradient(135deg,#6366f1,#8b5cf6)', color: '#fff', padding: '9px 20px', borderRadius: 10, cursor: 'pointer', fontSize: 13, fontWeight: 600, outline: 'none' }}>Buat Project →</button>
                        }
                    </div>
                )}
            </div>

            {showNew  && <NewJobOrderDialog onClose={() => setShowNew(false)} />}
            {deleting && <DeleteDialog project={deleting} onConfirm={() => delMut.mutate(deleting.uid)} onCancel={() => setDeleting(null)} pending={delMut.isPending} />}
        </div>
    );
}
