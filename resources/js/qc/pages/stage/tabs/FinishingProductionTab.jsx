import React, { useEffect, useRef, useState, useMemo } from 'react';
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
    FileSpreadsheet, Info, ShieldAlert, ClipboardList,
    Camera,
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

const SEV_COLORS = {
    Critical: { bg: '#fee2e2', text: '#991b1b' },
    Major:    { bg: '#fef3c7', text: '#92400e' },
    Minor:    { bg: '#e0f2fe', text: '#0369a1' },
};
const STA_COLORS = {
    OPEN:          { bg: '#fef9c3', text: '#713f12' },
    IN_REPAIR:     { bg: '#fff7ed', text: '#9a3412' },
    'REPAIRED-PQC':{ bg: '#f0fdf4', text: '#14532d' },
    CLOSED:        { bg: '#f1f5f9', text: '#475569' },
};

const HOT_COLUMNS = [
    { title: 'Component / Part',  data: 'item_name',              type: 'dropdown', source: COMPONENT_PARTS,   strict: false, width: 145 },
    { title: 'Defect Category',   data: 'defect_category',        type: 'dropdown', source: DEFECT_CATEGORIES, strict: false, width: 145 },
    { title: 'Description',       data: 'fail_note',              type: 'text',    width: 185 },
    { title: 'Qty',               data: 'qty_reject',             type: 'numeric', numericFormat: { pattern: '0' }, width: 55  },
    { title: 'Severity',          data: 'severity',               type: 'dropdown', source: SEVERITY_SOURCE,   strict: false, width: 100 },
    { title: 'Root Cause',        data: 'root_cause',             type: 'text',    width: 250 },
    { title: 'Corrective Action', data: 'corrective_action',      type: 'text',    width: 250 },
    { title: 'Target Date',       data: 'target_completion_date', type: 'date',    dateFormat: 'YYYY-MM-DD', correctFormat: true, width: 130 },
    { title: 'Status',            data: 'rework_status',          type: 'dropdown', source: STATUS_SOURCE,    strict: false, width: 70  },
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
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.2)', width: '100%', maxWidth: 920, maxHeight: '88vh', display: 'flex', flexDirection: 'column' }}>
                <div style={{ padding: '14px 20px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <FileSpreadsheet size={18} color="#7e22ce" />
                        <div>
                            <div style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>Import Preview</div>
                            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 1 }}>
                                {fileName} — {rows.length} row{rows.length !== 1 ? 's' : ''} detected
                                {rows.length > 20 && ' (showing first 20)'}
                            </div>
                        </div>
                    </div>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 4, outline: 'none', display: 'flex' }}>
                        <X size={18} />
                    </button>
                </div>
                {error && (
                    <div style={{ margin: '12px 20px 0', padding: '10px 14px', background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 10, fontSize: 12, color: '#dc2626', display: 'flex', gap: 8 }}>
                        <AlertCircle size={14} style={{ flexShrink: 0, marginTop: 1 }} /> {error}
                    </div>
                )}
                <div style={{ margin: '10px 20px 0', padding: '8px 12px', background: '#faf5ff', border: '1px solid #e9d5ff', borderRadius: 8, fontSize: 11, color: '#7e22ce', display: 'flex', gap: 6, flexShrink: 0 }}>
                    <Info size={12} style={{ flexShrink: 0, marginTop: 1 }} />
                    Photo references in the file are skipped — attach photos separately via Gallery tab.
                </div>
                <div style={{ flex: 1, overflowY: 'auto', padding: '12px 20px 0' }}>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#faf5ff', position: 'sticky', top: 0 }}>
                                    <th style={{ padding: '7px 10px', textAlign: 'center', fontWeight: 700, color: '#94a3b8', fontSize: 10, width: 32 }}>#</th>
                                    {PREVIEW_COLS.map(c => (
                                        <th key={c.key} style={{ padding: '7px 10px', textAlign: 'left', fontWeight: 700, color: '#7e22ce', fontSize: 10, textTransform: 'uppercase', letterSpacing: '.04em', whiteSpace: 'nowrap' }}>{c.label}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {preview.map((row, i) => (
                                    <tr key={i} style={{ borderTop: '1px solid #f1f5f9', background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                                        <td style={{ padding: '6px 10px', textAlign: 'center', color: '#cbd5e1', fontSize: 11 }}>{i + 1}</td>
                                        {PREVIEW_COLS.map(c => (
                                            <td key={c.key} style={{ padding: '6px 10px', color: '#334155', maxWidth: 140, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                                {row[c.key] ?? '—'}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                                {preview.length === 0 && (
                                    <tr><td colSpan={PREVIEW_COLS.length + 1} style={{ padding: 32, textAlign: 'center', color: '#94a3b8' }}>No valid rows found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div style={{ padding: '12px 20px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8, flexShrink: 0 }}>
                    <button onClick={onClose} style={{ padding: '8px 16px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#f1f5f9', color: '#475569', fontSize: 13, cursor: 'pointer', outline: 'none' }}>
                        Cancel
                    </button>
                    <button onClick={onConfirm} disabled={loading || rows.length === 0}
                        style={{ padding: '8px 16px', borderRadius: 8, border: 'none', background: '#7e22ce', color: '#fff', fontSize: 13, fontWeight: 600, cursor: 'pointer', outline: 'none', display: 'flex', alignItems: 'center', gap: 6, opacity: (loading || rows.length === 0) ? 0.5 : 1 }}>
                        {loading ? 'Importing…' : `Import ${rows.length} Row${rows.length !== 1 ? 's' : ''}`}
                    </button>
                </div>
            </div>
        </div>
    );
}

// ── Photo Panel ───────────────────────────────────────────────────────

function PhotoPanel({ selectedRow, photos, onPhotoChange }) {
    const [uploading, setUploading] = useState(false);
    const [uploadErr, setUploadErr] = useState(null);

    const handleUpload = async (files) => {
        if (!selectedRow || !files?.length) return;
        setUploading(true); setUploadErr(null);
        try {
            for (const file of Array.from(files)) {
                await uploadPhoto(file, 'reject_log', selectedRow.uid, { context: 'reject' });
            }
            onPhotoChange();
        } catch {
            setUploadErr('Upload failed. Try again.');
        }
        setUploading(false);
    };

    return (
        <div style={{ border: '1.5px solid #e9d5ff', borderRadius: 12, background: '#faf5ff', padding: '12px 14px', display: 'flex', flexDirection: 'column', gap: 10, height: '100%', boxSizing: 'border-box' }}>
            {/* Header */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                <Camera size={13} color="#7e22ce" />
                <span style={{ fontSize: 12, fontWeight: 700, color: '#7e22ce', flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {selectedRow ? (selectedRow.name || 'Defect Log') : 'Photos'}
                </span>
                {selectedRow && photos.length > 0 && (
                    <span style={{ fontSize: 10, color: '#a78bfa', flexShrink: 0 }}>{photos.length}</span>
                )}
            </div>

            {/* Upload buttons — only when row selected */}
            {selectedRow && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                    <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 5, padding: '6px 0', borderRadius: 8, border: '1.5px dashed #ddd6fe', background: '#fff', color: '#7c3aed', fontSize: 12, fontWeight: 500, cursor: uploading ? 'default' : 'pointer', opacity: uploading ? 0.6 : 1 }}>
                        <Upload size={12} /> {uploading ? 'Uploading…' : 'Upload'}
                        <input type="file" accept="image/*" multiple style={{ display: 'none' }} disabled={uploading}
                            onChange={e => { if (e.target.files.length) handleUpload(e.target.files); e.target.value = ''; }} />
                    </label>
                    <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 5, padding: '6px 0', borderRadius: 8, border: '1.5px solid #ddd6fe', background: '#fff', color: '#7c3aed', fontSize: 12, fontWeight: 500, cursor: uploading ? 'default' : 'pointer', opacity: uploading ? 0.6 : 1 }}>
                        <Camera size={12} /> Camera
                        <input type="file" accept="image/*" capture="environment" style={{ display: 'none' }} disabled={uploading}
                            onChange={e => { if (e.target.files.length) handleUpload(e.target.files); e.target.value = ''; }} />
                    </label>
                </div>
            )}

            {/* Body */}
            {!selectedRow ? (
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', flex: 1, gap: 6, color: '#c4b5fd', textAlign: 'center' }}>
                    <Camera size={22} color="#ddd6fe" />
                    <span style={{ fontSize: 11 }}>Klik baris untuk upload foto</span>
                </div>
            ) : photos.length === 0 ? (
                <div style={{ fontSize: 11, color: '#c4b5fd', textAlign: 'center', paddingTop: 4 }}>Belum ada foto.</div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6, overflowY: 'auto' }}>
                    {photos.map(p => (
                        <div key={p.uid} style={{ aspectRatio: '1', borderRadius: 8, overflow: 'hidden', background: '#f1f5f9' }}>
                            <img src={p.url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }} />
                        </div>
                    ))}
                </div>
            )}

            {uploadErr && <div style={{ fontSize: 10, color: '#dc2626' }}>{uploadErr}</div>}
        </div>
    );
}

// ── Stat Card ─────────────────────────────────────────────────────────

function StatCard({ label, value, bg, text, icon: Icon }) {
    return (
        <div style={{ flex: 1, minWidth: 90, background: bg, borderRadius: 12, padding: '12px 16px', display: 'flex', alignItems: 'center', gap: 10 }}>
            {Icon && <Icon size={18} color={text} style={{ flexShrink: 0, opacity: .8 }} />}
            <div>
                <div style={{ fontSize: 11, color: text, opacity: .75, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.04em' }}>{label}</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: text, lineHeight: 1.2, marginTop: 2 }}>{value}</div>
            </div>
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

    const [dirtyCount, setDirtyCount] = useState(0);
    const [saving,     setSaving]     = useState(false);
    const [saveErr,    setSaveErr]    = useState(null);
    const [saved,      setSaved]      = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importErr,  setImportErr]  = useState(null);
    const [importing,  setImporting]  = useState(false);
    const [selectedRow, setSelectedRow] = useState(null); // { uid, name }

    // Stats
    const stats = useMemo(() => ({
        total:    logs.length,
        open:     logs.filter(l => l.rework_status === 'OPEN').length,
        critical: logs.filter(l => l.severity === 'Critical').length,
        closed:   logs.filter(l => l.rework_status === 'CLOSED').length,
    }), [logs]);

    useEffect(() => { logsRef.current = logs; }, [logs]);

    const toHotRows = (list) => list.map(l => ({
        uid:                    l.uid,
        item_name:              l.item_name              ?? '',
        defect_category:        l.defect_category        ?? '',
        fail_note:              l.fail_note              ?? '',
        severity:               l.severity               ?? 'Major',
        qty_reject:             l.qty_reject             ?? null,
        _photo_count:           l.photos?.length ? `📷 ${l.photos.length}` : '',
        root_cause:             l.root_cause             ?? '',
        corrective_action:      l.corrective_action      ?? '',
        target_completion_date: l.target_completion_date ?? '',
        rework_status:          l.rework_status          ?? 'OPEN',
    }));

    // ── Init HOT ─────────────────────────────────────────────────────
    useEffect(() => {
        if (!containerRef.current) return;
        const hot = new Handsontable(containerRef.current, {
            data:               [],
            columns:            HOT_COLUMNS,
            colHeaders:         HOT_COLUMNS.map(c => c.title),
            rowHeaders:         true,
            height:             500,
            width:              '100%',
            stretchH:           'last',
            contextMenu:        ['copy', 'cut', '---------', 'undo', 'redo'],
            manualColumnResize: false,
            columnSorting:      true,
            filters:            true,
            dropdownMenu:       true,
            minSpareRows:       1,
            licenseKey:         'non-commercial-and-evaluation',
            cells(row, col) {
                const hot = this.instance;
                if (!hot) return {};
                const sev = hot.getDataAtRowProp(row, 'severity');
                const sta = hot.getDataAtRowProp(row, 'rework_status');
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
        hotRef.current = hot;
        return () => { hot.destroy(); hotRef.current = null; };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Reload data ───────────────────────────────────────────────────
    useEffect(() => {
        const hot = hotRef.current;
        if (!hot || isLoading) return;
        hot.loadData(toHotRows(logs));
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
    const handleSaveAll = async () => {
        const hot = hotRef.current;
        if (!hot || dirtyRef.current.size === 0) return;
        setSaving(true); setSaveErr(null); setSaved(false);
        try {
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
            setSaved(true);
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, 'finishing'] });
            setTimeout(() => setSaved(false), 2500);
        } catch (e) {
            setSaveErr(e?.response?.data?.message ?? e.message ?? 'Save failed');
        }
        setSaving(false);
    };

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
            setImportErr('Failed to parse file: ' + (err.message ?? 'unknown error'));
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
        ? (logs.find(l => l.uid === selectedRow.uid)?.photos ?? [])
        : [];

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>

            {/* ── Stats strip ── */}
            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                <StatCard label="Total Items"  value={stats.total}    bg="#faf5ff"  text="#7e22ce" icon={ClipboardList} />
                <StatCard label="Open"         value={stats.open}     bg="#fef9c3"  text="#713f12" />
                <StatCard label="Critical"     value={stats.critical} bg="#fee2e2"  text="#991b1b" icon={ShieldAlert} />
                <StatCard label="Closed"       value={stats.closed}   bg="#f1f5f9"  text="#475569" icon={CheckCircle2} />
            </div>

            {/* ── Toolbar ── */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8, background: '#faf5ff', borderRadius: 12, padding: '10px 14px', border: '1px solid #e9d5ff' }}>
                {/* Left */}
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <span style={{ fontSize: 13, fontWeight: 700, color: '#7e22ce', display: 'flex', alignItems: 'center', gap: 6 }}>
                        <ClipboardList size={15} />
                        Finishing — Defect Log
                    </span>
                    {isLoading && (
                        <div style={{ width: 13, height: 13, border: '2px solid #e9d5ff', borderTopColor: '#a855f7', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
                    )}
                    {dirtyCount > 0 && !saving && (
                        <span style={{ fontSize: 11, background: '#fef3c7', color: '#92400e', borderRadius: 99, padding: '2px 9px', fontWeight: 600, border: '1px solid #fde68a' }}>
                            {dirtyCount} unsaved
                        </span>
                    )}
                    {saved && (
                        <span style={{ fontSize: 11, color: '#15803d', display: 'flex', alignItems: 'center', gap: 4 }}>
                            <CheckCircle2 size={12} /> Saved
                        </span>
                    )}
                </div>

                {/* Right: action buttons */}
                <div style={{ display: 'flex', gap: 7, alignItems: 'center' }}>
                    <label style={{ display: 'flex', alignItems: 'center', gap: 5, padding: '6px 12px', borderRadius: 8, border: '1.5px solid #ddd6fe', background: '#fff', color: '#7c3aed', fontSize: 12, fontWeight: 500, cursor: 'pointer', outline: 'none', userSelect: 'none' }}>
                        <Upload size={13} /> Import
                        <input type="file" accept=".xlsx,.csv,.xls" style={{ display: 'none' }} onChange={handleFileSelect} />
                    </label>

                    <button onClick={handleAddRow}
                        style={{ display: 'flex', alignItems: 'center', gap: 5, padding: '6px 12px', borderRadius: 8, border: '1.5px solid #ddd6fe', background: '#fff', color: '#7c3aed', fontSize: 12, fontWeight: 500, cursor: 'pointer', outline: 'none' }}>
                        <Plus size={13} /> Add Row
                    </button>

                    <button onClick={handleSaveAll} disabled={dirtyCount === 0 || saving}
                        style={{ display: 'flex', alignItems: 'center', gap: 5, padding: '6px 14px', borderRadius: 8, border: 'none', background: dirtyCount > 0 ? '#7e22ce' : '#e2e8f0', color: dirtyCount > 0 ? '#fff' : '#94a3b8', fontSize: 12, fontWeight: 600, cursor: dirtyCount > 0 ? 'pointer' : 'default', outline: 'none', transition: 'background .15s', opacity: saving ? 0.7 : 1 }}>
                        <Save size={13} /> {saving ? 'Saving…' : 'Save'}
                    </button>
                </div>
            </div>

            {/* Error banners */}
            {saveErr && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 12px', border: '1px solid #fecaca' }}>
                    <AlertCircle size={13} /> {saveErr}
                </div>
            )}
            {importErr && !importFile && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 12px', border: '1px solid #fecaca' }}>
                    <AlertCircle size={13} /> {importErr}
                </div>
            )}

            {/* Hint */}
            <div style={{ fontSize: 11, color: '#a78bfa', display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap' }}>
                <Info size={11} />
                Klik sel untuk edit · klik baris untuk pilih foto ·
                <kbd style={{ fontSize: 10, background: '#f5f3ff', borderRadius: 4, padding: '1px 5px', border: '1px solid #ddd6fe', color: '#7e22ce' }}>Tab</kbd> navigasi ·
                <kbd style={{ fontSize: 10, background: '#f5f3ff', borderRadius: 4, padding: '1px 5px', border: '1px solid #ddd6fe', color: '#7e22ce' }}>Ctrl+Z</kbd> undo ·
                tekan <strong style={{ color: '#7e22ce' }}>Save</strong> untuk simpan
            </div>

            {/* HOT + Photo panel — side by side */}
            <div style={{ display: 'flex', gap: 12, alignItems: 'stretch' }}>
                {/* HOT container */}
                <div style={{ flex: 1, minWidth: 0, border: '1.5px solid #e9d5ff', borderRadius: 12, overflow: 'hidden', background: '#fff', boxShadow: '0 2px 10px rgba(126,34,206,.07)' }}>
                    <style>{`
                        .htCritical td { background: #fff0f0 !important; }
                        .htDimmed  td { background: #f8fafc !important; color: #94a3b8 !important; }
                        .ht-theme-main .htCore thead th .relative { overflow: visible !important; }
                        .ht-theme-main .htCore thead th { white-space: nowrap; }
                    `}</style>
                    <div ref={containerRef} className="ht-theme-main" />
                </div>

                {/* Photo panel sidebar */}
                <div style={{ width: 210, flexShrink: 0 }}>
                    <PhotoPanel
                        selectedRow={selectedRow}
                        photos={selectedPhotos}
                        onPhotoChange={() => {
                            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, 'finishing'] });
                            qc.invalidateQueries({ queryKey: ['stage-gallery', projectUid, 'finishing'] });
                        }}
                    />
                </div>
            </div>

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
