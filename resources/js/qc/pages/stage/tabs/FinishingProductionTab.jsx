import React, { useEffect, useRef, useState } from 'react';
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
import { DEFECT_CATEGORIES } from '../../../data/models';
import { Save, Upload, Plus, AlertCircle, X, CheckCircle2, FileSpreadsheet, Info } from 'lucide-react';

registerAllModules();

// ── Column definitions ────────────────────────────────────────────────

const STATUS_SOURCE   = ['OPEN', 'IN_REPAIR', 'REPAIRED-PQC', 'CLOSED'];
const SEVERITY_SOURCE = ['Critical', 'Major', 'Minor'];

const HOT_COLUMNS = [
    { title: 'Reject ID',         data: 'reject_id',              readOnly: true,  width: 88  },
    { title: 'Component / Part',  data: 'item_name',              type: 'text',    width: 155 },
    { title: 'Defect Category',   data: 'defect_category',        type: 'dropdown', source: DEFECT_CATEGORIES, strict: false, width: 138 },
    { title: 'Description',       data: 'fail_note',              type: 'text',    width: 200 },
    { title: 'Severity',          data: 'severity',               type: 'dropdown', source: SEVERITY_SOURCE, strict: false, width: 90  },
    { title: 'Qty Reject',        data: 'qty_reject',             type: 'numeric', numericFormat: { pattern: '0' }, width: 80  },
    { title: '📷',                data: '_photo_count',           readOnly: true,  width: 42  },
    { title: 'Root Cause',        data: 'root_cause',             type: 'text',    width: 175 },
    { title: 'Corrective Action', data: 'corrective_action',      type: 'text',    width: 175 },
    { title: 'Assigned To',       data: 'rework_assigned_to',     type: 'text',    width: 125 },
    { title: 'Target Date',       data: 'target_completion_date', type: 'date',    dateFormat: 'YYYY-MM-DD', correctFormat: true, width: 110 },
    { title: 'Status',            data: 'rework_status',          type: 'dropdown', source: STATUS_SOURCE, strict: false, width: 120 },
];

// ── Import helpers ────────────────────────────────────────────────────

const IMPORT_MAP = {
    'reject id':               'reject_id',
    'reject_id':               'reject_id',
    'component':               'item_name',
    'component/part':          'item_name',
    'component / part':        'item_name',
    'part':                    'item_name',
    'item':                    'item_name',
    'item_name':               'item_name',
    'item name':               'item_name',
    'defect category':         'defect_category',
    'defect_category':         'defect_category',
    'category':                'defect_category',
    'defect type':             'defect_category',
    'description':             'fail_note',
    'fail_note':               'fail_note',
    'fail note':               'fail_note',
    'defect description':      'fail_note',
    'severity':                'severity',
    'qty reject':              'qty_reject',
    'qty_reject':              'qty_reject',
    'qty fail':                'qty_reject',
    'qty_fail':                'qty_reject',
    'quantity':                'qty_reject',
    'qty':                     'qty_reject',
    'root cause':              'root_cause',
    'root_cause':              'root_cause',
    'cause':                   'root_cause',
    'corrective action':       'corrective_action',
    'corrective_action':       'corrective_action',
    'action':                  'corrective_action',
    'fix':                     'corrective_action',
    'assigned to':             'rework_assigned_to',
    'rework_assigned_to':      'rework_assigned_to',
    'assigned_to':             'rework_assigned_to',
    'assignee':                'rework_assigned_to',
    'target date':             'target_completion_date',
    'target_date':             'target_completion_date',
    'target_completion_date':  'target_completion_date',
    'due date':                'target_completion_date',
    'status':                  'rework_status',
    'rework_status':           'rework_status',
    'rework status':           'rework_status',
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
        rework_assigned_to:     mapped.rework_assigned_to     ?? '',
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
    { key: 'rework_assigned_to',     label: 'Assigned To' },
    { key: 'target_completion_date', label: 'Target Date' },
    { key: 'rework_status',          label: 'Status' },
];

function ImportPreviewModal({ rows, fileName, onClose, onConfirm, loading, error }) {
    const preview = rows.slice(0, 20);
    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 9999, background: 'rgba(0,0,0,.55)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }}>
            <div style={{ background: '#fff', borderRadius: 16, boxShadow: '0 20px 60px rgba(0,0,0,.2)', width: '100%', maxWidth: 920, maxHeight: '88vh', display: 'flex', flexDirection: 'column' }}>

                {/* Header */}
                <div style={{ padding: '14px 20px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <FileSpreadsheet size={18} color="#6366f1" />
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

                <div style={{ margin: '10px 20px 0', padding: '8px 12px', background: '#f0f9ff', border: '1px solid #bae6fd', borderRadius: 8, fontSize: 11, color: '#0369a1', display: 'flex', gap: 6, flexShrink: 0 }}>
                    <Info size={12} style={{ flexShrink: 0, marginTop: 1 }} />
                    Photo references in the file are skipped — attach photos separately via the Gallery tab after import.
                </div>

                {/* Table */}
                <div style={{ flex: 1, overflowY: 'auto', padding: '12px 20px 0' }}>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                            <thead>
                                <tr style={{ background: '#f8fafc', position: 'sticky', top: 0 }}>
                                    <th style={{ padding: '7px 10px', textAlign: 'center', fontWeight: 700, color: '#94a3b8', fontSize: 10, width: 32 }}>#</th>
                                    {PREVIEW_COLS.map(c => (
                                        <th key={c.key} style={{ padding: '7px 10px', textAlign: 'left', fontWeight: 700, color: '#64748b', fontSize: 10, textTransform: 'uppercase', letterSpacing: '.04em', whiteSpace: 'nowrap' }}>
                                            {c.label}
                                        </th>
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
                                    <tr><td colSpan={PREVIEW_COLS.length + 1} style={{ padding: 32, textAlign: 'center', color: '#94a3b8' }}>
                                        No valid rows found. Make sure columns match the expected format.
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Footer */}
                <div style={{ padding: '12px 20px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: 8, flexShrink: 0 }}>
                    <button onClick={onClose}
                        style={{ padding: '8px 16px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#f1f5f9', color: '#475569', fontSize: 13, cursor: 'pointer', outline: 'none' }}>
                        Cancel
                    </button>
                    <button onClick={onConfirm} disabled={loading || rows.length === 0}
                        style={{ padding: '8px 16px', borderRadius: 8, border: 'none', background: '#6366f1', color: '#fff', fontSize: 13, fontWeight: 600, cursor: 'pointer', outline: 'none', display: 'flex', alignItems: 'center', gap: 6, opacity: (loading || rows.length === 0) ? 0.5 : 1 }}>
                        {loading ? 'Importing…' : `Import ${rows.length} Row${rows.length !== 1 ? 's' : ''}`}
                    </button>
                </div>
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

    // HOT refs
    const containerRef = useRef(null);
    const hotRef       = useRef(null);
    const dirtyRef     = useRef(new Set());

    // Toolbar UI state
    const [dirtyCount, setDirtyCount] = useState(0);
    const [saving,     setSaving]     = useState(false);
    const [saveErr,    setSaveErr]    = useState(null);
    const [saved,      setSaved]      = useState(false);

    // Import state
    const [importFile, setImportFile] = useState(null);   // { name, rows }
    const [importErr,  setImportErr]  = useState(null);
    const [importing,  setImporting]  = useState(false);

    // Convert API logs → HOT row objects
    const toHotRows = (list) => list.map(l => ({
        uid:                    l.uid,
        reject_id:              l.reject_id ?? '—',
        item_name:              l.item_name              ?? '',
        defect_category:        l.defect_category        ?? '',
        fail_note:              l.fail_note              ?? '',
        severity:               l.severity               ?? 'Major',
        qty_reject:             l.qty_reject             ?? null,
        _photo_count:           l.photos?.length ? `📷 ${l.photos.length}` : '',
        root_cause:             l.root_cause             ?? '',
        corrective_action:      l.corrective_action      ?? '',
        rework_assigned_to:     l.rework_assigned_to     ?? '',
        target_completion_date: l.target_completion_date ?? '',
        rework_status:          l.rework_status          ?? 'OPEN',
    }));

    // ── Initialize HOT (once on mount) ────────────────────────────────
    useEffect(() => {
        if (!containerRef.current) return;
        const hot = new Handsontable(containerRef.current, {
            data:       [],
            columns:    HOT_COLUMNS,
            colHeaders: HOT_COLUMNS.map(c => c.title),
            rowHeaders: true,
            height:     520,
            width:      '100%',
            stretchH:   'last',
            contextMenu: ['copy', 'cut', '---------', 'undo', 'redo'],
            manualColumnResize: true,
            filters:      true,
            dropdownMenu: true,
            minSpareRows: 1,
            licenseKey:  'non-commercial-and-evaluation',
            afterChange(changes, source) {
                if (source === 'loadData') return;
                changes?.forEach(([row]) => dirtyRef.current.add(row));
                setDirtyCount(dirtyRef.current.size);
                setSaveErr(null);
                setSaved(false);
            },
        });
        hotRef.current = hot;
        return () => { hot.destroy(); hotRef.current = null; };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Reload data when logs arrive ──────────────────────────────────
    useEffect(() => {
        const hot = hotRef.current;
        if (!hot || isLoading) return;
        hot.loadData(toHotRows(logs));
        dirtyRef.current.clear();
        setDirtyCount(0);
    }, [logs, isLoading]); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Add blank row ─────────────────────────────────────────────────
    const handleAddRow = () => {
        const hot = hotRef.current;
        if (!hot) return;
        const insertAt = Math.max(0, hot.countRows() - 1);
        hot.alter('insert_row_below', insertAt);
        const newRow = insertAt + 1;
        if (authUser?.name) {
            hot.setDataAtRowProp(newRow, 'rework_assigned_to', authUser.name);
        }
        hot.selectCell(newRow, 1);
    };

    // ── Save all dirty rows ───────────────────────────────────────────
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
                    rework_assigned_to:     row.rework_assigned_to     ?? '',
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

    // ── Handle file pick for import ───────────────────────────────────
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

    // ── Confirm batch import ──────────────────────────────────────────
    const handleConfirmImport = async () => {
        if (!importFile || importFile.rows.length === 0) return;
        setImporting(true); setImportErr(null);
        try {
            const rows = authUser?.name
                ? importFile.rows.map(r => ({
                    ...r,
                    rework_assigned_to: r.rework_assigned_to || authUser.name,
                  }))
                : importFile.rows;
            await batchCreateRejectLogs(projectUid, 'finishing', rows);
            setImportFile(null);
            qc.invalidateQueries({ queryKey: ['stage-reject-logs', projectUid, 'finishing'] });
        } catch (e) {
            setImportErr(e?.response?.data?.message ?? e.message ?? 'Import failed');
        }
        setImporting(false);
    };

    // ── Render ────────────────────────────────────────────────────────
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>

            {/* Toolbar */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8 }}>
                {/* Left: title + status indicators */}
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <span style={{ fontSize: 13, fontWeight: 700, color: '#7e22ce' }}>
                        Finishing — Defect Log
                    </span>
                    {isLoading && (
                        <div style={{ width: 13, height: 13, border: '2px solid #e9d5ff', borderTopColor: '#a855f7', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
                    )}
                    {dirtyCount > 0 && !saving && (
                        <span style={{ fontSize: 11, background: '#fef3c7', color: '#92400e', borderRadius: 99, padding: '2px 9px', fontWeight: 600 }}>
                            {dirtyCount} unsaved change{dirtyCount !== 1 ? 's' : ''}
                        </span>
                    )}
                    {saved && (
                        <span style={{ fontSize: 11, color: '#15803d', display: 'flex', alignItems: 'center', gap: 4 }}>
                            <CheckCircle2 size={12} /> Saved
                        </span>
                    )}
                </div>

                {/* Right: action buttons */}
                <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    <label style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 12px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#475569', fontSize: 12, fontWeight: 500, cursor: 'pointer', outline: 'none', userSelect: 'none' }}>
                        <Upload size={13} /> Import xlsx / csv
                        <input type="file" accept=".xlsx,.csv,.xls" style={{ display: 'none' }} onChange={handleFileSelect} />
                    </label>

                    <button onClick={handleAddRow}
                        style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 12px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#475569', fontSize: 12, fontWeight: 500, cursor: 'pointer', outline: 'none' }}>
                        <Plus size={13} /> Add Row
                    </button>

                    <button onClick={handleSaveAll} disabled={dirtyCount === 0 || saving}
                        style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 14px', borderRadius: 8, border: 'none', background: dirtyCount > 0 ? '#6366f1' : '#e2e8f0', color: dirtyCount > 0 ? '#fff' : '#94a3b8', fontSize: 12, fontWeight: 600, cursor: dirtyCount > 0 ? 'pointer' : 'default', outline: 'none', transition: 'background .15s', opacity: saving ? 0.7 : 1 }}>
                        <Save size={13} /> {saving ? 'Saving…' : 'Save Changes'}
                    </button>
                </div>
            </div>

            {/* Error banners */}
            {saveErr && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 12px' }}>
                    <AlertCircle size={13} /> {saveErr}
                </div>
            )}
            {importErr && !importFile && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, color: '#dc2626', background: '#fef2f2', borderRadius: 8, padding: '8px 12px' }}>
                    <AlertCircle size={13} /> {importErr}
                </div>
            )}

            {/* Keyboard hint */}
            <div style={{ fontSize: 11, color: '#94a3b8' }}>
                Click any cell to edit inline. Use <kbd style={{ fontSize: 10, background: '#f1f5f9', borderRadius: 4, padding: '1px 5px', border: '1px solid #e2e8f0' }}>Tab</kbd> / arrows to navigate · <kbd style={{ fontSize: 10, background: '#f1f5f9', borderRadius: 4, padding: '1px 5px', border: '1px solid #e2e8f0' }}>Ctrl+Z</kbd> to undo · Press <strong>Save Changes</strong> to persist edits.
            </div>

            {/* Handsontable container */}
            <div style={{ border: '1px solid #e2e8f0', borderRadius: 10, overflow: 'hidden', background: '#fff' }}>
                <div ref={containerRef} className="ht-theme-main" />
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
