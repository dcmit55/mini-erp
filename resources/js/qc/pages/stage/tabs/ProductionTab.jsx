import React, { useState, useMemo, useRef, useEffect, useCallback } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getStageRecords, createStageRecord, inspectRecord } from '../../../api/stageProduction';
import { getEmployees } from '../../../api/employees';
import { uploadPhoto } from '../../../api/photos';
import { useApp } from '../../../context/AppContext';
import { STAGE_COLORS } from '../../../data/models';
import {
    Plus, Search, X, Check, ChevronDown, ChevronUp, AlertCircle,
    Camera, CheckCircle2, XCircle, Loader2, History, Upload, ChevronRight,
} from 'lucide-react';

// ── Constants ─────────────────────────────────────────────────────────────────

const DEFAULT_PARTS_MASCOT = [
    'Body Mascot','Body Suit','Body Pad','Shirt','Kepala','Mata','Hidung','Mulut',
    'Tangan','Kaki','Sepatu','Ekor','Harness','Fan','Cable','Battery',
    'Charger','Remote','Aksesori','Handle','Standy',
];
const DEFAULT_PARTS_COSTUME = [
    'Body','Head','Ear','Eye','Mouth','Hand','Tail','Nose',
    'Arm','Neck','Wing','Horn','Back','Fur','Seam','Zipper','Label','Accessories',
];
const DEFECT_CATS = ['Sewing','Cutting','Material','Finishing','Assembly','Embroidery','Zipper','Print','Other'];

const inp = {
    width: '100%', border: '1.5px solid #e2e8f0', borderRadius: 8,
    padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box',
    background: '#fff', color: '#1e293b',
};
const lbl = { fontSize: 11, fontWeight: 600, color: '#64748b', display: 'block', marginBottom: 4 };

function initials(name) {
    return (name ?? '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

// ── SearchCombobox ─────────────────────────────────────────────────────────────

function SearchCombobox({ value, onChange, options, placeholder = 'Ketik untuk mencari…', allowCustom = false }) {
    const [open, setOpen]   = useState(false);
    const [query, setQuery] = useState('');
    const wrapRef  = useRef(null);
    const inputRef = useRef(null);

    useEffect(() => {
        const h = e => { if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', h);
        return () => document.removeEventListener('mousedown', h);
    }, []);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        return (q ? options.filter(o => o.toLowerCase().includes(q)) : options).slice(0, 50);
    }, [options, query]);

    const isSelected   = !!value;
    const inputDisplay = open ? query : (value || query);
    const pick = opt   => { onChange(opt); setQuery(''); setOpen(false); };
    const clear = e    => { e.stopPropagation(); onChange(''); setQuery(''); inputRef.current?.focus(); };

    return (
        <div ref={wrapRef} style={{ position: 'relative' }}>
            <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                <Search size={13} style={{ position: 'absolute', left: 9, color: '#94a3b8', pointerEvents: 'none' }} />
                <input ref={inputRef} type="text" placeholder={placeholder} value={inputDisplay}
                    onFocus={() => { setOpen(true); if (value) setQuery(''); }}
                    onChange={e => { setQuery(e.target.value); if (allowCustom) onChange(e.target.value); setOpen(true); }}
                    onKeyDown={e => { if (e.key === 'Escape') setOpen(false); if (e.key === 'Enter' && allowCustom && query.trim()) pick(query.trim()); }}
                    style={{ ...inp, paddingLeft: 30, paddingRight: isSelected ? 28 : 10, borderColor: open ? '#6366f1' : '#e2e8f0', fontWeight: isSelected && !open ? 500 : 400 }} />
                {isSelected && <button type="button" onClick={clear} style={{ position: 'absolute', right: 7, border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8', display: 'flex', padding: 0 }}><X size={13} /></button>}
            </div>
            {open && (
                <div onMouseDown={e => e.preventDefault()} style={{ position: 'absolute', top: 'calc(100% + 4px)', left: 0, right: 0, zIndex: 9999, background: '#fff', borderRadius: 10, boxShadow: '0 8px 28px rgba(0,0,0,.13)', border: '1px solid #e2e8f0', overflow: 'hidden' }}>
                    {filtered.length === 0 && !allowCustom && <div style={{ padding: '14px 12px', fontSize: 12, color: '#94a3b8', textAlign: 'center' }}>{query ? `Tidak ada hasil untuk "${query}"` : 'Tidak ada opsi.'}</div>}
                    {filtered.length === 0 && allowCustom && query.trim() && (
                        <div onClick={() => pick(query.trim())} style={{ padding: '9px 12px', fontSize: 13, cursor: 'pointer', color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: 6 }} onMouseEnter={e => e.currentTarget.style.background = '#eef2ff'} onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                            <Plus size={13} /> Tambah "{query.trim()}"
                        </div>
                    )}
                    {filtered.length > 0 && (
                        <div style={{ maxHeight: 240, overflowY: 'auto' }}>
                            {filtered.map(opt => {
                                const active = opt === value;
                                return (
                                    <div key={opt} onClick={() => pick(opt)}
                                        style={{ padding: '8px 12px', fontSize: 13, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: active ? '#eef2ff' : '#fff', color: active ? '#4f46e5' : '#334155' }}
                                        onMouseEnter={e => { if (!active) e.currentTarget.style.background = '#f8fafc'; }}
                                        onMouseLeave={e => e.currentTarget.style.background = active ? '#eef2ff' : '#fff'}>
                                        <span>{opt}</span>
                                        {active && <Check size={13} color="#6366f1" />}
                                    </div>
                                );
                            })}
                            {allowCustom && query.trim() && !options.some(o => o.toLowerCase() === query.trim().toLowerCase()) && (
                                <div onClick={() => pick(query.trim())} style={{ padding: '8px 12px', fontSize: 13, cursor: 'pointer', color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: 6, borderTop: '1px solid #f1f5f9' }} onMouseEnter={e => e.currentTarget.style.background = '#eef2ff'} onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                                    <Plus size={13} /> Tambah "{query.trim()}"
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── HistoryTextarea — textarea + history autocomplete dropdown ─────────────────

function HistoryTextarea({ value, onChange, suggestions = [], placeholder, rows = 2 }) {
    const [open, setOpen] = useState(false);
    const wrapRef = useRef(null);

    useEffect(() => {
        const h = e => { if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', h);
        return () => document.removeEventListener('mousedown', h);
    }, []);

    const filtered = useMemo(() => {
        const q = (value ?? '').trim().toLowerCase();
        if (!q) return suggestions.slice(0, 6);
        return suggestions.filter(s => s.toLowerCase().includes(q) && s.toLowerCase() !== q).slice(0, 6);
    }, [value, suggestions]);

    return (
        <div ref={wrapRef} style={{ position: 'relative' }}>
            <div style={{ position: 'relative' }}>
                <textarea value={value} rows={rows} placeholder={placeholder}
                    onChange={e => { onChange(e.target.value); setOpen(true); }}
                    onFocus={() => setOpen(true)}
                    style={{ ...inp, resize: 'vertical', fontFamily: 'inherit', lineHeight: 1.5, paddingRight: suggestions.length > 0 ? 26 : 10 }} />
                {suggestions.length > 0 && <History size={12} style={{ position: 'absolute', right: 8, top: 9, color: '#94a3b8', pointerEvents: 'none' }} />}
            </div>
            {open && filtered.length > 0 && (
                <div onMouseDown={e => e.preventDefault()} style={{ position: 'absolute', top: '100%', left: 0, right: 0, zIndex: 9999, background: '#fff', borderRadius: 10, boxShadow: '0 8px 28px rgba(0,0,0,.13)', border: '1px solid #e2e8f0', overflow: 'hidden', marginTop: 2 }}>
                    <div style={{ padding: '5px 10px', borderBottom: '1px solid #f1f5f9', fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.06em', display: 'flex', alignItems: 'center', gap: 4 }}>
                        <History size={9} /> Dari history
                    </div>
                    {filtered.map((s, i) => (
                        <div key={i} onClick={() => { onChange(s); setOpen(false); }}
                            style={{ padding: '8px 12px', fontSize: 12, cursor: 'pointer', color: '#334155', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}
                            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                            onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                            {s}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

// ── InspectionSummary — shown for already-inspected records ───────────────────

function InspectionSummary({ record }) {
    const isPass = record.status === 'PASS';
    const sevColor = record.severity === 'Critical' ? '#dc2626' : record.severity === 'Major' ? '#d97706' : '#ca8a04';

    return (
        <div style={{ padding: '16px 20px', background: isPass ? '#f0fdf4' : '#fff5f5', borderTop: `1.5px solid ${isPass ? '#86efac' : '#fca5a5'}` }}>
            <div style={{ display: 'flex', gap: 24, flexWrap: 'wrap', marginBottom: (record.defect_desc || record.corrective_action) ? 12 : 0 }}>
                <div>
                    <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 3 }}>Result</div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 13, fontWeight: 700, color: isPass ? '#16a34a' : '#dc2626' }}>
                        {isPass ? <CheckCircle2 size={14} /> : <XCircle size={14} />} {record.status}
                    </div>
                </div>
                <div>
                    <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 3 }}>Pass</div>
                    <div style={{ fontSize: 14, fontWeight: 800, color: '#16a34a' }}>{record.qty_pass ?? '—'}</div>
                </div>
                <div>
                    <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 3 }}>Fail</div>
                    <div style={{ fontSize: 14, fontWeight: 800, color: (record.qty_fail ?? 0) > 0 ? '#dc2626' : '#94a3b8' }}>{record.qty_fail ?? '—'}</div>
                </div>
                {record.defect_cat && (
                    <div>
                        <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 3 }}>Category</div>
                        <div style={{ fontSize: 12, color: '#334155', fontWeight: 600 }}>{record.defect_cat}</div>
                    </div>
                )}
                {record.severity && (
                    <div>
                        <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 3 }}>Severity</div>
                        <span style={{ fontSize: 11, fontWeight: 700, padding: '2px 8px', borderRadius: 999, background: `${sevColor}18`, color: sevColor }}>{record.severity}</span>
                    </div>
                )}
            </div>
            {record.defect_desc && (
                <div style={{ marginBottom: 8 }}>
                    <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 2 }}>Defect Description</div>
                    <div style={{ fontSize: 12, color: '#334155', lineHeight: 1.6, background: '#fff', borderRadius: 7, padding: '6px 10px', border: '1px solid #fca5a5' }}>{record.defect_desc}</div>
                </div>
            )}
            {record.corrective_action && (
                <div>
                    <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 2 }}>Corrective Action</div>
                    <div style={{ fontSize: 12, color: '#334155', lineHeight: 1.6, background: '#fff', borderRadius: 7, padding: '6px 10px', border: '1px solid #86efac' }}>{record.corrective_action}</div>
                </div>
            )}
        </div>
    );
}

// ── PhotoSection ───────────────────────────────────────────────────────────────

function PhotoSection({ record, projectUid, stage, color }) {
    const qc      = useQueryClient();
    const fileRef = useRef(null);
    const [uploading, setUploading] = useState(false);
    const [err, setErr]             = useState(null);

    const handleUpload = useCallback(async e => {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploading(true); setErr(null);
        try {
            await uploadPhoto(file, 'daily_item', record.uid, { context: 'production' });
            qc.invalidateQueries({ queryKey: ['stage-records', projectUid, stage] });
        } catch {
            setErr('Upload gagal.');
        } finally {
            setUploading(false);
            if (fileRef.current) fileRef.current.value = '';
        }
    }, [record.uid, projectUid, stage, qc]);

    const photos = record.photos ?? [];

    return (
        <div style={{ padding: '12px 20px', borderTop: '1px solid #f1f5f9' }}>
            <div style={{ fontSize: 10, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 8, display: 'flex', alignItems: 'center', gap: 5 }}>
                <Camera size={11} /> Foto Inspeksi {photos.length > 0 && <span style={{ background: '#f1f5f9', borderRadius: 999, padding: '1px 6px', fontSize: 10, color: '#475569', marginLeft: 2 }}>{photos.length}</span>}
            </div>
            {err && <div style={{ fontSize: 11, color: '#dc2626', marginBottom: 6 }}>{err}</div>}
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
                {photos.map(p => (
                    <a key={p.uid} href={p.url} target="_blank" rel="noreferrer">
                        <img src={p.url} alt="foto" style={{ width: 64, height: 64, objectFit: 'cover', borderRadius: 8, border: '2px solid #e2e8f0', cursor: 'zoom-in', display: 'block' }} />
                    </a>
                ))}
                <button type="button" onClick={() => fileRef.current?.click()} disabled={uploading}
                    style={{ width: 64, height: 64, borderRadius: 8, border: `2px dashed ${color.border}`, background: color.bg, cursor: 'pointer', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 4, color: color.text, opacity: uploading ? 0.7 : 1, outline: 'none' }}>
                    {uploading ? <Loader2 size={18} style={{ animation: 'spin 1s linear infinite' }} /> : <Upload size={18} />}
                    <span style={{ fontSize: 9, fontWeight: 700 }}>{uploading ? '…' : 'Upload'}</span>
                </button>
                <input ref={fileRef} type="file" accept="image/*" capture="environment" style={{ display: 'none' }} onChange={handleUpload} />
            </div>
        </div>
    );
}

// ── InspectPanel — inline inspection form ─────────────────────────────────────

function InspectPanel({ record, projectUid, stage, color, historyNotes, historyActions }) {
    const qc    = useQueryClient();
    const isOne = record.qty_produced === 1;

    const [failMode, setFailMode] = useState(false);
    const [form, setForm] = useState({
        qty_pass: '', qty_fail: '',
        defect_category: '', defect_desc: '', severity: 'Major', corrective_action: '',
    });
    const [err, setErr] = useState(null);

    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const qtyFail      = parseInt(form.qty_fail) || 0;
    const showFailForm = failMode || qtyFail > 0;

    const mut = useMutation({
        mutationFn: data => inspectRecord(projectUid, stage, record.uid, data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['stage-records', projectUid, stage] });
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, stage] });
        },
        onError: e => setErr(e?.response?.data?.message ?? e.message ?? 'Gagal menyimpan'),
    });

    const validate = (qp, qf) => {
        if (qp + qf !== record.qty_produced) {
            setErr(`Pass (${qp}) + Fail (${qf}) harus sama dengan qty produksi (${record.qty_produced}).`);
            return false;
        }
        if (qf > 0 && !form.defect_desc.trim()) { setErr('Defect description wajib diisi saat ada item fail.'); return false; }
        if (qf > 0 && !form.corrective_action.trim()) { setErr('Corrective action wajib diisi saat ada item fail.'); return false; }
        return true;
    };

    const submit = (qp, qf) => {
        setErr(null);
        if (!validate(qp, qf)) return;
        mut.mutate({
            qty_pass: qp, qty_fail: qf,
            defect_category:   form.defect_category || 'Other',
            defect_desc:       form.defect_desc || undefined,
            severity:          form.severity,
            corrective_action: form.corrective_action || undefined,
        });
    };

    const handleQtyPassChange = v => {
        const qp = Math.min(Math.max(0, parseInt(v) || 0), record.qty_produced);
        set('qty_pass', String(qp));
        set('qty_fail', String(record.qty_produced - qp));
    };

    const handleQtyFailChange = v => {
        const qf = Math.min(Math.max(0, parseInt(v) || 0), record.qty_produced);
        set('qty_fail', String(qf));
        set('qty_pass', String(record.qty_produced - qf));
    };

    const canConfirm = !showFailForm || (form.defect_desc.trim() && form.corrective_action.trim());

    return (
        <div style={{ padding: '18px 20px', background: color.bg, borderTop: `2px solid ${color.border}33` }}>

            {/* Header */}
            <div style={{ fontSize: 11, fontWeight: 700, color: color.text, textTransform: 'uppercase', letterSpacing: '.07em', marginBottom: 14, display: 'flex', alignItems: 'center', gap: 6 }}>
                <CheckCircle2 size={13} /> Inspection
            </div>

            {err && (
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 7, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 10px', marginBottom: 12 }}>
                    <AlertCircle size={13} style={{ marginTop: 1, flexShrink: 0 }} /> {err}
                </div>
            )}

            {/* qty=1: big Pass/Fail buttons */}
            {isOne && !failMode && (
                <div style={{ display: 'flex', gap: 10, marginBottom: 4 }}>
                    <button onClick={() => submit(1, 0)} disabled={mut.isPending}
                        style={{ flex: 1, padding: '14px 0', borderRadius: 10, border: '2px solid #22c55e', background: '#f0fdf4', color: '#15803d', fontSize: 14, fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, outline: 'none' }}>
                        {mut.isPending ? <Loader2 size={16} style={{ animation: 'spin 1s linear infinite' }} /> : <CheckCircle2 size={19} color="#22c55e" />} Pass
                    </button>
                    <button onClick={() => setFailMode(true)} disabled={mut.isPending}
                        style={{ flex: 1, padding: '14px 0', borderRadius: 10, border: '2px solid #ef4444', background: '#fef2f2', color: '#b91c1c', fontSize: 14, fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, outline: 'none' }}>
                        <XCircle size={19} color="#ef4444" /> Fail
                    </button>
                </div>
            )}

            {/* qty>1: qty_pass + qty_fail inputs */}
            {!isOne && (
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 12 }}>
                    <div>
                        <span style={{ ...lbl, color: '#15803d' }}>Qty Pass</span>
                        <input type="number" min={0} max={record.qty_produced} value={form.qty_pass}
                            onChange={e => handleQtyPassChange(e.target.value)}
                            placeholder={`0 – ${record.qty_produced}`}
                            style={{ ...inp, borderColor: '#22c55e55', background: '#f0fdf4' }} />
                    </div>
                    <div>
                        <span style={{ ...lbl, color: '#dc2626' }}>Qty Fail</span>
                        <input type="number" min={0} max={record.qty_produced} value={form.qty_fail}
                            onChange={e => handleQtyFailChange(e.target.value)}
                            placeholder="0"
                            style={{ ...inp, borderColor: '#ef444455', background: '#fff5f5' }} />
                    </div>
                </div>
            )}

            {/* Fail detail form */}
            {showFailForm && (
                <div style={{ background: '#fff7ed', border: '1.5px solid #fed7aa', borderRadius: 10, padding: '14px', marginBottom: 14 }}>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#c2410c', marginBottom: 12, display: 'flex', alignItems: 'center', gap: 5 }}>
                        <AlertCircle size={12} /> Detail Kerusakan — Wajib Diisi
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 10 }}>
                        <div>
                            <span style={lbl}>Defect Category</span>
                            <select value={form.defect_category} onChange={e => set('defect_category', e.target.value)} style={{ ...inp, cursor: 'pointer' }}>
                                <option value="">— Pilih —</option>
                                {DEFECT_CATS.map(c => <option key={c} value={c}>{c}</option>)}
                            </select>
                        </div>
                        <div>
                            <span style={lbl}>Severity</span>
                            <select value={form.severity} onChange={e => set('severity', e.target.value)} style={{ ...inp, cursor: 'pointer' }}>
                                {['Critical','Major','Minor'].map(s => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>
                    </div>
                    <div style={{ marginBottom: 10 }}>
                        <span style={{ ...lbl, color: '#dc2626' }}>Defect Description *</span>
                        <HistoryTextarea value={form.defect_desc} onChange={v => set('defect_desc', v)} suggestions={historyNotes} placeholder="Tulis deskripsi kerusakan yang ditemukan…" rows={2} />
                    </div>
                    <div>
                        <span style={{ ...lbl, color: '#dc2626' }}>Corrective Action *</span>
                        <HistoryTextarea value={form.corrective_action} onChange={v => set('corrective_action', v)} suggestions={historyActions} placeholder="Tulis tindakan perbaikan yang harus dilakukan…" rows={2} />
                    </div>
                </div>
            )}

            {/* Confirm button (qty>1, or qty=1 fail mode) */}
            {(!isOne || failMode) && (
                <button
                    onClick={() => {
                        if (isOne) submit(0, 1);
                        else submit(parseInt(form.qty_pass) || 0, parseInt(form.qty_fail) || 0);
                    }}
                    disabled={mut.isPending || !canConfirm}
                    style={{
                        width: '100%', padding: '11px 0', borderRadius: 9, border: 'none', cursor: 'pointer', outline: 'none',
                        background: (failMode || qtyFail > 0) ? '#ef4444' : color.border,
                        color: '#fff', fontSize: 13, fontWeight: 700,
                        opacity: (mut.isPending || !canConfirm) ? 0.5 : 1,
                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 7,
                    }}>
                    {mut.isPending
                        ? <><Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> Saving…</>
                        : (failMode || qtyFail > 0) ? 'Confirm Fail' : 'Confirm Inspection'}
                </button>
            )}
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────────────

export default function ProductionTab({ projectUid, stage, project }) {
    const { context } = useApp();
    const qc    = useQueryClient();
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const { data: records = [], isLoading } = useQuery({
        queryKey: ['stage-records', projectUid, stage],
        queryFn:  () => getStageRecords(projectUid, stage),
        staleTime: 30_000,
    });

    const { data: employees = [] } = useQuery({
        queryKey: ['employees'],
        queryFn:  getEmployees,
        staleTime: 300_000,
    });

    const [showForm, setShowForm]   = useState(false);
    const [expandedUid, setExpanded] = useState(null);
    const [form, setForm]           = useState({ date: new Date().toISOString().slice(0, 10), operator: '', part: '', qty: 1, notes: '' });
    const [formErr, setFormErr]     = useState(null);
    const [search, setSearch]       = useState('');
    const [sortDir, setSortDir]     = useState('desc');

    const setF = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const employeeNames = useMemo(() => employees.map(e => e.name), [employees]);
    const allParts = useMemo(() => {
        const base  = context === 'costume' ? DEFAULT_PARTS_COSTUME : DEFAULT_PARTS_MASCOT;
        const extra = project?.custom_parts ?? [];
        return [...new Set([...base, ...extra])];
    }, [context, project]);

    const historyNotes   = useMemo(() => [...new Set(records.filter(r => r.defect_desc).map(r => r.defect_desc))], [records]);
    const historyActions = useMemo(() => [...new Set(records.filter(r => r.corrective_action).map(r => r.corrective_action))], [records]);

    const createMut = useMutation({
        mutationFn: () => createStageRecord(projectUid, stage, { ...form }),
        onSuccess: newRec => {
            qc.invalidateQueries({ queryKey: ['stage-records', projectUid, stage] });
            setForm(f => ({ ...f, part: '', qty: 1, notes: '' }));
            setFormErr(null);
            setShowForm(false);
            setExpanded(newRec.uid);
        },
        onError: e => setFormErr(e?.response?.data?.message ?? e.message),
    });

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return records
            .filter(r => !q || r.part?.toLowerCase().includes(q) || r.operator?.toLowerCase().includes(q))
            .sort((a, b) => sortDir === 'desc'
                ? (b.date ?? '').localeCompare(a.date ?? '')
                : (a.date ?? '').localeCompare(b.date ?? ''));
    }, [records, search, sortDir]);

    const canSubmit = form.operator && form.part && form.qty >= 1;
    const toggle    = uid => setExpanded(prev => prev === uid ? null : uid);

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

            {/* ── Add Form (collapsible) ── */}
            <div style={{ background: color.bg, borderRadius: 14, overflow: 'hidden', border: `1.5px solid ${color.border}33`, borderTop: `3px solid ${color.border}` }}>
                <button onClick={() => setShowForm(s => !s)} style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: 'none', border: 'none', cursor: 'pointer', outline: 'none' }}>
                    <span style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, fontWeight: 700, color: color.text }}>
                        <div style={{ width: 26, height: 26, borderRadius: 7, background: color.border, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <Plus size={14} color="#fff" />
                        </div>
                        Add Production Record
                    </span>
                    <ChevronDown size={16} color={color.text} style={{ transform: showForm ? 'rotate(180deg)' : 'rotate(0)', transition: 'transform .2s' }} />
                </button>
                {showForm && (
                    <div style={{ padding: '4px 18px 18px' }}>
                        {formErr && (
                            <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '7px 10px', marginBottom: 12 }}>
                                <AlertCircle size={13} /> {formErr}
                            </div>
                        )}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(155px, 1fr))', gap: 12 }}>
                            <div><span style={lbl}>Date *</span><input type="date" style={inp} value={form.date} onChange={e => setF('date', e.target.value)} /></div>
                            <div><span style={lbl}>Operator *</span><SearchCombobox value={form.operator} onChange={v => setF('operator', v)} options={employeeNames} placeholder="Cari atau ketik nama…" allowCustom /></div>
                            <div><span style={lbl}>Part / Component *</span><SearchCombobox value={form.part} onChange={v => setF('part', v)} options={allParts} placeholder="Cari atau ketik part…" allowCustom /></div>
                            <div><span style={lbl}>Qty Produced *</span><input type="number" min="1" style={inp} value={form.qty} onChange={e => setF('qty', parseInt(e.target.value) || 1)} /></div>
                            <div style={{ gridColumn: '1 / -1' }}><span style={lbl}>Notes</span><input type="text" style={inp} value={form.notes} onChange={e => setF('notes', e.target.value)} placeholder="Optional notes…" /></div>
                        </div>
                        <div style={{ marginTop: 14, display: 'flex', justifyContent: 'flex-end' }}>
                            <button onClick={() => createMut.mutate()} disabled={!canSubmit || createMut.isPending}
                                style={{ padding: '9px 24px', borderRadius: 8, border: 'none', cursor: canSubmit ? 'pointer' : 'not-allowed', background: color.border, color: '#fff', fontSize: 13, fontWeight: 600, outline: 'none', opacity: (!canSubmit || createMut.isPending) ? 0.6 : 1, display: 'flex', alignItems: 'center', gap: 6 }}>
                                {createMut.isPending ? <><Loader2 size={13} style={{ animation: 'spin 1s linear infinite' }} /> Saving…</> : 'Save Record'}
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {/* ── Records Table ── */}
            <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 2px 8px rgba(0,0,0,.07)', overflow: 'hidden', border: '1px solid #f1f5f9' }}>

                {/* Toolbar */}
                <div style={{ padding: '12px 16px', background: color.bg, borderBottom: `1.5px solid ${color.border}22`, display: 'flex', alignItems: 'center', gap: 10 }}>
                    <span style={{ fontSize: 13, fontWeight: 700, color: color.text, flex: 1 }}>
                        Production Records
                        <span style={{ fontSize: 11, fontWeight: 400, color: `${color.text}88`, marginLeft: 6 }}>({filtered.length})</span>
                    </span>
                    <div style={{ position: 'relative' }}>
                        <Search size={12} style={{ position: 'absolute', left: 9, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                        <input type="text" placeholder="Filter…" value={search} onChange={e => setSearch(e.target.value)} style={{ ...inp, paddingLeft: 28, fontSize: 12, width: 150 }} />
                    </div>
                    <button onClick={() => setSortDir(d => d === 'desc' ? 'asc' : 'desc')}
                        style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '6px 10px', borderRadius: 7, border: `1.5px solid ${color.border}44`, background: '#fff', cursor: 'pointer', fontSize: 12, color: color.text, fontWeight: 600, outline: 'none' }}>
                        {sortDir === 'desc' ? <ChevronDown size={13} /> : <ChevronUp size={13} />} Date
                    </button>
                </div>

                {isLoading ? (
                    <div style={{ padding: '44px 0', textAlign: 'center', color: '#94a3b8', fontSize: 13, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                        <Loader2 size={16} style={{ animation: 'spin 1s linear infinite' }} /> Loading…
                    </div>
                ) : filtered.length === 0 ? (
                    <div style={{ padding: '44px 0', textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>Belum ada production records.</div>
                ) : (
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                            <thead>
                                <tr style={{ background: `${color.border}12` }}>
                                    {['No','Foto Operator','Nama Operator','Part / Component','Qty','Description','Status'].map(h => (
                                        <th key={h} style={{ padding: '9px 14px', textAlign: 'left', fontSize: 10, fontWeight: 700, color: color.text, textTransform: 'uppercase', letterSpacing: '.06em', whiteSpace: 'nowrap' }}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.map((r, i) => {
                                    const isExpanded  = expandedUid === r.uid;
                                    const isInspected = !!r.status;
                                    const qf          = r.qty_fail ?? 0;

                                    return (
                                        <React.Fragment key={r.uid}>
                                            {/* ── Main row ── */}
                                            <tr
                                                style={{ borderTop: '1px solid #f1f5f9', background: isExpanded ? color.bg : '#fff', cursor: 'pointer', transition: 'background .1s' }}
                                                onMouseEnter={e => { if (!isExpanded) e.currentTarget.style.background = `${color.bg}`; }}
                                                onMouseLeave={e => { if (!isExpanded) e.currentTarget.style.background = '#fff'; }}
                                                onClick={() => toggle(r.uid)}
                                            >
                                                {/* No */}
                                                <td style={{ padding: '11px 14px', color: '#94a3b8', fontSize: 11, fontWeight: 600, whiteSpace: 'nowrap' }}>
                                                    <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                                                        <ChevronRight size={12} color={color.text} style={{ transform: isExpanded ? 'rotate(90deg)' : 'rotate(0)', transition: 'transform .2s', flexShrink: 0 }} />
                                                        {i + 1}
                                                    </span>
                                                </td>

                                                {/* Foto Operator (avatar) */}
                                                <td style={{ padding: '11px 14px' }}>
                                                    <div style={{ width: 36, height: 36, borderRadius: '50%', background: color.border, color: '#fff', fontSize: 12, fontWeight: 700, display: 'flex', alignItems: 'center', justifyContent: 'center', letterSpacing: '-.02em', flexShrink: 0 }}>
                                                        {initials(r.operator)}
                                                    </div>
                                                </td>

                                                {/* Nama Operator */}
                                                <td style={{ padding: '11px 14px', minWidth: 130 }}>
                                                    <div style={{ fontSize: 12, fontWeight: 600, color: '#1e293b', whiteSpace: 'nowrap' }}>{r.operator ?? '—'}</div>
                                                    <div style={{ fontSize: 10, color: '#94a3b8', marginTop: 1 }}>{r.date ?? '—'}</div>
                                                </td>

                                                {/* Part / Component */}
                                                <td style={{ padding: '11px 14px' }}>
                                                    <span style={{ fontSize: 12, fontWeight: 600, padding: '3px 10px', borderRadius: 999, background: color.bg, color: color.text, border: `1px solid ${color.border}33`, whiteSpace: 'nowrap', display: 'inline-block' }}>
                                                        {r.part ?? '—'}
                                                    </span>
                                                </td>

                                                {/* Qty */}
                                                <td style={{ padding: '11px 14px', whiteSpace: 'nowrap' }}>
                                                    {isInspected ? (
                                                        <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                                            <span style={{ fontSize: 11, fontWeight: 700, color: '#16a34a', display: 'flex', alignItems: 'center', gap: 3 }}><CheckCircle2 size={10} /> {r.qty_pass ?? 0}</span>
                                                            {qf > 0 && <span style={{ fontSize: 11, fontWeight: 700, color: '#dc2626', display: 'flex', alignItems: 'center', gap: 3 }}><XCircle size={10} /> {qf}</span>}
                                                        </div>
                                                    ) : (
                                                        <span style={{ fontSize: 15, fontWeight: 800, color: color.text }}>
                                                            {r.qty_produced}<span style={{ fontSize: 10, color: '#94a3b8', fontWeight: 400, marginLeft: 3 }}>pcs</span>
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Description */}
                                                <td style={{ padding: '11px 14px', color: '#64748b', fontSize: 12, maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                                    {r.notes
                                                        ? <span style={{ fontStyle: 'italic' }}>{r.notes}</span>
                                                        : <span style={{ color: '#cbd5e1' }}>—</span>}
                                                </td>

                                                {/* Status */}
                                                <td style={{ padding: '11px 14px' }}>
                                                    {!r.status ? (
                                                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 10, fontWeight: 700, padding: '4px 10px', borderRadius: 999, background: '#fef9c3', color: '#92400e', border: '1px solid #fde68a', cursor: 'pointer', whiteSpace: 'nowrap' }}>
                                                            Inspect ›
                                                        </span>
                                                    ) : r.status === 'PASS' ? (
                                                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 10, fontWeight: 700, padding: '4px 10px', borderRadius: 999, background: '#dcfce7', color: '#166534', whiteSpace: 'nowrap' }}>
                                                            <CheckCircle2 size={11} /> PASS
                                                        </span>
                                                    ) : (
                                                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 10, fontWeight: 700, padding: '4px 10px', borderRadius: 999, background: '#fee2e2', color: '#991b1b', whiteSpace: 'nowrap' }}>
                                                            <XCircle size={11} /> FAIL
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>

                                            {/* ── Expanded inspection row ── */}
                                            {isExpanded && (
                                                <tr>
                                                    <td colSpan={7} style={{ padding: 0 }}>
                                                        {!r.status
                                                            ? <InspectPanel record={r} projectUid={projectUid} stage={stage} color={color} historyNotes={historyNotes} historyActions={historyActions} />
                                                            : <InspectionSummary record={r} />
                                                        }
                                                        <PhotoSection record={r} projectUid={projectUid} stage={stage} color={color} />
                                                    </td>
                                                </tr>
                                            )}
                                        </React.Fragment>
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
