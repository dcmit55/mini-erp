import React, { useState, useMemo } from 'react';
import { useMQ } from '../MascotQC';
import { fmtDate } from '../constants/mascotConstants';

const STATUS_TABS = ['All', 'WIP', 'Delivered', 'Rejected'];

export default function MascotJobs() {
    const { projects, openDetail, setModal, updateProject } = useMQ();
    const [activeTab, setActiveTab] = useState('All');
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        let list = [...projects];
        if (activeTab !== 'All') list = list.filter(p => p.status === activeTab);
        if (search) list = list.filter(p =>
            p.projectName.toLowerCase().includes(search.toLowerCase()) ||
            (p.jobNumber || '').toLowerCase().includes(search.toLowerCase())
        );
        return list;
    }, [projects, activeTab, search]);

    const countFor = (tab) => tab === 'All' ? projects.length : projects.filter(p => p.status === tab).length;

    const uploadCover = (projectId, file) => {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => {
            updateProject(projectId, proj => { proj.coverImage = ev.target.result; });
        };
        reader.readAsDataURL(file);
    };

    return (
        <div style={{ padding: '20px 24px', maxWidth: 1100 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16, flexWrap: 'wrap', gap: 10 }}>
                <h2 style={{ margin: 0, fontSize: 19, fontWeight: 700, color: '#1e293b' }}>Job Orders</h2>
                <button className="mq-btn-primary" onClick={() => setModal('newProject')}>+ New Project</button>
            </div>

            <div style={{ display: 'flex', gap: 10, marginBottom: 16, alignItems: 'center', flexWrap: 'wrap' }}>
                <div style={{ display: 'flex', gap: 4, background: 'rgba(255,255,255,.9)', borderRadius: 10, padding: 4, boxShadow: '0 1px 3px rgba(0,0,0,.06)', border: '1px solid #e2e8f0' }}>
                    {STATUS_TABS.map(tab => (
                        <button key={tab}
                            onClick={() => setActiveTab(tab)}
                            style={{
                                padding: '5px 14px', borderRadius: 7, border: 'none', cursor: 'pointer', outline: 'none',
                                background: activeTab === tab ? '#6366f1' : 'transparent',
                                color: activeTab === tab ? '#fff' : '#64748b',
                                fontSize: 12, fontWeight: 500, transition: 'all .15s',
                            }}>
                            {tab} <span style={{ fontSize: 10, opacity: 0.8 }}>({countFor(tab)})</span>
                        </button>
                    ))}
                </div>
                <input className="mq-input" style={{ maxWidth: 220, marginLeft: 'auto' }}
                    placeholder="Search by name or JO #…" value={search} onChange={e => setSearch(e.target.value)} />
            </div>

            {filtered.length === 0 && (
                <div style={{ textAlign: 'center', padding: 48, color: '#94a3b8' }}>
                    <div style={{ fontSize: 36, marginBottom: 8 }}>🧸</div>
                    <div style={{ fontSize: 14 }}>
                        {search ? 'No projects match your search' : 'No projects in this category'}
                    </div>
                </div>
            )}

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(260px,1fr))', gap: 16 }}>
                {filtered.map(p => (
                    <ProjectCard key={p.id} project={p}
                        onOpen={() => openDetail(p.id)}
                        onUploadCover={(file) => uploadCover(p.id, file)} />
                ))}
            </div>
        </div>
    );
}

function ProjectCard({ project: p, onOpen, onUploadCover }) {
    const progress = p.progress || 0;
    const defects  = (p.rejectLog || []).length;
    const openDef  = (p.rejectLog || []).filter(r => (r.reworkStatus || r.status) === 'OPEN').length;
    const daysLeft = p.deadline
        ? Math.ceil((new Date(p.deadline) - new Date()) / 86400000)
        : null;
    const stColor = p.status === 'Delivered' ? { bg: '#d1fae5', text: '#065f46' }
        : p.status === 'Rejected' ? { bg: '#fee2e2', text: '#991b1b' }
        : { bg: '#fef9c3', text: '#854d0e' };

    return (
        <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 2px 8px rgba(0,0,0,.08)', overflow: 'hidden', display: 'flex', flexDirection: 'column', transition: 'box-shadow .15s' }}
            onMouseEnter={e => e.currentTarget.style.boxShadow = '0 4px 16px rgba(0,0,0,.12)'}
            onMouseLeave={e => e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,.08)'}>

            {/* cover */}
            <div style={{ height: 120, position: 'relative', cursor: 'pointer', flexShrink: 0 }} onClick={onOpen}>
                <div style={{
                    position: 'absolute', inset: 0,
                    background: p.coverImage ? `url(${p.coverImage}) center/cover no-repeat` : (p.coverGradient || 'linear-gradient(135deg,#667eea,#764ba2)'),
                }} />
                <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,.25)' }} />
                {/* status badge */}
                <span style={{ position: 'absolute', top: 8, left: 8, fontSize: 10, fontWeight: 700, padding: '2px 8px', borderRadius: 999, background: stColor.bg, color: stColor.text }}>
                    {p.status || 'WIP'}
                </span>
                {/* type badge */}
                <span style={{ position: 'absolute', top: 8, right: 36, fontSize: 10, fontWeight: 600, padding: '2px 7px', borderRadius: 999, background: 'rgba(255,255,255,.2)', color: '#fff', border: '1px solid rgba(255,255,255,.3)' }}>
                    {p.mascotType || 'Mascot'}
                </span>
                {/* camera upload button */}
                <label style={{ position: 'absolute', top: 6, right: 6, width: 26, height: 26, borderRadius: '50%', background: 'rgba(0,0,0,.35)', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: 13 }}
                    onClick={e => e.stopPropagation()}>
                    📷
                    <input type="file" accept="image/*" hidden onChange={e => onUploadCover(e.target.files[0])} />
                </label>
            </div>

            {/* body */}
            <div style={{ padding: '12px 14px', flex: 1, display: 'flex', flexDirection: 'column', gap: 6, cursor: 'pointer' }} onClick={onOpen}>
                <div style={{ fontSize: 14, fontWeight: 700, color: '#1e293b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {p.projectName}
                </div>
                {p.jobNumber && (
                    <div style={{ fontSize: 11, color: '#94a3b8' }}>{p.jobNumber}</div>
                )}
                {p.supervisor && (
                    <div style={{ fontSize: 11, color: '#64748b' }}>👤 {p.supervisor}</div>
                )}

                <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap' }}>
                    <span style={{ fontSize: 10, padding: '1px 7px', borderRadius: 999, background: '#f1f5f9', color: '#64748b' }}>
                        {p.totalUnit || 1} unit{(p.totalUnit || 1) > 1 ? 's' : ''}
                    </span>
                    {defects > 0 && (
                        <span style={{ fontSize: 10, padding: '1px 7px', borderRadius: 999, background: openDef > 0 ? '#fee2e2' : '#f1f5f9', color: openDef > 0 ? '#dc2626' : '#64748b' }}>
                            {openDef > 0 ? `${openDef} open defect${openDef > 1 ? 's' : ''}` : `${defects} defect${defects > 1 ? 's' : ''}`}
                        </span>
                    )}
                </div>

                {/* progress bar */}
                <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 10, color: '#94a3b8', marginBottom: 3 }}>
                        <span>Progress</span><span style={{ fontWeight: 600, color: '#6366f1' }}>{progress}%</span>
                    </div>
                    <div style={{ height: 5, background: '#f1f5f9', borderRadius: 999, overflow: 'hidden' }}>
                        <div style={{ height: '100%', width: `${progress}%`, background: '#6366f1', borderRadius: 999 }} />
                    </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 10, color: '#94a3b8' }}>
                    <span>{p.inspectionDate ? `Inspection: ${fmtDate(p.inspectionDate)}` : ''}</span>
                    {daysLeft !== null && (
                        <span style={{ color: daysLeft < 0 ? '#ef4444' : daysLeft < 3 ? '#f59e0b' : '#94a3b8' }}>
                            {daysLeft < 0 ? `${Math.abs(daysLeft)}d overdue` : `${daysLeft}d left`}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}
