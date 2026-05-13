import React, { useEffect } from 'react';
import { useParams, useNavigate, Outlet } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getProject } from '../api/projects';
import { useApp } from '../context/AppContext';
import { STAGE_LABELS, STAGE_COLORS, TABS, TAB_LABELS } from '../data/models';
import {
    ArrowLeft,
    LayoutDashboard, Activity, ClipboardCheck,
    Wrench, Image, Clock,
} from 'lucide-react';

const TAB_ICONS = {
    dashboard:  LayoutDashboard,
    production: Activity,
    inspection: ClipboardCheck,
    rework:     Wrench,
    gallery:    Image,
    history:    Clock,
};

export default function StagePage() {
    const { joUID, stage, tab } = useParams();
    const navigate = useNavigate();
    const { setActiveStage, setActiveTab, activeTab } = useApp();

    const { data: project } = useQuery({
        queryKey: ['project', joUID],
        queryFn: () => getProject(joUID),
        staleTime: 60_000,
    });

    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;
    const currentTab = tab ?? 'dashboard';

    useEffect(() => {
        setActiveStage(stage);
        setActiveTab(currentTab);
    }, [stage, currentTab, setActiveStage, setActiveTab]);

    const handleTabClick = (t) => {
        setActiveTab(t);
        navigate(`/projects/${joUID}/${stage}/${t}`);
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 0 }}>

            {/* ── Stage header ── */}
            <div style={{
                background: color.bg,
                borderBottom: `2px solid ${color.border}`,
                padding: '12px 20px',
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                borderRadius: '12px 12px 0 0',
            }}>
                <button onClick={() => navigate(`/projects/${joUID}`)} style={iconBtn}>
                    <ArrowLeft size={15} />
                </button>
                <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 14, fontWeight: 700, color: color.text }}>
                        {STAGE_LABELS[stage] ?? stage} Stage
                    </div>
                    <div style={{ fontSize: 11, color: '#64748b', marginTop: 1 }}>
                        {project?.project_name ?? '…'}
                    </div>
                </div>
                {/* Stage stepper (compact) */}
                <StageStepper joUID={joUID} activeStage={stage} navigate={navigate} />
            </div>

            {/* ── Tab bar ── */}
            <div style={{
                background: '#fff',
                borderBottom: '1px solid #f1f5f9',
                display: 'flex',
                overflowX: 'auto',
                paddingLeft: 8,
            }}>
                {TABS.map(t => {
                    const Icon   = TAB_ICONS[t];
                    const active = currentTab === t;
                    return (
                        <button key={t} onClick={() => handleTabClick(t)}
                            style={{
                                display: 'flex', alignItems: 'center', gap: 5,
                                padding: '11px 16px', fontSize: 12, fontWeight: 500,
                                border: 'none',
                                borderBottom: active ? `2px solid ${color.border}` : '2px solid transparent',
                                background: 'none', cursor: 'pointer', whiteSpace: 'nowrap',
                                color: active ? color.text : '#64748b',
                                outline: 'none',
                                transition: 'color .13s, border-color .13s',
                            }}>
                            <Icon size={13} />
                            {TAB_LABELS[t]}
                        </button>
                    );
                })}
            </div>

            {/* ── Tab content area ── */}
            <div style={{ background: '#fff', borderRadius: '0 0 12px 12px', padding: 20, minHeight: 320 }}>
                {/* Outlet renders the matched tab sub-route component */}
                <Outlet context={{ project, stage, tab: currentTab, color }} />

                {/* Fallback placeholder shown until sub-routes are implemented */}
                {!tab && (
                    <PlaceholderTab stage={stage} tab="dashboard" color={color} />
                )}
            </div>
        </div>
    );
}

// ── Stage stepper (compact inline) ───────────────────────────────────────────

function StageStepper({ joUID, activeStage, navigate }) {
    const stageList = ['cutting', 'sewing', 'finishing'];
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
            {stageList.map((s, i) => {
                const color   = STAGE_COLORS[s];
                const isActive = s === activeStage;
                return (
                    <React.Fragment key={s}>
                        <button onClick={() => navigate(`/projects/${joUID}/${s}/dashboard`)}
                            style={{
                                padding: '3px 10px', borderRadius: 999, fontSize: 11, fontWeight: 600,
                                border: `1px solid ${isActive ? color.border : '#e2e8f0'}`,
                                background: isActive ? color.bg : 'transparent',
                                color: isActive ? color.text : '#94a3b8',
                                cursor: 'pointer', outline: 'none',
                                transition: 'all .13s',
                            }}>
                            {STAGE_LABELS[s]}
                        </button>
                        {i < stageList.length - 1 && (
                            <span style={{ color: '#cbd5e1', fontSize: 11 }}>›</span>
                        )}
                    </React.Fragment>
                );
            })}
        </div>
    );
}

// ── Placeholder (Phase 2+ will replace with real tabs) ───────────────────────

export function PlaceholderTab({ stage, tab, color }) {
    return (
        <div style={{
            display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
            padding: '60px 20px', color: '#94a3b8', gap: 12,
        }}>
            <div style={{
                width: 56, height: 56, borderRadius: 16,
                background: color.bg, border: `1px solid ${color.border}40`,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                marginBottom: 4,
            }}>
                {React.createElement(TAB_ICONS[tab] ?? LayoutDashboard, { size: 24, color: color.text })}
            </div>
            <div style={{ fontSize: 15, fontWeight: 600, color: '#334155' }}>
                {STAGE_LABELS[stage]} › {TAB_LABELS[tab]}
            </div>
            <div style={{ fontSize: 13, color: '#94a3b8' }}>
                This section will be implemented in the next phase.
            </div>
        </div>
    );
}

const iconBtn = {
    background: 'rgba(255,255,255,.7)', border: '1px solid #e2e8f0',
    borderRadius: 7, padding: '5px 7px', cursor: 'pointer',
    display: 'flex', alignItems: 'center', color: '#475569', outline: 'none',
};
