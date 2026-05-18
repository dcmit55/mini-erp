import React from 'react';
import { useMQ } from '../MascotQC';
import { fmtDate } from '../constants/mascotConstants';
import DailyProgressTab      from './DailyProgressTab';
import FinishingChecklistTab from './FinishingChecklistTab';

const TABS = [
    { id: 'daily',     label: '📋 Daily Progress' },
    { id: 'finishing', label: '✅ Finishing Checklist' },
];

export default function MascotDetail({ project: p }) {
    const { detailTab, setDetailTab, setView, updateProject } = useMQ();

    const coverStyle = p.coverImage
        ? { backgroundImage: `url(${p.coverImage})`, backgroundSize: 'cover', backgroundPosition: 'center' }
        : { background: p.coverGradient || 'linear-gradient(135deg,#667eea,#764ba2)' };

    const setStatus = (s) => updateProject(p.id, proj => { proj.status = s; });

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>

            {/* ── Cover banner ── */}
            <div style={{ ...coverStyle, height: 110, position: 'relative', borderRadius: '12px 12px 0 0', overflow: 'hidden', flexShrink: 0 }}>
                <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,.45)' }} />
                <div style={{ position: 'relative', zIndex: 1, padding: '12px 16px', display: 'flex', alignItems: 'flex-start', gap: 10, height: '100%' }}>
                    <button className="mq-btn-ghost" style={{ fontSize: 13, padding: '5px 10px', background: 'rgba(255,255,255,.15)', color: '#fff', border: '1px solid rgba(255,255,255,.3)' }}
                        onClick={() => setView('jobs')}>← Back</button>
                    <div style={{ flex: 1 }}>
                        <div style={{ fontSize: 17, fontWeight: 700, color: '#fff', textShadow: '0 1px 3px rgba(0,0,0,.4)' }}>{p.projectName}</div>
                        <div style={{ fontSize: 11, color: 'rgba(255,255,255,.75)', marginTop: 2 }}>
                            {p.jobNumber && <span>{p.jobNumber} · </span>}
                            {p.mascotType} · {p.totalUnit ?? 1} unit · Due: {fmtDate(p.deadline)}
                        </div>
                        {p.supervisor && (
                            <div style={{ fontSize: 11, color: 'rgba(255,255,255,.65)', marginTop: 1 }}>Supervisor: {p.supervisor}</div>
                        )}
                    </div>
                    <div style={{ textAlign: 'right', color: '#fff' }}>
                        <div style={{ fontSize: 22, fontWeight: 800 }}>{p.progress || 0}%</div>
                        <StatusSelect status={p.status} onChange={setStatus} />
                    </div>
                </div>
            </div>

            {/* ── Progress bar ── */}
            <div style={{ height: 5, background: '#e2e8f0', flexShrink: 0 }}>
                <div style={{ height: '100%', width: `${p.progress || 0}%`, background: '#6366f1', transition: 'width .3s', borderRadius: '0 3px 3px 0' }} />
            </div>

            {/* ── Tabs ── */}
            <div style={{ display: 'flex', borderBottom: '2px solid #f1f5f9', background: '#fff', flexShrink: 0 }}>
                {TABS.map(t => (
                    <button key={t.id}
                        onClick={() => setDetailTab(t.id)}
                        style={{
                            padding: '11px 20px', fontSize: 13, fontWeight: 600, border: 'none', cursor: 'pointer', outline: 'none',
                            background: 'none', whiteSpace: 'nowrap',
                            color: detailTab === t.id ? '#6366f1' : '#64748b',
                            borderBottom: detailTab === t.id ? '2px solid #6366f1' : '2px solid transparent',
                            marginBottom: -2,
                        }}>
                        {t.label}
                    </button>
                ))}
            </div>

            {/* ── Tab content ── */}
            <div style={{ flex: 1, overflowY: 'auto', padding: '16px 20px', background: '#f8fafc' }}>
                {detailTab === 'daily'     && <DailyProgressTab      project={p} />}
                {detailTab === 'finishing' && <FinishingChecklistTab project={p} />}
            </div>
        </div>
    );
}

function StatusSelect({ status, onChange }) {
    const opts = [
        { v: 'WIP',       label: 'WIP',       bg: '#fef9c3', c: '#854d0e' },
        { v: 'Delivered', label: 'Delivered', bg: '#d1fae5', c: '#065f46' },
        { v: 'Rejected',  label: 'Rejected',  bg: '#fee2e2', c: '#991b1b' },
        { v: 'On Hold',   label: 'On Hold',   bg: '#fef3c7', c: '#92400e' },
    ];
    const cur = opts.find(o => o.v === status) || opts[0];
    return (
        <select value={status || 'WIP'} onChange={e => onChange(e.target.value)}
            style={{ fontSize: 11, fontWeight: 700, padding: '2px 6px', borderRadius: 8, border: 'none', background: cur.bg, color: cur.c, cursor: 'pointer', marginTop: 2 }}>
            {opts.map(o => <option key={o.v} value={o.v}>{o.label}</option>)}
        </select>
    );
}
