import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateChecklistItem } from '../../api/checklist';
import { CheckCircle2, XCircle, Circle, ChevronDown, ChevronRight, AlertCircle } from 'lucide-react';

const SECTIONS = [
    { id: 1,  name: 'Body & Structure', items: [
        { id: 1, name: 'Body shape symmetrical' }, { id: 2, name: 'Foam density even' },
        { id: 3, name: 'Seam quality OK' }, { id: 4, name: 'No visible gaps' }, { id: 5, name: 'Base plate stable' },
    ]},
    { id: 2,  name: 'Head & Face', items: [
        { id: 6, name: 'Head shape correct' }, { id: 7, name: 'Eye alignment' }, { id: 8, name: 'Mouth / nose finish' },
    ]},
    { id: 3,  name: 'Fabric & Color', items: [
        { id: 9, name: 'Color match reference' }, { id: 10, name: 'No fabric fraying' },
        { id: 11, name: 'Print quality' }, { id: 12, name: 'Stitch density' }, { id: 13, name: 'No discoloration' },
    ]},
    { id: 4,  name: 'Accessories', items: [
        { id: 14, name: 'Accessories attached' }, { id: 15, name: 'Accessories color match' }, { id: 16, name: 'No sharp edges' },
    ]},
    { id: 5,  name: 'Hands & Feet', items: [
        { id: 17, name: 'Hand shape correct' }, { id: 18, name: 'Fingers formed properly' },
        { id: 19, name: 'Feet shape correct' }, { id: 20, name: 'Shoe cover fit' }, { id: 21, name: 'Claw / nail finish' },
    ]},
    { id: 6,  name: 'Wearability', items: [
        { id: 22, name: 'Vision OK' }, { id: 23, name: 'Ventilation OK' }, { id: 24, name: 'Easy to wear / remove' },
    ]},
    { id: 7,  name: 'Harness & Comfort', items: [
        { id: 25, name: 'Harness fits properly' }, { id: 26, name: 'Padding in place' }, { id: 27, name: 'No internal protrusions' },
    ]},
    { id: 8,  name: 'Final Visual', items: [
        { id: 28, name: 'Overall appearance clean' }, { id: 29, name: 'No loose threads' },
        { id: 30, name: 'No dirty spots' }, { id: 31, name: 'Matches reference photo' },
    ]},
    { id: 10, name: 'Tail & Extras', items: [
        { id: 36, name: 'Tail attached securely' }, { id: 37, name: 'Tail shape correct' },
        { id: 38, name: 'Wings / fins secure' }, { id: 39, name: 'Additional props OK' },
    ]},
];

const DEFECT_CATS = ['Sewing', 'Fabric', 'Color', 'Shape', 'Accessories', 'Wearability', 'Finish', 'Other'];

const btnStyle = (bg, color) => ({
    padding: '4px 10px', borderRadius: 6, border: 'none', cursor: 'pointer',
    background: bg, color, fontSize: 11, fontWeight: 600, outline: 'none',
});

const inputStyle = {
    width: '100%', border: '1px solid #e2e8f0', borderRadius: 8,
    padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box', background: '#fff',
};

// ── Fail Dialog ───────────────────────────────────────────────────────

function FailDialog({ item, projectUid, onClose }) {
    const qc = useQueryClient();
    const [form, setForm] = useState({ defect_category: '', severity: 'Major', fail_note: '', note: '' });
    const [err, setErr] = useState(null);
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: () => updateChecklistItem(projectUid, item.id, {
            status: 'FAIL', item_name: item.name,
            defect_category: form.defect_category, severity: form.severity,
            fail_note: form.fail_note, note: form.note || null,
        }),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['project', projectUid] }); onClose(); },
        onError: e => setErr(e.message),
    });

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.4)', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.18)', width: '100%', maxWidth: 420 }}>
                <div style={{ padding: '14px 18px', borderBottom: '1px solid #f1f5f9' }}>
                    <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b' }}>Mark as FAIL — {item.name}</div>
                </div>
                <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 12 }}>
                    {err && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 10px' }}>
                            <AlertCircle size={13} /> {err}
                        </div>
                    )}
                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Defect Category *</div>
                        <select style={inputStyle} value={form.defect_category} onChange={e => set('defect_category', e.target.value)}>
                            <option value="">Select…</option>
                            {DEFECT_CATS.map(c => <option key={c} value={c}>{c}</option>)}
                        </select>
                    </div>
                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Severity *</div>
                        <div style={{ display: 'flex', gap: 16 }}>
                            {['Critical', 'Major'].map(s => (
                                <label key={s} style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13, cursor: 'pointer' }}>
                                    <input type="radio" value={s} checked={form.severity === s} onChange={() => set('severity', s)} style={{ accentColor: '#6366f1' }} />
                                    <span style={{ color: s === 'Critical' ? '#dc2626' : '#d97706', fontWeight: 500 }}>{s}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Fail Note *</div>
                        <textarea rows={3} style={{ ...inputStyle, resize: 'none' }}
                            placeholder="Describe the defect…"
                            value={form.fail_note} onChange={e => set('fail_note', e.target.value)} />
                    </div>
                    <div>
                        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 4 }}>Inspector Note (optional)</div>
                        <input type="text" style={inputStyle} placeholder="Internal note"
                            value={form.note} onChange={e => set('note', e.target.value)} />
                    </div>
                </div>
                <div style={{ padding: '12px 18px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                    <button onClick={onClose} style={btnStyle('#f1f5f9', '#475569')}>Cancel</button>
                    <button onClick={() => mut.mutate()}
                        disabled={!form.defect_category || !form.fail_note || mut.isPending}
                        style={{ ...btnStyle('#ef4444', '#fff'), padding: '4px 14px', opacity: (!form.defect_category || !form.fail_note || mut.isPending) ? 0.5 : 1 }}>
                        {mut.isPending ? 'Saving…' : 'Confirm FAIL'}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Item Row ──────────────────────────────────────────────────────────

function ItemRow({ item, projectUid, checklistMap }) {
    const qc = useQueryClient();
    const [failOpen, setFailOpen] = useState(false);
    const existing = checklistMap[item.id];
    const status = existing?.status ?? null;

    const passMut = useMutation({
        mutationFn: () => updateChecklistItem(projectUid, item.id, { status: 'PASS', item_name: item.name }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['project', projectUid] }),
    });

    const rowBg = status === 'PASS' ? '#f0fdf4' : status === 'FAIL' ? '#fef2f2' : '#fff';

    return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 10px', borderRadius: 8, background: rowBg, transition: 'background .15s' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, minWidth: 0 }}>
                {status === 'PASS'
                    ? <CheckCircle2 size={15} style={{ color: '#22c55e', flexShrink: 0 }} />
                    : status === 'FAIL'
                    ? <XCircle size={15} style={{ color: '#ef4444', flexShrink: 0 }} />
                    : <Circle size={15} style={{ color: '#cbd5e1', flexShrink: 0 }} />}
                <span style={{ fontSize: 13, color: status === 'PASS' ? '#15803d' : status === 'FAIL' ? '#b91c1c' : '#334155', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {item.name}
                </span>
            </div>
            {status === null && (
                <div style={{ display: 'flex', gap: 4, flexShrink: 0 }}>
                    <button onClick={() => passMut.mutate()} disabled={passMut.isPending}
                        style={{ ...btnStyle('#dcfce7', '#15803d'), opacity: passMut.isPending ? 0.5 : 1 }}>Pass</button>
                    <button onClick={() => setFailOpen(true)}
                        style={btnStyle('#fee2e2', '#b91c1c')}>Fail</button>
                </div>
            )}
            {failOpen && <FailDialog item={item} projectUid={projectUid} onClose={() => setFailOpen(false)} />}
        </div>
    );
}

// ── Section Accordion ─────────────────────────────────────────────────

function SectionAccordion({ section, projectUid, checklistMap }) {
    const [open, setOpen] = useState(true);
    const passCount = section.items.filter(i => checklistMap[i.id]?.status === 'PASS').length;
    const failCount = section.items.filter(i => checklistMap[i.id]?.status === 'FAIL').length;

    return (
        <div style={{ borderRadius: 10, overflow: 'hidden', background: '#f8fafc' }}>
            <button onClick={() => setOpen(o => !o)}
                style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 12px', background: '#f1f5f9', border: 'none', cursor: 'pointer', outline: 'none' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                    {open ? <ChevronDown size={13} color="#64748b" /> : <ChevronRight size={13} color="#64748b" />}
                    <span style={{ fontSize: 13, fontWeight: 600, color: '#334155' }}>{section.name}</span>
                </div>
                <div style={{ display: 'flex', gap: 8, fontSize: 11 }}>
                    <span style={{ color: '#22c55e', fontWeight: 600 }}>{passCount}✓</span>
                    {failCount > 0 && <span style={{ color: '#ef4444', fontWeight: 600 }}>{failCount}✗</span>}
                    <span style={{ color: '#94a3b8' }}>{section.items.length} items</span>
                </div>
            </button>
            {open && (
                <div style={{ padding: '6px 8px', display: 'flex', flexDirection: 'column', gap: 2 }}>
                    {section.items.map(item => (
                        <ItemRow key={item.id} item={item} projectUid={projectUid} checklistMap={checklistMap} />
                    ))}
                </div>
            )}
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────

export default function ChecklistTab({ project }) {
    const checklistMap = {};
    (project.checklist_items ?? []).forEach(ci => { checklistMap[ci.item_id] = ci; });
    const total = SECTIONS.reduce((s, sec) => s + sec.items.length, 0);
    const passed = (project.checklist_items ?? []).filter(ci => ci.status === 'PASS').length;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            <div style={{ fontSize: 12, color: '#94a3b8', marginBottom: 4 }}>{passed} / {total} items passed</div>
            {SECTIONS.map(sec => (
                <SectionAccordion key={sec.id} section={sec} projectUid={project.uid} checklistMap={checklistMap} />
            ))}
        </div>
    );
}
