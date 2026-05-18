import React, { useEffect, useRef, useState, useMemo, useCallback } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useApp } from '../../../context/AppContext';
import Handsontable from 'handsontable/base';
import { registerAllModules } from 'handsontable/registry';
import 'handsontable/styles/handsontable.min.css';
import 'handsontable/styles/ht-theme-main.min.css';
import * as XLSX from 'xlsx';
import {
    getStageRejectLogs,
    createStageRejectLog,
    batchCreateRejectLogs,
    updateStageRejectLog,
} from '../../../api/stageProduction';
import {
    Save, Upload, Plus, AlertCircle, X, CheckCircle2,
    FileSpreadsheet, Info, ShieldAlert, ClipboardList, Camera,
} from 'lucide-react';
import { uploadPhoto } from '../../../api/photos';
import { FINISHING_STATUSES, DEFECT_SEVERITIES } from '../../../data/models';

registerAllModules();

// ── Constants ─────────────────────────────────────────────────────────

const STATUS_SOURCE   = FINISHING_STATUSES;
const SEVERITY_SOURCE = DEFECT_SEVERITIES;

const DEFECT_CATEGORIES = [
    'Structural Defect', 'Stitching Defect', 'Surface Defect',
    'Accessory Issue',   'Cutting Error',    'Material Defect',
    'Label Error',       'Size/Shape',       'Colour/Tone',
];

const COMPONENT_PARTS = [
    'Body', 'Head', 'Mouth', 'Hand', 'Ear', 'Tail', 'Nose',
    'Arm',  'Leg',  'Wing',  'Horn', 'Neck', 'Back', 'Fur',
    'Seam', 'Zipper', 'Label', 'Accessory',
];

// ── Columns — no Photos column ─────────────────────────────────────────

const HOT_COLUMNS = [
    { title: 'Component / Part',  data: 'item_name',              type: 'dropdown', source: COMPONENT_PARTS,   strict: false, width: 120 },
    { title: 'Defect Category',   data: 'defect_category',        type: 'dropdown', source: DEFECT_CATEGORIES, strict: false, width: 130 },
    { title: 'Description',       data: 'fail_note',              type: 'text',    width: 160 },
    { title: 'Qty',               data: 'qty_reject',             type: 'numeric', numericFormat: { pattern: '0' }, width: 45  },
    { title: 'Severity',          data: 'severity',               type: 'dropdown', source: SEVERITY_SOURCE,   strict: false, width: 85 },
    { title: 'Root Cause',        data: 'root_cause',             type: 'text',    width: 130 },
    { title: 'Corrective Action', data: 'corrective_action',      type: 'text',    width: 130 },
    { title: 'Target Date',       data: 'target_completion_date', type: 'date',    dateFormat: 'YYYY-MM-DD', correctFormat: true, width: 110 },
    { title: 'Status',            data: 'rework_status',          type: 'dropdown', source: STATUS_SOURCE,    strict: false, width: 75  },
];

// ── Import helpers ────────────────────────────────────────────────────

const IMPORT_MAP = {
    'component': 'item_name', 'component/part': 'item_name', 'component / part': 'item_name',
    'part': 'item_name', 'item': 'item_name', 'item_name': 'item_name', 'item name': 'item_name',
    'defect category': 'defect_category', 'defect_category': 'defect_category',
    'category': 'defect_category', 'defect type': 'defect_category',
    'description': 'fail_note', 'fail_note': 'fail_note', 'fail note': 'fail_note',
    'defect description': 'fail_note',
    'severity': 'severity',
    'qty reject': 'qty_reject', 'qty_reject': 'qty_reject', 'qty fail': 'qty_reject',
    'qty_fail': 'qty_reject', 'quantity': 'qty_reject', 'qty': 'qty_reject',
    'root cause': 'root_cause', 'root_cause': 'root_cause', 'cause': 'root_cause',
    'corrective action': 'corrective_action', 'corrective_action': 'corrective_action',
    'action': 'corrective_action', 'fix': 'corrective_action',
    'target date': 'target_completion_date', 'target_date': 'target_completion_date',
    'target_completion_date': 'target_completion_date', 'due date': 'target_completion_date',
    'status': 'rework_status', 'rework_status': 'rework_status', 'rework status': 'rework_status',
};

function mapImportRow(raw) {
    const mapped = {};
    for (const [col, val] of Object.entries(raw)) {
        const key = IMPORT_MAP[col.toLowerCase().trim()];
        if (key) mapped[key] = String(val ?? '').trim();
    }
    const sev = mapped.severity ?? '';
    const sta = (mapped.rework_status ?? '').toUpperCase();
    return {
        item_name:              mapped.item_name              ?? '',
        defect_category:        mapped.defect_category        ?? 'Other',
        fail_note:              mapped.fail_note              ?? '',
        severity:               SEVERITY_SOURCE.includes(sev) ? sev : 'Major',
        qty_reject:             mapped.qty_reject ? (parseInt(mapped.qty_reject) || null) : null,
        root_cause:             mapped.root_cause             ?? '',
        corrective_action:      mapped.corrective_action      ?? '',
        target_completion_date: mapped.target_completion_date ?? '',
        rework_status:          STATUS_SOURCE.includes(sta)   ? sta : 'OPEN',
    };
}

function parseFileData(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const wb  = XLSX.read(e.target.result, { type: 'array', raw: false });
                const ws  = wb.Sheets[wb.SheetNames[0]];
                const raw = XLSX.utils.sheet_to_json(ws, { defval: '' });
                resolve(raw.map(mapImportRow).filter(r => r.item_name || r.defect_category));
            } catch (err) { reject(err); }
        };
        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
    });
}

// ── Import Preview Modal ──────────────────────────────────────────────

const PREVIEW_COLS = [
    { key: 'item_name',              label: 'Component' },
    { key: 'defect_category',        label: 'Category' },
    { key: 'fail_note',              label: 'Description' },
    { key: 'severity',               label: 'Severity' },
    { key: 'qty_reject',             label: 'Qty' },
    { key: 'root_cause',             label: 'Root Cause' },
    { key: 'corrective_action',      label: 'Corrective Action' },
    { key: 'target_completion_date', label: 'Target Date' },
    { key: 'rework_status',          label: 'Status' },
];

function ImportPreviewModal({ rows, fileName, onClose, onConfirm, loading, error }) {
    const preview = rows.slice(0, 20);
    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, background: 'rgba(0,0,0,.55)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.2)', width: '100%', maxWidth: 860, maxHeight: '88vh', display: 'flex', flexDirection: 'column' }}>
                <div style={{ padding: '12px 18px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <FileSpreadsheet size={16} color="#7e22ce" />
                        <div>
                            <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b' }}>Import Preview</div>
                            <div style={{ fontSize: 11, color: '#94a3b8' }}>
                                {fileName} — {rows.length} row{rows.length !== 1 ? 's' : ''}
                                {rows.length > 20 && ' (showing first 20)'}
                            </div>
                        </div>
                    </div>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 4, outline: 'none' }}>
                        <X size={16} />
                    </button>
                </div>
                {error && (
                    <div style={{ margin: '10px 18px 0', padding: '8px 12px', background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 8, fontSize: 12, color: '#dc2626' }}>
                        {error}
                    </div>
                )}
                <div style={{ flex: 1, overflowY: 'auto', padding: '10px 18px' }}>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#faf5ff' }}>
                                    <th style={{ padding: '6px 8px', fontWeight: 700, color: '#94a3b8', fontSize: 10, width: 28 }}>#</th>
                                    {PREVIEW_COLS.map(c => (
                                        <th key={c.key} style={{ padding: '6px 8px', textAlign: 'left', fontWeight: 700, color: '#7e22ce', fontSize: 10, textTransform: 'uppercase', letterSpacing: '.04em', whiteSpace: 'nowrap' }}>{c.label}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {preview.map((row, i) => (
                                    <tr key={i} style={{ borderTop: '1px solid #f1f5f9', background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                                        <td style={{ padding: '5px 8px', textAlign: 'center', color: '#cbd5e1', fontSize: 11 }}>{i + 1}</td>
                                        {PREVIEW_COLS.map(c => (
                                            <td key={c.key} style={{ padding: '5px 8px', color: '#334155', maxWidth: 120, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                                {row[c.key] ?? '—'}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                                {preview.length === 0 && (
                                    <tr><td colSpan={PREVIEW_COLS.length + 1} style={{ padding: 24, textAlign: 'center', color: '#94a3b8' }}>No valid rows.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div style={{ padding: '10px 18px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8, flexShrink: 0 }}>
                    <button onClick={onClose} style={{ padding: '6px 14px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#f1f5f9', color: '#475569', fontSize: 12, cursor: 'pointer' }}>Cancel</button>
                    <button onClick={onConfirm} disabled={loading || rows.length === 0}
                        style={{ padding: '6px 14px', borderRadius: 8, border: 'none', background: '#7e22ce', color: '#fff', fontSize: 12, fontWeight: 600, cursor: 'pointer', opacity: (loading || rows.length === 0) ? 0.5 : 1 }}>
                        {loading ? 'Importing…' : `Import ${rows.length} Row${rows.length !== 1 ? 's' : ''}`}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Stat Card ─────────────────────────────────────────────────────────

function StatCard({ label, value, bg, text, icon: Icon }) {
    return (
        <div style={{ flex: 1, minWidth: 80, background: bg, borderRadius: 10, padding: '10px 14px', display: 'flex', alignItems: 'center', gap: 8 }}>
            {Icon && <Icon size={15} color={text} style={{ flexShrink: 0, opacity: .8 }} />}
            <div>
                <div style={{ fontSize: 10, color: text, opacity: .75, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.04em' }}>{label}</div>
                <div style={{ fontSize: 18, fontWeight: 800, color: text, lineHeight: 1.2, marginTop: 1 }}>{value}</div>
            </div>
        </div>
    );
}

// ── Photo Panel ────────────────────────────────────────────────────────

function PhotoPanel({ selectedRow, photos, pendingPhotos, onAddPending, onRemovePending, onViewLightbox, saving }) {
    const pending = pendingPhotos || [];

    if (!selectedRow) {
        return (
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '18px 20px', background: '#faf5ff', borderRadius: 10, border: '1.5px dashed #ddd6fe' }}>
                <Camera size={22} color="#ddd6fe" style={{ flexShrink: 0 }} />
                <div>
                    <div style={{ fontSize: 12, fontWeight: 700, color: '#a78bfa' }}>Foto Defect</div>
                    <div style={{ fontSize: 11, color: '#c4b5fd', marginTop: 2 }}>Klik baris di tabel untuk melihat atau upload foto</div>
                </div>
            </div>
        );
    }

    const needSave = !selectedRow.uid;

    return (
        <div style={{ background: '#faf5ff', borderRadius: 10, border: '1.5px solid #e9d5ff', overflow: 'hidden' }}>
            {/* Header */}
            <div style={{ padding: '8px 14px', borderBottom: '1px solid #e9d5ff', display: 'flex', alignItems: 'center', gap: 8 }}>
                <Camera size={13} color="#7c3aed" style={{ flexShrink: 0 }} />
                <span style={{ fontSize: 12, fontWeight: 700, color: '#7e22ce', flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {selectedRow.name || 'Defect'}
                </span>
                <span style={{ fontSize: 10, color: '#94a3b8', flexShrink: 0 }}>{photos.length + pending.length} foto</span>
                {pending.length > 0 && (
                    <span style={{ fontSize: 10, background: '#fef3c7', color: '#92400e', borderRadius: 99, padding: '1px 7px', fontWeight: 600, border: '1px solid #fde68a', flexShrink: 0 }}>
                        {pending.length} belum tersimpan
                    </span>
                )}
            </div>

            {/* Photo grid */}
            <div style={{ padding: '10px 14px', display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'flex-start' }}>
                {/* Saved photos */}
                {photos.map((p, i) => (
                    <div key={p.uid} style={{ position: 'relative', flexShrink: 0 }}>
                        <img src={p.url} alt="" onClick={() => onViewLightbox(i)}
                            style={{ width: 72, height: 72, objectFit: 'cover', borderRadius: 8, cursor: 'pointer', border: '2px solid #ddd6fe', display: 'block' }} />
                    </div>
                ))}

                {/* Pending photos with × */}
                {pending.map((p, i) => (
                    <div key={i} style={{ position: 'relative', flexShrink: 0 }}>
                        <img src={p.preview} alt="" onClick={() => onViewLightbox(photos.length + i)}
                            style={{ width: 72, height: 72, objectFit: 'cover', borderRadius: 8, cursor: 'pointer', border: '2px dashed #f59e0b', display: 'block' }} />
                        <button onClick={() => onRemovePending(i)}
                            style={{ position: 'absolute', top: -6, right: -6, width: 20, height: 20, borderRadius: '50%', border: 'none', background: '#ef4444', color: '#fff', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 14, fontWeight: 700, lineHeight: 1, boxShadow: '0 1px 4px rgba(0,0,0,.25)', padding: 0, outline: 'none' }}>
                            ×
                        </button>
                        <div style={{ position: 'absolute', bottom: 3, left: 3, fontSize: 8, background: 'rgba(245,158,11,.9)', color: '#fff', borderRadius: 3, padding: '1px 4px', fontWeight: 700, letterSpacing: '.03em' }}>
                            PENDING
                        </div>
                    </div>
                ))}

                {/* + Add button */}
                {needSave ? (
                    <div style={{ width: 72, height: 72, borderRadius: 8, border: '2px dashed #fbbf24', background: '#fffbeb', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                        <span style={{ fontSize: 9, color: '#92400e', fontWeight: 600, textAlign: 'center', padding: '0 6px', lineHeight: 1.4 }}>Simpan baris dulu</span>
                    </div>
                ) : saving ? (
                    <div style={{ width: 72, height: 72, borderRadius: 8, border: '2px dashed #a78bfa', background: '#f5f3ff', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                        <div style={{ width: 18, height: 18, border: '2px solid #ddd6fe', borderTopColor: '#7c3aed', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
                    </div>
                ) : (
                    <label style={{ width: 72, height: 72, borderRadius: 8, border: '2px dashed #a78bfa', background: '#f5f3ff', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 3, cursor: 'pointer', flexShrink: 0 }}>
                        <Plus size={22} color="#7c3aed" />
                        <span style={{ fontSize: 9, color: '#7c3aed', fontWeight: 700 }}>Tambah</span>
                        <input type="file" accept="image/*" multiple style={{ display: 'none' }}
                            onChange={e => {
                                [...(e.target.files || [])].forEach(file => {
                                    const preview = URL.createObjectURL(file);
                                    onAddPending({ file, preview });
                                });
                                e.target.value = '';
                            }} />
                    </label>
                )}
            </div>
        </div>
    );
}

// ── Full-page Lightbox ─────────────────────────────────────────────────

function FullLightbox({ items, idx, rowName, onClose, onNavigate, onAddPending }) {
    if (!items || !items.length) return null;
    const safeIdx = Math.max(0, Math.min(idx, items.length - 1));
    const item    = items[safeIdx];

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 10000, background: 'rgba(0,0,0,.95)', display: 'flex', flexDirection: 'column' }}
            onClick={onClose}>

            {/* Top bar */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', flexShrink: 0, background: 'rgba(0,0,0,.4)', backdropFilter: 'blur(8px)' }}
                onClick={e => e.stopPropagation()}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <Camera size={15} color="#a78bfa" />
                    <div>
                        <div style={{ fontSize: 13, fontWeight: 700, color: '#fff', maxWidth: 280, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {rowName || 'Foto Defect'}
                        </div>
                        <div style={{ fontSize: 11, color: '#94a3b8', display: 'flex', alignItems: 'center', gap: 6 }}>
                            {safeIdx + 1} / {items.length}
                            {!item.saved && (
                                <span style={{ fontSize: 8, background: '#f59e0b', color: '#fff', borderRadius: 3, padding: '1px 5px', fontWeight: 700, letterSpacing: '.03em' }}>PENDING</span>
                            )}
                        </div>
                    </div>
                </div>
                <button onClick={onClose}
                    style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 16px', borderRadius: 8, border: 'none', background: 'rgba(255,255,255,.12)', color: '#fff', cursor: 'pointer', fontSize: 13, fontWeight: 500, outline: 'none' }}>
                    <X size={14} /> Tutup
                </button>
            </div>

            {/* Main image */}
            <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', position: 'relative', minHeight: 0, padding: '0 70px' }}
                onClick={e => e.stopPropagation()}>
                {safeIdx > 0 && (
                    <button onClick={() => onNavigate(safeIdx - 1)}
                        style={{ position: 'absolute', left: 14, width: 44, height: 44, borderRadius: '50%', border: 'none', background: 'rgba(255,255,255,.15)', color: '#fff', cursor: 'pointer', fontSize: 28, display: 'flex', alignItems: 'center', justifyContent: 'center', outline: 'none' }}>
                        ‹
                    </button>
                )}
                <img src={item.url} alt=""
                    style={{ maxWidth: '100%', maxHeight: '100%', objectFit: 'contain', borderRadius: 10, boxShadow: '0 8px 40px rgba(0,0,0,.5)', userSelect: 'none', display: 'block' }} />
                {safeIdx < items.length - 1 && (
                    <button onClick={() => onNavigate(safeIdx + 1)}
                        style={{ position: 'absolute', right: 14, width: 44, height: 44, borderRadius: '50%', border: 'none', background: 'rgba(255,255,255,.15)', color: '#fff', cursor: 'pointer', fontSize: 28, display: 'flex', alignItems: 'center', justifyContent: 'center', outline: 'none' }}>
                        ›
                    </button>
                )}
            </div>

            {/* Bottom bar: dots + Add Photo */}
            <div style={{ padding: '12px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}
                onClick={e => e.stopPropagation()}>
                <div style={{ display: 'flex', gap: 5, alignItems: 'center' }}>
                    {items.map((it, i) => (
                        <button key={i} onClick={() => onNavigate(i)}
                            style={{ width: i === safeIdx ? 24 : 8, height: 8, borderRadius: 999, border: 'none', padding: 0, background: i === safeIdx ? '#a78bfa' : (it.saved ? 'rgba(255,255,255,.3)' : '#f59e0b'), cursor: 'pointer', transition: 'all .2s', outline: 'none' }} />
                    ))}
                </div>
                {onAddPending && (
                    <label style={{ display: 'flex', alignItems: 'center', gap: 7, padding: '8px 18px', borderRadius: 10, border: '1.5px solid rgba(167,139,250,.4)', background: 'rgba(124,58,237,.2)', color: '#c4b5fd', fontSize: 13, fontWeight: 600, cursor: 'pointer', outline: 'none' }}>
                        <Plus size={15} /> Tambah Foto
                        <input type="file" accept="image/*" multiple style={{ display: 'none' }}
                            onChange={e => {
                                [...(e.target.files || [])].forEach(file => {
                                    const preview = URL.createObjectURL(file);
                                    onAddPending({ file, preview });
                                });
                                e.target.value = '';
                            }} />
                    </label>
                )}
            </div>

            {/* Thumbnail strip */}
            {items.length > 1 && (
                <div style={{ padding: '0 20px 16px', display: 'flex', gap: 6, overflowX: 'auto', flexShrink: 0, justifyContent: 'center', scrollbarWidth: 'none' }}
                    onClick={e => e.stopPropagation()}>
                    {items.map((it, i) => (
                        <img key={i} src={it.url} alt="" onClick={() => onNavigate(i)}
                            style={{ width: 52, height: 52, objectFit: 'cover', borderRadius: 7, cursor: 'pointer', flexShrink: 0, border: `2px solid ${i === safeIdx ? '#a78bfa' : (it.saved ? 'rgba(255,255,255,.2)' : '#f59e0b')}`, opacity: i === safeIdx ? 1 : 0.55, transition: 'all .15s', display: 'block' }} />
                    ))}
                </div>
            )}
        </div>
    );
}

// ── Main Component ────────────────────────────────────────────────────

export default function FinishingProductionTab({ projectUid }) {
    const { authUser } = useApp();
    const qc = useQueryClient();

    const { data: logs = [], isLoading } = useQuery({
        queryKey: ['stage-reject-logs', projectUid, 'finishing'],
        queryFn:  () => getStageRejectLogs(projectUid, 'finishing'),
        staleTime: 30_000,
    });

    const containerRef = useRef(null);
    const hotRef       = useRef(null);
    const dirtyRef     = useRef(new Set());
    const logsRef      = useRef([]);

    const [dirtyCount,  setDirtyCount]  = useState(0);
    const [saving,      setSaving]      = useState(false);
    const [saveErr,     setSaveErr]     = useState(null);
    const [saved,       setSaved]       = useState(false);
    const [importFile,  setImportFile]  = useState(null);
    const [importErr,   setImportErr]   = useState(null);
    const [importing,   setImporting]   = useState(false);
    const [selectedRow, setSelectedRow] = useState(null); // { uid, name }
    const [lightbox,    setLightbox]    = useState(null); // { rowUid, idx }
    // pending photos per row uid: { [uid]: [{file, preview}] }
    const [pendingPhotos, setPendingPhotos] = useState({});

    const stats = useMemo(() => ({
        total:    logs.length,
        open:     logs.filter(l => l.rework_status === 'OPEN').length,
        critical: logs.filter(l => l.severity === 'Critical').length,
        closed:   logs.filter(l => l.rework_status === 'CLOSED').length,
    }), [logs]);

    const lightboxInfo = useMemo(() => {
        if (!lightbox) return null;
        const log         = logs.find(l => l.uid === lightbox.rowUid);
        const savedPhotos = log?.photos ?? [];
        const pending     = pendingPhotos[lightbox.rowUid] ?? [];
        const items = [
            ...savedPhotos.map(p => ({ url: p.url, saved: true, uid: p.uid })),
            ...pending.map(p => ({ url: p.preview, saved: false })),
        ];
        if (!items.length) return null;
        return {
            items,
            idx:     Math.max(0, Math.min(lightbox.idx, items.length - 1)),
            rowName: log?.item_name ?? '',
            rowUid:  lightbox.rowUid,
        };
    }, [lightbox, logs, pendingPhotos]);

    useEffect(() => { logsRef.current = logs; }, [logs]);

    const toHotRows = (list) => list.map(l => ({
        uid:                    l.uid,
        item_name:              l.item_name              ?? '',
        defect_category:        l.defect_category        ?? '',
        fail_note:              l.fail_note              ?? '',
        severity:               l.severity               ?? 'Major',
        qty_reject:             l.qty_reject             ?? null,
        root_cause:             l.root_cause             ?? '',
        corrective_action:      l.corrective_action      ?? '',
        target_completion_date: l.target_completion_date ?? '',
        rework_status:          l.rework_status          ?? 'OPEN',
        _photos:                l.photos ?? [],          // source-data only
    }));

    // ── Init HOT ─────────────────────────────────────────────────────
    useEffect(() => {
        if (!containerRef.current) return;
        let hot;
        try {
            hot = new Handsontable(containerRef.current, {
                data:               [],
                columns:            HOT_COLUMNS,
                colHeaders:         HOT_COLUMNS.map(c => c.title),
                rowHeaders:         true,
                height:             'auto',
                width:              '100%',
                stretchH:           'all',
                contextMenu:        ['copy', 'cut', '---------', 'undo', 'redo'],
                manualColumnResize: true,
                columnSorting:      true,
                filters:            true,
                dropdownMenu:       true,
                minSpareRows:       1,
                rowHeights:         30,
                licenseKey:         'non-commercial-and-evaluation',
                cells(row) {
                    const h = this.instance;
                    if (!h) return {};
                    const sta = h.getDataAtRowProp(row, 'rework_status');
                    const sev = h.getDataAtRowProp(row, 'severity');
                    if (sta === 'CLOSED') return { className: 'htDimmed' };
                    if (sev === 'Critical') return { className: 'htCritical' };
                    return {};
                },
                afterChange(changes, source) {
                    if (source === 'loadData') return;
                    changes?.forEach(([row]) => dirtyRef.current.add(row));
                    setDirtyCount(dirtyRef.current.size);
                    setSaveErr(null);
                    setSaved(false);
                },
                afterSelectionEnd(r1) {
                    if (r1 < 0) return;
                    const uid = this.getDataAtRowProp(r1, 'uid');
                    if (!uid) { setSelectedRow(null); return; }
                    const log = logsRef.current.find(l => l.uid === uid);
                    setSelectedRow(log ? { uid, name: log.item_name ?? '' } : null);
                },
            });
        } catch (err) { console.error('HOT init error:', err); return; }
        hotRef.current = hot;
        return () => { hot?.destroy(); hotRef.current = null; };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Reload data ───────────────────────────────────────────────────
    useEffect(() => {
        const hot = hotRef.current;
        if (!hot || isLoading) return;
        try { hot.loadData(toHotRows(logs)); } catch (err) { console.error('HOT loadData error:', err); return; }
        dirtyRef.current.clear();
        setDirtyCount(0);
    }, [logs, isLoading]); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Add row ───────────────────────────────────────────────────────
    const handleAddRow = () => {
        const hot = hotRef.current;
        if (!hot) return;
        const insertAt = Math.max(0, hot.countRows() - 1);
        hot.alter('insert_row_below', insertAt);
        hot.selectCell(insertAt + 1, 0);
    };

    // ── Save ──────────────────────────────────────────────────────────
    const handleSaveAll = useCallback(async () => {
        const hot = hotRef.current;
        const hasDirty = dirtyRef.current.size > 0;
        const hasPending = Object.values(pendingPhotos).some(arr => arr.length > 0);
        if (!hot || (!hasDirty && !hasPending)) return;

        setSaving(true); setSaveErr(null); setSaved(false);
        try {
            // 1. save dirty rows
            if (hasDirty) {
                const indices = Array.from(dirtyRef.current).sort((a, b) => a - b);
                for (const rowIdx of indices) {
                    const row = hot.getSourceDataAtRow(rowIdx);
                    if (!row || (!row.item_name && !row.defect_category)) continue;
                    const payload = {
                        item_name:              row.item_name              ?? '',
                        defect_category:        row.defect_category        ?? 'Other',
                        fail_note:              row.fail_note              ?? '',
                        severity:               row.severity               ?? 'Major',
                        qty_reject:             row.qty_reject             ?? null,
                        root_cause:             row.root_cause             ?? '',
                        corrective_action:      row.corrective_action      ?? '',
                        rework_assigned_to:     authUser?.name             ?? '',
                        target_completion_date: row.target_completion_date ?? '',
                        rework_status:          row.rework_status          ?? 'OPEN',
                    };
                    if (row.uid) {
                        await updateStageRejectLog(projectUid, 'finishing', row.uid, payload);
                    } else {
                        await createStageRejectLog(projectUid, 'finishing', payload);
                    }
                }
                dirtyRef.current.clear();
                setDirtyCount(0);
            }

            // 2. upload pending photos (after rows have uids)
            if (hasPending) {
                // re-fetch to get fresh uids
                const freshLogs = await getStageRejectLogs(projectUid, 'finishing');
                for (const [rowUid, files] of Object.entries(pendingPhotos)) {
                    if (!files.length) continue;
                    // find uid in fresh data (might be new row — match by position)
                    const targetUid = freshLogs.find(l => l.uid === rowUid)?.uid || rowUid;
                    for (const { file } of files) {
                        await uploadPhoto(file, 'reject_log', targetUid, { context: 'reject' });
                    }
                }
                setPendingPhotos({});
            }

            setSaved(true);
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, 'finishing'] });
            qc.invalidateQueries({ queryKey: ['stage-gallery', projectUid, 'finishing'] });
            setTimeout(() => setSaved(false), 2500);
        } catch (e) {
            setSaveErr(e?.response?.data?.message ?? e.message ?? 'Save failed');
        }
        setSaving(false);
    }, [pendingPhotos, projectUid, authUser, qc]); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Import ────────────────────────────────────────────────────────
    const handleFileSelect = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        e.target.value = '';
        setImportErr(null);
        try {
            const rows = await parseFileData(file);
            setImportFile({ name: file.name, rows });
        } catch (err) {
            setImportErr('Failed to parse file: ' + (err.message ?? 'unknown'));
        }
    };

    const handleConfirmImport = async () => {
        if (!importFile || importFile.rows.length === 0) return;
        setImporting(true); setImportErr(null);
        try {
            const rows = importFile.rows.map(r => ({
                ...r,
                rework_assigned_to: r.rework_assigned_to || authUser?.name || '',
            }));
            await batchCreateRejectLogs(projectUid, 'finishing', rows);
            setImportFile(null);
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, 'finishing'] });
        } catch (e) {
            setImportErr(e?.response?.data?.message ?? e.message ?? 'Import failed');
        }
        setImporting(false);
    };

    // ── Render ────────────────────────────────────────────────────────
    const selectedPhotos = selectedRow
        ? (logs.find(l => l.uid === selectedRow?.uid)?.photos ?? [])
        : [];
    const pendingForRow  = selectedRow ? (pendingPhotos[selectedRow.uid] ?? []) : [];

    const hasDirtyOrPending = dirtyCount > 0 ||
        Object.values(pendingPhotos).some(a => a.length > 0);

    const addPendingForRow = useCallback((rowUid, item) => {
        setPendingPhotos(prev => ({
            ...prev,
            [rowUid]: [...(prev[rowUid] ?? []), item],
        }));
        setDirtyCount(c => c + 1);
    }, []);

    const addPending = useCallback((item) => {
        if (!selectedRow?.uid) return;
        addPendingForRow(selectedRow.uid, item);
    }, [selectedRow, addPendingForRow]);

    const removePending = useCallback((rowUid, pendingIdx) => {
        setPendingPhotos(prev => {
            const arr = [...(prev[rowUid] ?? [])];
            arr.splice(pendingIdx, 1);
            return { ...prev, [rowUid]: arr };
        });
    }, []);

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>

            {/* ── Stats strip ── */}
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                <StatCard label="Total Items"  value={stats.total}    bg="#faf5ff"  text="#7e22ce" icon={ClipboardList} />
                <StatCard label="Open"         value={stats.open}     bg="#fef9c3"  text="#713f12" />
                <StatCard label="Critical"     value={stats.critical} bg="#fee2e2"  text="#991b1b" icon={ShieldAlert} />
                <StatCard label="Closed"       value={stats.closed}   bg="#f1f5f9"  text="#475569" icon={CheckCircle2} />
            </div>

            {/* ── Toolbar ── */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8, background: '#faf5ff', borderRadius: 10, padding: '8px 12px', border: '1px solid #e9d5ff' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <span style={{ fontSize: 12, fontWeight: 700, color: '#7e22ce', display: 'flex', alignItems: 'center', gap: 5 }}>
                        <ClipboardList size={13} /> Finishing — Defect Log
                    </span>
                    {isLoading && (
                        <div style={{ width: 12, height: 12, border: '2px solid #e9d5ff', borderTopColor: '#a855f7', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
                    )}
                    {hasDirtyOrPending && !saving && (
                        <span style={{ fontSize: 10, background: '#fef3c7', color: '#92400e', borderRadius: 99, padding: '1px 7px', fontWeight: 600, border: '1px solid #fde68a' }}>
                            unsaved
                        </span>
                    )}
                    {saved && (
                        <span style={{ fontSize: 10, color: '#15803d', display: 'flex', alignItems: 'center', gap: 3 }}>
                            <CheckCircle2 size={11} /> Saved
                        </span>
                    )}
                </div>

                <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                    <label style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '5px 10px', borderRadius: 7, border: '1.5px solid #ddd6fe', background: '#fff', color: '#7c3aed', fontSize: 11, fontWeight: 500, cursor: 'pointer' }}>
                        <Upload size={11} /> Import
                        <input type="file" accept=".xlsx,.csv,.xls" style={{ display: 'none' }} onChange={handleFileSelect} />
                    </label>
                    <button onClick={handleAddRow}
                        style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '5px 10px', borderRadius: 7, border: '1.5px solid #ddd6fe', background: '#fff', color: '#7c3aed', fontSize: 11, fontWeight: 500, cursor: 'pointer' }}>
                        <Plus size={11} /> Add Row
                    </button>
                    <button onClick={handleSaveAll} disabled={!hasDirtyOrPending || saving}
                        style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '5px 12px', borderRadius: 7, border: 'none', background: hasDirtyOrPending ? '#7e22ce' : '#e2e8f0', color: hasDirtyOrPending ? '#fff' : '#94a3b8', fontSize: 11, fontWeight: 600, cursor: hasDirtyOrPending ? 'pointer' : 'default', transition: 'background .15s', opacity: saving ? 0.7 : 1 }}>
                        <Save size={11} /> {saving ? 'Saving…' : 'Save'}
                    </button>
                </div>
            </div>

            {/* Error banners */}
            {saveErr && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 11, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '6px 10px', border: '1px solid #fecaca' }}>
                    <AlertCircle size={12} /> {saveErr}
                </div>
            )}
            {importErr && !importFile && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 11, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '6px 10px', border: '1px solid #fecaca' }}>
                    <AlertCircle size={12} /> {importErr}
                </div>
            )}

            {/* Hint */}
            <div style={{ fontSize: 10, color: '#a78bfa', display: 'flex', alignItems: 'center', gap: 5, flexWrap: 'wrap' }}>
                <Info size={10} />
                Klik sel untuk edit · klik baris untuk pilih foto ·
                <kbd style={{ fontSize: 9, background: '#f5f3ff', borderRadius: 3, padding: '1px 4px', border: '1px solid #ddd6fe', color: '#7e22ce' }}>Tab</kbd> navigasi ·
                <kbd style={{ fontSize: 9, background: '#f5f3ff', borderRadius: 3, padding: '1px 4px', border: '1px solid #ddd6fe', color: '#7e22ce' }}>Ctrl+Z</kbd> undo · tekan
                <strong style={{ color: '#7e22ce' }}>Save</strong> untuk simpan
            </div>

            {/* HOT table — full width, no side panel */}
            <div style={{ border: '1.5px solid #e9d5ff', borderRadius: 10, overflow: 'hidden', background: '#fff', boxShadow: '0 2px 8px rgba(126,34,206,.06)' }}>
                <style>{`
                    .htCritical td { background: #fff0f0 !important; }
                    .htDimmed  td { background: #f8fafc !important; color: #94a3b8 !important; }
                    .ht-theme-main .htCore td { font-size: 11px !important; font-family: Arial, sans-serif; padding: 3px 6px !important; }
                    .ht-theme-main .htCore th { font-size: 10px !important; font-family: Arial, sans-serif; }
                    .ht-theme-main .htCore tr.htRowHeader th,
                    .ht-theme-main .htCore tbody th { width: 28px !important; min-width: 28px !important; }
                `}</style>
                <div ref={containerRef} className="ht-theme-main" />
            </div>

            {/* Photo panel */}
            <PhotoPanel
                selectedRow={selectedRow}
                photos={selectedPhotos}
                pendingPhotos={pendingForRow}
                onAddPending={addPending}
                onRemovePending={(pendingIdx) => removePending(selectedRow?.uid, pendingIdx)}
                onViewLightbox={(idx) => selectedRow?.uid && setLightbox({ rowUid: selectedRow.uid, idx })}
                saving={saving}
            />

            {/* Full-page lightbox */}
            {lightboxInfo && (
                <FullLightbox
                    items={lightboxInfo.items}
                    idx={lightboxInfo.idx}
                    rowName={lightboxInfo.rowName}
                    onClose={() => setLightbox(null)}
                    onNavigate={(idx) => setLightbox(l => ({ ...l, idx }))}
                    onAddPending={lightboxInfo.rowUid ? (item) => addPendingForRow(lightboxInfo.rowUid, item) : null}
                />
            )}

            {/* Import preview modal */}
            {importFile && (
                <ImportPreviewModal
                    rows={importFile.rows}
                    fileName={importFile.name}
                    onClose={() => { setImportFile(null); setImportErr(null); }}
                    onConfirm={handleConfirmImport}
                    loading={importing}
                    error={importErr}
                />
            )}
        </div>
    );
}
