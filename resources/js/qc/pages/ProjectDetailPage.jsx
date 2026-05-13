import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getProject } from '../api/projects';
import { ArrowLeft, LayoutDashboard, Calendar, ClipboardList, AlertTriangle, Image, Package, FileText } from 'lucide-react';
import ProjectDashboardTab from '../components/tabs/ProjectDashboardTab';
import DailyProgressTab from '../components/tabs/DailyProgressTab';
import ChecklistTab from '../components/tabs/ChecklistTab';
import PackingTab from '../components/tabs/PackingTab';
import RejectLogTab from '../components/tabs/RejectLogTab';
import PhotosTab from '../components/tabs/PhotosTab';
import ProjectReportTab from '../components/tabs/ProjectReportTab';

const TABS = [
    { id: 'dashboard', label: 'Dashboard',           icon: LayoutDashboard },
    { id: 'daily',     label: 'Daily Progress',      icon: Calendar },
    { id: 'checklist', label: 'Finishing Checklist', icon: ClipboardList },
    { id: 'packing',   label: 'Packing',             icon: Package },
    { id: 'rejects',   label: 'Reject Log',          icon: AlertTriangle },
    { id: 'photos',    label: 'Photos',               icon: Image },
    { id: 'report',    label: 'Report',               icon: FileText },
];

const statusColor = (s) =>
    s === 'Delivered' ? '#10b981' : s === 'Rejected' ? '#ef4444' : '#f59e0b';

function ProgressRing({ value, size = 52 }) {
    const r = (size - 6) / 2;
    const circ = 2 * Math.PI * r;
    const offset = circ - (value / 100) * circ;
    return (
        <svg width={size} height={size} style={{ transform: 'rotate(-90deg)' }}>
            <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#e2e8f0" strokeWidth={5} />
            <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#6366f1" strokeWidth={5}
                strokeDasharray={circ} strokeDashoffset={offset} strokeLinecap="round" />
            <text x="50%" y="50%" textAnchor="middle" dominantBaseline="central"
                style={{ transform: 'rotate(90deg)', transformOrigin: '50% 50%', fontSize: 12, fill: '#334155', fontWeight: 700 }}>
                {value}%
            </text>
        </svg>
    );
}

export default function ProjectDetailPage() {
    const { uid } = useParams();
    const nav = useNavigate();
    const [activeTab, setActiveTab] = useState('dashboard');

    const { data: project, isLoading, isError } = useQuery({
        queryKey: ['project', uid],
        queryFn: () => getProject(uid),
    });

    if (isLoading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 240, color: '#94a3b8' }}>
            <div style={{ width: 28, height: 28, border: '3px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 1s linear infinite', marginRight: 12 }} />
            Loading project…
        </div>
    );
    if (isError || !project) return (
        <div style={{ padding: 24, color: '#ef4444', background: '#fef2f2', borderRadius: 12 }}>Failed to load project.</div>
    );

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Back + Header */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                <button onClick={() => nav('/jobs')}
                    style={{ background: '#f1f5f9', border: 'none', borderRadius: 8, padding: '8px 10px', cursor: 'pointer', display: 'flex', alignItems: 'center', color: '#475569', outline: 'none' }}>
                    <ArrowLeft size={16} />
                </button>
                <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontWeight: 600, fontSize: 15, color: '#1e293b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {project.project_name}
                    </div>
                    <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>
                        {project.mascot_type} · JO #{project.job_number}
                    </div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexShrink: 0 }}>
                    <ProgressRing value={project.progress} />
                    <span style={{ padding: '3px 10px', borderRadius: 999, fontSize: 12, fontWeight: 600, background: statusColor(project.status) + '20', color: statusColor(project.status) }}>
                        {project.status}
                    </span>
                </div>
            </div>

            {/* Tab panel */}
            <div style={{ background: '#fff', borderRadius: 14, boxShadow: '0 1px 3px rgba(0,0,0,.06)', overflow: 'hidden' }}>
                <div style={{ display: 'flex', overflowX: 'auto' }}>
                    {TABS.map(t => {
                        const Icon = t.icon;
                        const active = activeTab === t.id;
                        return (
                            <button key={t.id} onClick={() => setActiveTab(t.id)}
                                style={{
                                    display: 'flex', alignItems: 'center', gap: 6,
                                    padding: '12px 18px', fontSize: 13, fontWeight: 500,
                                    border: 'none', borderBottom: active ? '2px solid #6366f1' : '2px solid transparent',
                                    background: 'none', cursor: 'pointer', whiteSpace: 'nowrap',
                                    color: active ? '#6366f1' : '#64748b', outline: 'none',
                                    transition: 'color .15s, border-color .15s',
                                }}>
                                <Icon size={14} /> {t.label}
                            </button>
                        );
                    })}
                </div>
                <div style={{ padding: 16 }}>
                    {activeTab === 'dashboard' && <ProjectDashboardTab project={project} />}
                    {activeTab === 'daily'     && <DailyProgressTab project={project} />}
                    {activeTab === 'checklist' && <ChecklistTab project={project} />}
                    {activeTab === 'packing'   && <PackingTab project={project} />}
                    {activeTab === 'rejects'   && <RejectLogTab project={project} />}
                    {activeTab === 'photos'    && <PhotosTab project={project} />}
                    {activeTab === 'report'    && <ProjectReportTab project={project} />}
                </div>
            </div>
        </div>
    );
}
