import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getStageRecords, inspectRecord } from '../../../api/stageProduction';
import { uploadPhoto } from '../../../api/photos';
import { STAGE_COLORS, DEFECT_CATEGORIES, SEVERITY_LEVELS } from '../../../data/models';
import { ClipboardCheck, Camera, X, AlertCircle, CheckCircle2, XCircle, Image } from 'lucide-react';

const inp = {
    width: '100%', border: '1px solid #e2e8f0', borderRadius: 8,
    padding: '7px 10px', fontSize: 13, outline: 'none', boxSizing: 'border-box', background: '#fff',
};
const lbl = { fontSize: 11, fontWeight: 600, color: '#64748b', display: 'block', marginBottom: 4 };

// ── Inspection Form ───────────────────────────────────────────────────

function InspectionForm({ record, projectUid, stage, onDone }) {
    const qc    = useQueryClient();
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const [form, setForm] = useState({
        qty_pass: record.qty_produced ?? 0,
        qty_fail: 0,
        defect_category: '',
        defect_desc: '',
        severity: 'Major',
    });
    const [photos, setPhotos]     = useState([]);
    const [uploading, setUploading] = useState(false);
    const [err, setErr]           = useState(null);

    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    const mut = useMutation({
        mutationFn: () => inspectRecord(projectUid, stage, record.uid, form),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['stage-records', projectUid, stage] });
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, stage] });
            qc.invalidateQueries({ queryKey: ['stage-gallery', projectUid, stage] });
            onDone();
        },
        onError: e => setErr(e.message),
    });

    const handlePhoto = async (file) => {
        setUploading(true);
        try {
            const res = await uploadPhoto(file, 'daily_item', record.uid, { context: 'inspection' });
            setPhotos(p => [...p, res]);
        } catch {
            setErr('Photo upload failed.');
        }
        setUploading(false);
    };

    const hasFail = form.qty_fail > 0;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {err && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '7px 10px' }}>
                    <AlertCircle size={13} /> {err}
                </div>
            )}

            {/* Qty fields */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                <div>
                    <span style={lbl}>Qty Pass *</span>
                    <input type="number" min="0" max={record.qty_produced} style={inp}
                        value={form.qty_pass}
                        onChange={e => {
                            const p = parseInt(e.target.value) || 0;
                            set('qty_pass', p);
                            set('qty_fail', Math.max(0, (record.qty_produced ?? 0) - p));
                        }} />
                </div>
                <div>
                    <span style={lbl}>Qty Fail</span>
                    <input type="number" min="0" style={{ ...inp, background: form.qty_fail > 0 ? '#fef2f2' : '#fff', color: form.qty_fail > 0 ? '#dc2626' : undefined }}
                        value={form.qty_fail}
                        onChange={e => {
                            const f = parseInt(e.target.value) || 0;
                            set('qty_fail', f);
                            set('qty_pass', Math.max(0, (record.qty_produced ?? 0) - f));
                        }} />
                </div>
            </div>

            {/* Defect fields — shown only when fail > 0 */}
            {hasFail && (
                <>
                    <div>
                        <span style={lbl}>Defect Category *</span>
                        <select style={inp} value={form.defect_category} onChange={e => set('defect_category', e.target.value)}>
                            <option value="">— Select —</option>
                            {DEFECT_CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
                        </select>
                    </div>
                    <div>
                        <span style={lbl}>Defect Description</span>
                        <textarea rows={2} style={{ ...inp, resize: 'vertical' }}
                            value={form.defect_desc} onChange={e => set('defect_desc', e.target.value)}
                            placeholder="Describe the defect…" />
                    </div>
                    <div>
                        <span style={lbl}>Severity</span>
                        <div style={{ display: 'flex', gap: 10 }}>
                            {['Major', 'Critical'].map(s => (
                                <label key={s} style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 13, cursor: 'pointer' }}>
                                    <input type="radio" name={`sev-${record.uid}`} value={s}
                                        checked={form.severity === s} onChange={() => set('severity', s)}
                                        style={{ accentColor: s === 'Critical' ? '#dc2626' : '#d97706' }} />
                                    <span style={{ color: s === 'Critical' ? '#dc2626' : '#d97706', fontWeight: 600 }}>{s}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Photo upload */}
            <div>
                <span style={lbl}>Photos</span>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
                    {photos.map(p => (
                        <img key={p.uid} src={p.url} alt="" style={{ width: 52, height: 52, objectFit: 'cover', borderRadius: 8, border: '1px solid #e2e8f0' }} />
                    ))}
                    <label style={{ width: 52, height: 52, borderRadius: 8, border: '2px dashed #e2e8f0', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', gap: 2, background: '#f8fafc', fontSize: 9, color: '#94a3b8' }}>
                        <Image size={16} />File
                        <input type="file" accept="image/*" style={{ display: 'none' }}
                            onChange={e => { if (e.target.files[0]) handlePhoto(e.target.files[0]); }} />
                    </label>
                    <label style={{ width: 52, height: 52, borderRadius: 8, border: '2px dashed #e2e8f0', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', gap: 2, background: '#f8fafc', fontSize: 9, color: '#94a3b8' }}>
                        <Camera size={16} />Cam
                        <input type="file" accept="image/*" capture="environment" style={{ display: 'none' }}
                            onChange={e => { if (e.target.files[0]) handlePhoto(e.target.files[0]); }} />
                    </label>
                    {uploading && <span style={{ fontSize: 11, color: '#94a3b8' }}>Uploading…</span>}
                </div>
            </div>

            {/* Submit */}
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                <button onClick={onDone}
                    style={{ padding: '7px 14px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#64748b', fontSize: 13, cursor: 'pointer', outline: 'none' }}>
                    Cancel
                </button>
                <button onClick={() => mut.mutate()}
                    disabled={hasFail && !form.defect_category || mut.isPending}
                    style={{ padding: '7px 16px', borderRadius: 8, border: 'none', cursor: 'pointer', background: hasFail ? '#ef4444' : '#22c55e', color: '#fff', fontSize: 13, fontWeight: 600, outline: 'none', opacity: (hasFail && !form.defect_category || mut.isPending) ? 0.6 : 1 }}>
                    {mut.isPending ? 'Saving…' : hasFail ? 'Submit (w/ Reject Log)' : 'Submit Pass'}
                </button>
            </div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────

export default function InspectionTab({ projectUid, stage, project }) {
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const { data: records = [], isLoading } = useQuery({
        queryKey: ['stage-records', projectUid, stage],
        queryFn: () => getStageRecords(projectUid, stage),
        staleTime: 30_000,
    });

    const [activeUid, setActiveUid] = useState(null);

    const pending = records.filter(r => !r.is_finalized);
    const done    = records.filter(r => r.is_finalized);

    if (isLoading) return <div style={{ padding: '40px 0', textAlign: 'center', color: '#94a3b8' }}>Loading…</div>;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div style={{ fontSize: 13, color: '#64748b' }}>
                <strong style={{ color: color.text }}>{pending.length}</strong> pending inspection · {done.length} done
            </div>

            {pending.length === 0 && (
                <div style={{ textAlign: 'center', padding: '40px 0', color: '#94a3b8', fontSize: 13 }}>
                    All records have been inspected.
                </div>
            )}

            {pending.map(r => (
                <div key={r.uid} style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                    <div style={{ padding: '12px 16px', display: 'flex', alignItems: 'center', gap: 12, borderBottom: activeUid === r.uid ? '1px solid #f1f5f9' : 'none', cursor: 'pointer' }}
                        onClick={() => setActiveUid(v => v === r.uid ? null : r.uid)}>
                        <ClipboardCheck size={16} color={color.border} style={{ flexShrink: 0 }} />
                        <div style={{ flex: 1 }}>
                            <div style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>{r.part} — Qty: {r.qty_produced}</div>
                            <div style={{ fontSize: 11, color: '#94a3b8' }}>{r.date} · {r.operator}</div>
                        </div>
                        <span style={{ fontSize: 11, color: color.text, fontWeight: 600, background: color.bg, padding: '2px 8px', borderRadius: 6 }}>Pending</span>
                    </div>
                    {activeUid === r.uid && (
                        <div style={{ padding: '14px 16px' }}>
                            <InspectionForm record={r} projectUid={projectUid} stage={stage} onDone={() => setActiveUid(null)} />
                        </div>
                    )}
                </div>
            ))}

            {/* Inspected records */}
            {done.length > 0 && (
                <div style={{ marginTop: 8 }}>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.05em', marginBottom: 10 }}>Completed</div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                        {done.map(r => (
                            <div key={r.uid} style={{ background: '#f8fafc', borderRadius: 10, padding: '10px 14px', display: 'flex', alignItems: 'center', gap: 10 }}>
                                {r.status === 'PASS' ? <CheckCircle2 size={16} color="#22c55e" /> : <XCircle size={16} color="#ef4444" />}
                                <div style={{ flex: 1 }}>
                                    <span style={{ fontSize: 13, fontWeight: 600, color: '#334155' }}>{r.part}</span>
                                    <span style={{ fontSize: 11, color: '#94a3b8', marginLeft: 8 }}>{r.date} · {r.operator}</span>
                                </div>
                                <span style={{ fontSize: 12, color: '#64748b' }}>Pass: {r.qty_pass ?? 0} · Fail: {r.qty_fail ?? 0}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
