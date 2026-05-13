import React, { useState, useMemo, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { createProject, getAvailableJobOrders } from '../api/projects';
import { useApp } from '../context/AppContext';
import { Search, X, AlertCircle, Check } from 'lucide-react';

const TYPE_OPTIONS = {
    mascot:  ['Compress Foam', 'Inflatable', 'Plush Toy', 'Lainnya'],
    costume: ['Uniform', 'Plush Toy', 'Lainnya'],
};

const JO_STATUS_COLORS = {
    WIP:                   { bg: '#ede9fe', text: '#5b21b6' },
    'Pending Material':    { bg: '#fef3c7', text: '#92400e' },
    'Pending Design':      { bg: '#e0f2fe', text: '#0369a1' },
    'Client Confirmation': { bg: '#dcfce7', text: '#166534' },
    Cancelled:             { bg: '#fee2e2', text: '#991b1b' },
};
const statusChip = (s) => JO_STATUS_COLORS[s] ?? { bg: '#f1f5f9', text: '#475569' };

const fieldWrap = { display: 'flex', flexDirection: 'column', gap: 5 };
const fieldLabel = { fontSize: 11, fontWeight: 700, color: '#64748b', letterSpacing: '.05em', textTransform: 'uppercase' };
const fieldInput = {
    width: '100%', boxSizing: 'border-box',
    border: '1.5px solid #e2e8f0', borderRadius: 9,
    padding: '9px 12px', fontSize: 13, outline: 'none',
    background: '#fff', color: '#1e293b',
    transition: 'border-color .15s',
};

// ── JO Combobox ───────────────────────────────────────────────────────────────

function JoCombobox({ jobOrders, loading, value, onChange }) {
    const [open, setOpen]       = useState(false);
    const [query, setQuery]     = useState('');
    const wrapRef               = useRef(null);
    const inputRef              = useRef(null);

    const selected = value ? jobOrders.find(j => j.id === value) : null;

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        const list = q
            ? jobOrders.filter(j =>
                j.name?.toLowerCase().includes(q) ||
                j.id?.toLowerCase().includes(q)   ||
                j.project_name?.toLowerCase().includes(q))
            : jobOrders;
        return list.slice(0, 40);
    }, [jobOrders, query]);

    // Close dropdown on outside click
    useEffect(() => {
        const h = (e) => {
            if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', h);
        return () => document.removeEventListener('mousedown', h);
    }, []);

    const pick = (jo) => {
        onChange(jo);
        setQuery('');
        setOpen(false);
    };

    const clear = (e) => {
        e.stopPropagation();
        onChange(null);
        setQuery('');
        inputRef.current?.focus();
    };

    // Input display: show selected name when not searching, else show query
    const inputDisplay = open ? query : (selected ? `${selected.id} — ${selected.name}` : query);

    return (
        <div ref={wrapRef} style={{ position: 'relative' }}>
            <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                <Search size={14} style={{ position: 'absolute', left: 11, color: '#94a3b8', pointerEvents: 'none', zIndex: 1 }} />
                <input
                    ref={inputRef}
                    type="text"
                    placeholder={loading ? 'Loading…' : 'Ketik nama atau ID job order…'}
                    value={inputDisplay}
                    onFocus={() => { setOpen(true); if (selected) setQuery(''); }}
                    onChange={e => { setQuery(e.target.value); setOpen(true); }}
                    style={{
                        ...fieldInput,
                        paddingLeft: 34, paddingRight: selected ? 34 : 12,
                        border: open ? '1.5px solid #6366f1' : '1.5px solid #e2e8f0',
                        fontWeight: selected && !open ? 500 : 400,
                    }}
                />
                {selected && (
                    <button type="button" onClick={clear}
                        style={{ position: 'absolute', right: 10, border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8', display: 'flex', padding: 0, zIndex: 1 }}>
                        <X size={14} />
                    </button>
                )}
            </div>

            {open && (
                <div
                    onMouseDown={e => e.preventDefault()} // keep focus in input
                    style={{
                        position: 'absolute', top: 'calc(100% + 4px)', left: 0, right: 0, zIndex: 99999,
                        background: '#fff', borderRadius: 10,
                        boxShadow: '0 8px 32px rgba(0,0,0,.14)', border: '1px solid #e2e8f0',
                        overflow: 'hidden',
                    }}
                >
                    {filtered.length === 0 ? (
                        <div style={{ padding: '18px 14px', textAlign: 'center', fontSize: 13, color: '#94a3b8' }}>
                            {query ? `Tidak ada hasil untuk "${query}"` : 'Tidak ada job order tersedia.'}
                        </div>
                    ) : (
                        <div style={{ maxHeight: 260, overflowY: 'auto' }}>
                            {filtered.map(jo => {
                                const active = jo.id === value;
                                const sc = statusChip(jo.status);
                                return (
                                    <button key={jo.id} type="button" onClick={() => pick(jo)}
                                        style={{
                                            width: '100%', textAlign: 'left', border: 'none', cursor: 'pointer',
                                            padding: '9px 14px', display: 'flex', alignItems: 'center', gap: 10,
                                            background: active ? '#eef2ff' : '#fff', transition: 'background .08s',
                                        }}
                                        onMouseEnter={e => { if (!active) e.currentTarget.style.background = '#f8fafc'; }}
                                        onMouseLeave={e => { e.currentTarget.style.background = active ? '#eef2ff' : '#fff'; }}
                                    >
                                        <div style={{ flex: 1, minWidth: 0 }}>
                                            <div style={{ fontSize: 11, fontWeight: 700, color: '#6366f1', marginBottom: 1 }}>{jo.id}</div>
                                            <div style={{ fontSize: 13, color: '#1e293b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{jo.name}</div>
                                        </div>
                                        {jo.status && (
                                            <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 7px', borderRadius: 999, background: sc.bg, color: sc.text, whiteSpace: 'nowrap', flexShrink: 0 }}>
                                                {jo.status}
                                            </span>
                                        )}
                                        {active && <Check size={14} color="#6366f1" style={{ flexShrink: 0 }} />}
                                    </button>
                                );
                            })}
                            {jobOrders.length > 40 && (
                                <div style={{ padding: '7px 14px', fontSize: 11, color: '#94a3b8', textAlign: 'center', borderTop: '1px solid #f1f5f9' }}>
                                    Ketik untuk mempersempit hasil ({jobOrders.length} total)
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── Type Pills ────────────────────────────────────────────────────────────────

function TypePills({ options, value, onChange }) {
    return (
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
            {options.map(t => {
                const active = value === t;
                return (
                    <button key={t} type="button" onClick={() => onChange(t)}
                        style={{
                            padding: '6px 14px', borderRadius: 999, fontSize: 12, fontWeight: 600,
                            cursor: 'pointer', outline: 'none', transition: 'all .15s',
                            border: active ? '1.5px solid #6366f1' : '1.5px solid #e2e8f0',
                            background: active ? '#eef2ff' : '#fff',
                            color: active ? '#4f46e5' : '#64748b',
                        }}
                    >
                        {active ? <span style={{ marginRight: 4 }}>✓</span> : null}{t}
                    </button>
                );
            })}
        </div>
    );
}

// ── Dialog ────────────────────────────────────────────────────────────────────

export default function NewJobOrderDialog({ onClose }) {
    const nav         = useNavigate();
    const qc          = useQueryClient();
    const { context } = useApp();

    const typeOptions = TYPE_OPTIONS[context] ?? TYPE_OPTIONS.mascot;
    const today       = new Date().toISOString().slice(0, 10);

    const { data: jobOrders = [], isLoading } = useQuery({
        queryKey:  ['job-orders-available', context],
        queryFn:   () => getAvailableJobOrders(context),
    });

    const [form, setForm] = useState({
        job_order_id: '', mascot_type: typeOptions[0],
        total_unit: '', inspection_date: today, deadline: '',
    });
    const [selectedJo, setSelectedJo] = useState(null);
    const [error, setError]           = useState(null);

    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: createProject,
        onSuccess:  (data) => {
            qc.invalidateQueries({ queryKey: ['projects'] });
            qc.invalidateQueries({ queryKey: ['dashboard'] });
            nav(`/projects/${data.uid}`);
        },
        onError: (e) => setError(e.response?.data?.message ?? e.message),
    });

    const handleJoSelect = (jo) => {
        if (!jo) {
            setSelectedJo(null);
            set('job_order_id', '');
            return;
        }
        setSelectedJo(jo);
        set('job_order_id', jo.id);                             // string — no Number() cast
        if (jo.deadline) set('deadline', String(jo.deadline).slice(0, 10));
    };

    const ctxLabel = context === 'costume' ? 'Costume' : 'Mascot';

    return (
        <div
            onClick={onClose}
            style={{
                position: 'fixed', inset: 0, zIndex: 9999,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                background: 'rgba(15,23,42,.5)', padding: 16,
            }}
        >
            <div
                onClick={e => e.stopPropagation()}
                style={{
                    background: '#fff', borderRadius: 18,
                    boxShadow: '0 28px 72px rgba(0,0,0,.22)',
                    width: '100%', maxWidth: 480,
                    maxHeight: '92vh', display: 'flex', flexDirection: 'column',
                }}
            >
                {/* Header */}
                <div style={{
                    padding: '18px 22px 14px', borderBottom: '1px solid #f1f5f9',
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    borderRadius: '18px 18px 0 0', background: '#fff',
                }}>
                    <div>
                        <div style={{ fontSize: 16, fontWeight: 700, color: '#1e293b' }}>New QC Project</div>
                        <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>QC {ctxLabel} · {today}</div>
                    </div>
                    <button onClick={onClose}
                        style={{ background: '#f1f5f9', border: 'none', cursor: 'pointer', borderRadius: 8, padding: '6px 8px', display: 'flex', outline: 'none', color: '#64748b' }}>
                        <X size={15} />
                    </button>
                </div>

                {/* Body — scrollable */}
                <div style={{ padding: '18px 22px', display: 'flex', flexDirection: 'column', gap: 16, overflowY: 'auto', flex: 1 }}>
                    {error && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 9, padding: '9px 12px', border: '1px solid #fca5a5' }}>
                            <AlertCircle size={14} style={{ flexShrink: 0 }} /> {error}
                        </div>
                    )}

                    <div style={fieldWrap}>
                        <label style={fieldLabel}>Job Order</label>
                        <JoCombobox
                            jobOrders={jobOrders}
                            loading={isLoading}
                            value={form.job_order_id}
                            onChange={handleJoSelect}
                        />
                        {selectedJo?.project_name && (
                            <div style={{ fontSize: 11, color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: 5, marginTop: 1 }}>
                                <span style={{ width: 5, height: 5, borderRadius: '50%', background: '#6366f1', flexShrink: 0 }} />
                                Project: {selectedJo.project_name}
                            </div>
                        )}
                    </div>

                    <div style={fieldWrap}>
                        <label style={fieldLabel}>Type</label>
                        <TypePills options={typeOptions} value={form.mascot_type} onChange={v => set('mascot_type', v)} />
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                        <div style={fieldWrap}>
                            <label style={fieldLabel}>Total Units</label>
                            <input type="number" min="1" style={fieldInput}
                                value={form.total_unit}
                                placeholder="e.g. 10"
                                onChange={e => set('total_unit', e.target.value === '' ? '' : parseInt(e.target.value) || '')} />
                        </div>
                        <div style={fieldWrap}>
                            <label style={fieldLabel}>Deadline</label>
                            <input type="date" style={fieldInput}
                                value={form.deadline}
                                onChange={e => set('deadline', e.target.value)} />
                        </div>
                    </div>

                    <div style={fieldWrap}>
                        <label style={fieldLabel}>Inspection Date</label>
                        <input type="date" style={fieldInput}
                            value={form.inspection_date}
                            onChange={e => set('inspection_date', e.target.value)} />
                    </div>
                </div>

                {/* Footer */}
                <div style={{
                    padding: '14px 22px', borderTop: '1px solid #f1f5f9',
                    display: 'flex', justifyContent: 'flex-end', gap: 8,
                    borderRadius: '0 0 18px 18px', background: '#fff',
                }}>
                    <button type="button" onClick={onClose}
                        style={{ padding: '9px 18px', borderRadius: 9, border: '1px solid #e2e8f0', background: '#f8fafc', color: '#475569', fontSize: 13, fontWeight: 500, cursor: 'pointer', outline: 'none' }}>
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={() => mut.mutate(form)}
                        disabled={!form.job_order_id || mut.isPending}
                        style={{
                            padding: '9px 22px', borderRadius: 9, border: 'none',
                            background: form.job_order_id ? '#6366f1' : '#cbd5e1',
                            color: form.job_order_id ? '#fff' : '#94a3b8',
                            fontSize: 13, fontWeight: 600,
                            cursor: form.job_order_id ? 'pointer' : 'not-allowed',
                            outline: 'none', transition: 'background .15s',
                            opacity: mut.isPending ? 0.7 : 1,
                        }}
                    >
                        {mut.isPending ? 'Creating…' : 'Create Project'}
                    </button>
                </div>
            </div>
        </div>
    );
}
