import React, { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getDailyProgress, upsertDailyProgress, updateDailyItem } from '../../api/dailyProgress';
import { getEmployees } from '../../api/employees';
import { addCustomPart } from '../../api/projects';
import { Calendar, Plus, X, CheckCircle2, XCircle, Clock, Tag } from 'lucide-react';

const DAILY_SECTIONS = [
    { id: 'A', name: 'Struktur & Material', items: [
        { id: 'dp1',  name: 'Rangka / struktur sesuai dimensi spec dan terasa kuat' },
        { id: 'dp2',  name: 'Material struktur (PVC / foam / wire mesh) bebas dari cacat atau retak' },
        { id: 'dp3',  name: 'Foam / padding terpasang merata dan simetris pada semua sisi' },
        { id: 'dp4',  name: 'Sambungan antar komponen struktur kuat dan tidak ada yang longgar' },
    ]},
    { id: 'B', name: 'Wrapping & Surface', items: [
        { id: 'dp5',  name: 'Kain wrapping tertempel merata, tidak ada gelembung udara atau lipatan' },
        { id: 'dp6',  name: 'Jahitan pada kain wrapping rapi, kuat, dan tidak terlihat dari depan' },
        { id: 'dp7',  name: 'Permukaan bebas dari noda, sobekan, atau cacat yang tampak' },
        { id: 'dp8',  name: 'Warna dan tekstur kain wrapping sesuai referensi warna job order' },
    ]},
    { id: 'C', name: 'Painting & Airbrush', items: [
        { id: 'dp9',  name: 'Warna cat / airbrush sesuai referensi warna dan konsisten di seluruh area' },
        { id: 'dp10', name: 'Tidak ada tetesan cat, area tidak rata, atau kebocoran warna' },
        { id: 'dp11', name: 'Transisi warna (shading / blending) sesuai desain yang disetujui' },
        { id: 'dp12', name: 'Permukaan cat kering sempurna, tidak lengket atau masih basah' },
    ]},
    { id: 'D', name: 'Assembly & Komponen', items: [
        { id: 'dp13', name: 'Mata / aksesori terpasang aman, posisi simetris dan sesuai desain' },
        { id: 'dp14', name: 'Jahitan tangan rapi, tersembunyi, dan kuat di semua titik assembly' },
        { id: 'dp15', name: 'Semua bagian / komponen terhubung kuat — tidak ada yang longgar' },
        { id: 'dp16', name: 'Dimensi keseluruhan sesuai spec (tinggi, lebar, proporsi)' },
    ]},
];

// Default mascot part/component list
const DEFAULT_PARTS = [
    'Body Mascot', 'Body Suit', 'Body Pad', 'Shirt',
    'Kepala / Head', 'Mata / Eyes', 'Hidung / Nose', 'Mulut / Mouth',
    'Tangan / Hands', 'Kaki / Feet', 'Sepatu / Shoes',
    'Ekor / Tail', 'Harness', 'Fan', 'Cable', 'Battery', 'Charger',
    'Remote', 'Aksesori', 'Handle', 'Standy',
];

const TOTAL = DAILY_SECTIONS.reduce((acc, s) => acc + s.items.length, 0); // 16

// ── Part chips inline ─────────────────────────────────────────────────

function PartChips({ parts, allParts, onAdd, onRemove, onAddCustom, disabled }) {
    const [open, setOpen]       = useState(false);
    const [customText, setCustom] = useState('');
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const h = e => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', h);
        return () => document.removeEventListener('mousedown', h);
    }, [open]);

    const available = allParts.filter(p => !parts.includes(p));

    return (
        <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 4 }}>
            {parts.map(part => (
                <span key={part} style={{ display: 'inline-flex', alignItems: 'center', gap: 3, padding: '2px 8px 2px 10px', borderRadius: 999, fontSize: 11, fontWeight: 500, background: '#fff7ed', color: '#c2410c' }}>
                    {part}
                    {!disabled && (
                        <button onClick={() => onRemove(part)} style={{ background: 'none', border: 'none', cursor: 'pointer', display: 'flex', padding: 0, color: '#fdba74', outline: 'none', marginLeft: 1 }}>
                            <X size={10} />
                        </button>
                    )}
                </span>
            ))}
            {!disabled && (
                <div style={{ position: 'relative' }} ref={ref}>
                    <button onClick={() => setOpen(o => !o)}
                        style={{ display: 'inline-flex', alignItems: 'center', gap: 3, padding: '2px 8px', borderRadius: 999, border: '1px dashed #fed7aa', background: 'none', cursor: 'pointer', fontSize: 11, color: '#94a3b8', outline: 'none' }}>
                        <Plus size={10} /> Add Part
                    </button>
                    {open && (
                        <div style={{ position: 'absolute', top: 'calc(100% + 4px)', left: 0, zIndex: 200, background: '#fff', borderRadius: 10, boxShadow: '0 4px 20px rgba(0,0,0,.12)', minWidth: 190, maxHeight: 220, overflowY: 'auto', padding: '4px 0' }}>
                            {/* Custom input */}
                            <div style={{ padding: '6px 10px', borderBottom: '1px solid #f1f5f9', display: 'flex', gap: 4 }}>
                                <input
                                    placeholder="Custom part..."
                                    value={customText}
                                    onChange={e => setCustom(e.target.value)}
                                    onKeyDown={e => {
                                        if (e.key === 'Enter' && customText.trim()) {
                                            onAddCustom(customText.trim());
                                            onAdd(customText.trim());
                                            setCustom('');
                                            setOpen(false);
                                        }
                                    }}
                                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 6, padding: '4px 8px', fontSize: 12, outline: 'none' }}
                                />
                                <button
                                    onClick={() => {
                                        if (customText.trim()) {
                                            onAddCustom(customText.trim());
                                            onAdd(customText.trim());
                                            setCustom('');
                                            setOpen(false);
                                        }
                                    }}
                                    style={{ padding: '4px 8px', borderRadius: 6, border: 'none', background: '#f97316', color: '#fff', cursor: 'pointer', fontSize: 11, fontWeight: 600, outline: 'none' }}>
                                    +
                                </button>
                            </div>
                            {/* Preset list */}
                            {available.length === 0
                                ? <div style={{ padding: '10px 12px', fontSize: 12, color: '#94a3b8' }}>All parts selected</div>
                                : available.map(part => (
                                    <button key={part}
                                        onClick={() => { onAdd(part); setOpen(false); }}
                                        style={{ width: '100%', textAlign: 'left', padding: '7px 12px', background: 'none', border: 'none', cursor: 'pointer', fontSize: 12, color: '#334155', outline: 'none', display: 'block' }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#fff7ed'}
                                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
                                        {part}
                                    </button>
                                ))
                            }
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── Operator chips inline ─────────────────────────────────────────────

function OperatorChips({ operators, employees, onAdd, onRemove, disabled }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const h = e => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', h);
        return () => document.removeEventListener('mousedown', h);
    }, [open]);

    const available = employees.filter(e => !operators.includes(e.id));

    return (
        <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 4 }}>
            {operators.map(id => {
                const emp = employees.find(e => e.id === id);
                return (
                    <span key={id} style={{ display: 'inline-flex', alignItems: 'center', gap: 3, padding: '2px 8px 2px 10px', borderRadius: 999, fontSize: 11, fontWeight: 500, background: '#eef2ff', color: '#4f46e5' }}>
                        {emp?.name ?? id}
                        {!disabled && (
                            <button onClick={() => onRemove(id)} style={{ background: 'none', border: 'none', cursor: 'pointer', display: 'flex', padding: 0, color: '#a5b4fc', outline: 'none', marginLeft: 1 }}>
                                <X size={10} />
                            </button>
                        )}
                    </span>
                );
            })}
            {!disabled && (
                <div style={{ position: 'relative' }} ref={ref}>
                    <button onClick={() => setOpen(o => !o)}
                        style={{ display: 'inline-flex', alignItems: 'center', gap: 3, padding: '2px 8px', borderRadius: 999, border: '1px dashed #cbd5e1', background: 'none', cursor: 'pointer', fontSize: 11, color: '#94a3b8', outline: 'none' }}>
                        <Plus size={10} /> Add Operator
                    </button>
                    {open && (
                        <div style={{ position: 'absolute', top: 'calc(100% + 4px)', left: 0, zIndex: 200, background: '#fff', borderRadius: 10, boxShadow: '0 4px 20px rgba(0,0,0,.12)', minWidth: 170, maxHeight: 200, overflowY: 'auto', padding: '4px 0' }}>
                            {available.length === 0
                                ? <div style={{ padding: '10px 12px', fontSize: 12, color: '#94a3b8' }}>All assigned</div>
                                : available.map(emp => (
                                    <button key={emp.id}
                                        onClick={() => { onAdd(emp.id); setOpen(false); }}
                                        style={{ width: '100%', textAlign: 'left', padding: '7px 12px', background: 'none', border: 'none', cursor: 'pointer', fontSize: 12, color: '#334155', outline: 'none', display: 'block' }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
                                        {emp.name}
                                        {emp.position && <span style={{ color: '#94a3b8', fontSize: 10, marginLeft: 4 }}>{emp.position}</span>}
                                    </button>
                                ))
                            }
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── Item Row ──────────────────────────────────────────────────────────

function ItemRow({ num, itemDef, entry, projectUid, date, employees, allParts, onAddCustomPart }) {
    const qc = useQueryClient();
    const status    = entry?.status ?? null;
    const operators = entry?.operators ?? [];
    const parts     = entry?.parts_data ?? [];
    const note      = entry?.note ?? '';

    const mut = useMutation({
        mutationFn: (data) => updateDailyItem(projectUid, date, itemDef.id, data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['daily', projectUid, date] }),
    });

    const borderColor = status === 'PASS' ? '#86efac' : status === 'FAIL' ? '#fca5a5' : '#f1f5f9';
    const bgColor     = status === 'PASS' ? '#f0fdf4' : status === 'FAIL' ? '#fff8f8' : '#fff';

    const StatusIcon = status === 'PASS' ? CheckCircle2 : status === 'FAIL' ? XCircle : Clock;
    const iconColor  = status === 'PASS' ? '#16a34a' : status === 'FAIL' ? '#dc2626' : '#cbd5e1';
    const iconLabel  = status === 'PASS' ? 'Pass' : status === 'FAIL' ? 'Fail' : 'Ongoing';

    return (
        <div style={{ borderRadius: 10, border: `1px solid ${borderColor}`, background: bgColor, transition: 'all .15s' }}>
            <div style={{ padding: '10px 12px' }}>
                {/* Number + name + status icon */}
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 8, marginBottom: 8 }}>
                    <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 700, minWidth: 20, marginTop: 1, textAlign: 'right', flexShrink: 0 }}>{num}</span>
                    <span style={{ flex: 1, fontSize: 13, color: '#334155', fontWeight: 500, lineHeight: 1.55 }}>{itemDef.name}</span>
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 3, flexShrink: 0, marginTop: 1 }}>
                        <StatusIcon size={14} style={{ color: iconColor }} />
                        <span style={{ fontSize: 10, color: iconColor, fontWeight: 600 }}>{iconLabel}</span>
                    </div>
                </div>

                {/* Operator chips */}
                <div style={{ paddingLeft: 28, marginBottom: 6 }}>
                    <OperatorChips
                        operators={operators}
                        employees={employees}
                        onAdd={id => mut.mutate({ operators: [...operators, id] })}
                        onRemove={id => mut.mutate({ operators: operators.filter(o => o !== id) })}
                        disabled={mut.isPending}
                    />
                </div>

                {/* Part chips */}
                <div style={{ paddingLeft: 28, marginBottom: 8 }}>
                    <PartChips
                        parts={parts}
                        allParts={allParts}
                        onAdd={part => mut.mutate({ parts_data: [...parts, part] })}
                        onRemove={part => mut.mutate({ parts_data: parts.filter(p => p !== part) })}
                        onAddCustom={onAddCustomPart}
                        disabled={mut.isPending}
                    />
                </div>

                {/* Pass / Fail buttons */}
                <div style={{ paddingLeft: 28, display: 'flex', gap: 6 }}>
                    <button onClick={() => mut.mutate({ status: 'PASS' })} disabled={mut.isPending}
                        style={{ padding: '4px 16px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, transition: 'all .15s', opacity: mut.isPending ? 0.6 : 1, background: status === 'PASS' ? '#16a34a' : '#f1f5f9', color: status === 'PASS' ? '#fff' : '#64748b' }}>
                        Pass
                    </button>
                    <button onClick={() => mut.mutate({ status: 'FAIL' })} disabled={mut.isPending}
                        style={{ padding: '4px 16px', borderRadius: 8, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 12, fontWeight: 600, transition: 'all .15s', opacity: mut.isPending ? 0.6 : 1, background: status === 'FAIL' ? '#dc2626' : '#f1f5f9', color: status === 'FAIL' ? '#fff' : '#64748b' }}>
                        Fail
                    </button>
                </div>

                {/* Note — only visible on FAIL */}
                {status === 'FAIL' && (
                    <div style={{ paddingLeft: 28, marginTop: 8 }}>
                        <textarea rows={2} placeholder="Note..."
                            key={entry?.uid ?? itemDef.id}
                            defaultValue={note}
                            style={{ width: '100%', border: '1px solid #fecaca', borderRadius: 6, padding: '6px 8px', fontSize: 12, outline: 'none', resize: 'none', boxSizing: 'border-box', background: '#fef2f2', color: '#334155', fontFamily: 'inherit' }}
                            onBlur={e => { if (e.target.value !== note) mut.mutate({ note: e.target.value }); }} />
                    </div>
                )}
            </div>
        </div>
    );
}

// ── Section ───────────────────────────────────────────────────────────

function DailySection({ section, itemMap, projectUid, date, employees, startNum, allParts, onAddCustomPart }) {
    const [open, setOpen] = useState(true);
    const total     = section.items.length;
    const passCount = section.items.filter(i => itemMap[i.id]?.status === 'PASS').length;
    const failCount = section.items.filter(i => itemMap[i.id]?.status === 'FAIL').length;
    const checked   = passCount + failCount;

    const barColor = failCount > 0 ? '#ef4444' : checked === total ? '#22c55e' : '#6366f1';

    return (
        <div style={{ borderRadius: 12, overflow: 'hidden', background: '#fff', boxShadow: '0 1px 3px rgba(0,0,0,.05)' }}>
            <button onClick={() => setOpen(o => !o)}
                style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '11px 14px', background: '#f8fafc', border: 'none', cursor: 'pointer', outline: 'none' }}>
                <span style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>{section.name}</span>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <div style={{ width: 56, height: 4, background: '#e2e8f0', borderRadius: 999 }}>
                        <div style={{ width: `${(checked / total) * 100}%`, height: '100%', background: barColor, borderRadius: 999, transition: 'width .3s' }} />
                    </div>
                    <span style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8', minWidth: 28, textAlign: 'right' }}>{checked}/{total}</span>
                </div>
            </button>
            {open && (
                <div style={{ padding: '8px 10px', display: 'flex', flexDirection: 'column', gap: 6 }}>
                    {section.items.map((itemDef, idx) => (
                        <ItemRow
                            key={itemDef.id}
                            num={startNum + idx}
                            itemDef={itemDef}
                            entry={itemMap[itemDef.id]}
                            projectUid={projectUid}
                            date={date}
                            employees={employees}
                            allParts={allParts}
                            onAddCustomPart={onAddCustomPart}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

// ── Summary card ──────────────────────────────────────────────────────

function SumCard({ value, label, color, bg }) {
    return (
        <div style={{ background: bg, borderRadius: 10, padding: '10px 12px', textAlign: 'center' }}>
            <div style={{ fontSize: 22, fontWeight: 700, color, lineHeight: 1.1 }}>{value}</div>
            <div style={{ fontSize: 10, fontWeight: 600, color, opacity: 0.75, marginTop: 2, letterSpacing: '.04em' }}>{label}</div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────

export default function DailyProgressTab({ project }) {
    const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
    const qc = useQueryClient();

    const sessionDates = (project.daily_progress ?? [])
        .map(dp => dp.date)
        .sort((a, b) => b.localeCompare(a));

    const { data: dp, isLoading } = useQuery({
        queryKey: ['daily', project.uid, date],
        queryFn: () => getDailyProgress(project.uid, date),
    });

    const { data: employees = [] } = useQuery({
        queryKey: ['qc-employees'],
        queryFn: getEmployees,
        staleTime: 5 * 60_000,
    });

    const noteMut = useMutation({
        mutationFn: (data) => upsertDailyProgress(project.uid, date, data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['daily', project.uid, date] }),
    });

    const itemMap = {};
    (dp?.items ?? []).forEach(i => { itemMap[i.item_id] = i; });

    // Merge default parts with project-level custom parts (saved in DB)
    const [customParts, setCustomParts] = useState(project.custom_parts ?? []);
    const allParts = [...DEFAULT_PARTS, ...customParts.filter(p => !DEFAULT_PARTS.includes(p))];

    const customPartMut = useMutation({
        mutationFn: (part) => addCustomPart(project.uid, part),
        onSuccess: (data) => {
            if (data?.custom_parts) setCustomParts(data.custom_parts);
        },
    });

    const handleAddCustomPart = (part) => {
        if (!allParts.includes(part)) customPartMut.mutate(part);
    };

    const passCount    = Object.values(itemMap).filter(i => i.status === 'PASS').length;
    const failCount    = Object.values(itemMap).filter(i => i.status === 'FAIL').length;
    const pendingCount = TOTAL - passCount - failCount;
    const checkedCount = passCount + failCount;
    const passRate     = checkedCount > 0 ? Math.round((passCount / checkedCount) * 100) : 0;

    const formatDateLabel = d =>
        new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>

            {/* ── Header card ── */}
            <div style={{ background: '#fff', borderRadius: 12, padding: 14, boxShadow: '0 1px 3px rgba(0,0,0,.05)', display: 'flex', flexDirection: 'column', gap: 12 }}>

                {/* Date picker */}
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: '#f8fafc', borderRadius: 8, padding: '5px 10px', border: '1px solid #e2e8f0' }}>
                        <Calendar size={13} style={{ color: '#94a3b8' }} />
                        <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 600 }}>Inspection Date</span>
                        <input type="date"
                            style={{ border: 'none', background: 'transparent', fontSize: 13, outline: 'none', color: '#334155', cursor: 'pointer', fontWeight: 600 }}
                            value={date} onChange={e => setDate(e.target.value)} />
                    </div>
                </div>

                {/* Stat pills */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 8 }}>
                    <SumCard value={passCount}                label="Pass"    color="#16a34a" bg="#f0fdf4" />
                    <SumCard value={failCount}                label="Fail"    color="#dc2626" bg="#fef2f2" />
                    <SumCard value={pendingCount}             label="Pending" color="#64748b" bg="#f8fafc" />
                    <SumCard value={`${checkedCount}/${TOTAL}`} label="Checked" color="#6366f1" bg="#eef2ff" />
                </div>

                {/* Session history */}
                {sessionDates.length > 0 && (
                    <div>
                        <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 6 }}>Session History</div>
                        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                            {sessionDates.slice(0, 8).map(d => (
                                <button key={d} onClick={() => setDate(d)}
                                    style={{ padding: '3px 10px', borderRadius: 999, border: 'none', cursor: 'pointer', outline: 'none', fontSize: 11, fontWeight: 500, transition: 'all .15s', background: date === d ? '#6366f1' : '#f1f5f9', color: date === d ? '#fff' : '#64748b' }}>
                                    {formatDateLabel(d)}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Session note */}
                <div>
                    <div style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8', marginBottom: 4 }}>Session Note</div>
                    <textarea rows={2} placeholder="e.g. Morning shift — body assembly..."
                        key={dp?.uid ?? date}
                        defaultValue={dp?.session_note ?? ''}
                        style={{ width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '8px 12px', fontSize: 13, outline: 'none', resize: 'none', boxSizing: 'border-box', color: '#334155', fontFamily: 'inherit', background: '#fafafa' }}
                        onBlur={e => { if (e.target.value !== (dp?.session_note ?? '')) noteMut.mutate({ session_note: e.target.value }); }} />
                </div>
            </div>

            {/* ── Checklist sections ── */}
            {isLoading
                ? <div style={{ textAlign: 'center', color: '#94a3b8', padding: '40px 0' }}>Loading…</div>
                : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                        {DAILY_SECTIONS.map((section, si) => (
                            <DailySection
                                key={section.id}
                                section={section}
                                startNum={si * 4 + 1}
                                itemMap={itemMap}
                                projectUid={project.uid}
                                date={date}
                                employees={employees}
                                allParts={allParts}
                                onAddCustomPart={handleAddCustomPart}
                            />
                        ))}
                    </div>
                )
            }

            {/* ── Daily summary ── */}
            <div style={{ background: '#fff', borderRadius: 12, padding: 14, boxShadow: '0 1px 3px rgba(0,0,0,.05)' }}>
                <div style={{ fontSize: 12, fontWeight: 600, color: '#64748b', marginBottom: 10 }}>
                    Daily Summary — {formatDateLabel(date)}
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 8 }}>
                    <SumCard value={passCount}    label="PASS"      color="#16a34a" bg="#f0fdf4" />
                    <SumCard value={failCount}    label="FAIL"      color="#dc2626" bg="#fef2f2" />
                    <SumCard value={pendingCount} label="PENDING"   color="#64748b" bg="#f8fafc" />
                    <SumCard value={`${passRate}%`} label="PASS RATE" color="#6366f1" bg="#eef2ff" />
                </div>
            </div>
        </div>
    );
}
