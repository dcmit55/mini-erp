import React, { useState, useCallback, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getProject } from '../api/projects';
import { getEmployees } from '../api/employees';
import { useApp } from '../context/AppContext';
import {
    mkProject, buildDP, calcProg, todayStr,
} from '../../modules/mascot/constants/mascotConstants';
import { MQCtx } from '../../modules/mascot/MascotQC';
import DailyProgressTab      from '../../modules/mascot/components/DailyProgressTab';
import FinishingChecklistTab from '../../modules/mascot/components/FinishingChecklistTab';
import DefectLogModal        from '../../modules/mascot/components/DefectLogModal';
import ReworkModal           from '../../modules/mascot/components/ReworkModal';
import PhotoModal            from '../../modules/mascot/components/PhotoModal';
import { ArrowLeft } from 'lucide-react';

/* ── Local storage key per project ── */
const lsKey = (uid) => `mascot_qc_ws_${uid}`;

function loadLocal(uid) {
    try {
        const raw = localStorage.getItem(lsKey(uid));
        if (raw) return JSON.parse(raw);
    } catch { /* ignore */ }
    return null;
}

function saveLocal(uid, data) {
    try { localStorage.setItem(lsKey(uid), JSON.stringify(data)); } catch { /* ignore */ }
}

/* MQCtx is imported from the mascot module — we provide a minimal value */

/* ── Main Page ── */
export default function MascotJoWorkspacePage() {
    const { joUID }  = useParams();
    const navigate   = useNavigate();
    const { setActiveJo } = useApp();

    const { data: apiProject, isLoading, isError } = useQuery({
        queryKey: ['project', joUID],
        queryFn: () => getProject(joUID),
    });

    useEffect(() => {
        if (joUID) setActiveJo(joUID);
        return () => setActiveJo(null);
    }, [joUID, setActiveJo]);

    /* operators from employees API */
    const [operators, setOperators] = useState([]);
    useEffect(() => {
        getEmployees()
            .then(data => {
                const names = (Array.isArray(data) ? data : data?.data ?? [])
                    .map(e => e.name || e.full_name || e.employee_name || '')
                    .filter(Boolean);
                if (names.length) setOperators(names);
            })
            .catch(() => {});
    }, []);

    /* local mascot QC data (localStorage) */
    const [localProject, setLocalProject] = useState(() => loadLocal(joUID));
    const [tab, setTab] = useState('daily'); // 'daily' | 'finishing'

    /* ensure local project exists once API data is loaded */
    useEffect(() => {
        if (!apiProject) return;
        setLocalProject(prev => {
            if (prev) return prev;
            const created = mkProject({
                jobNumber:      apiProject.job_number || apiProject.uid?.slice(0, 8) || '',
                projectName:    apiProject.project_name,
                supervisor:     apiProject.supervisor_name || '',
                inspectionDate: '',
                deadline:       apiProject.deadline || '',
                mascotType:     'Mascot',
                totalUnit:      apiProject.total_unit || 1,
            });
            created.id = joUID;
            saveLocal(joUID, created);
            return created;
        });
    }, [apiProject, joUID]);

    /* update helper */
    const updateLocalProject = useCallback((updater) => {
        setLocalProject(prev => {
            if (!prev) return prev;
            const next = structuredClone(prev);
            updater(next);
            saveLocal(joUID, next);
            return next;
        });
    }, [joUID]);

    /* modals */
    const [_fail,   setFail]   = useState(null);
    const [_dpFail, setDpFail] = useState(null);
    const [_rework, setRework] = useState(null);
    const [_photo,  setPhoto]  = useState(null);
    const [dailyDate, setDailyDate] = useState(todayStr);

    const updateProject = useCallback((pid, updater) => {
        updateLocalProject(updater);
    }, [updateLocalProject]);

    const ensureDate = useCallback((pid, date) => {
        updateLocalProject(proj => {
            if (!proj.dailyProgress) proj.dailyProgress = {};
            if (!proj.dailyProgress[date]) {
                proj.dailyProgress[date] = { sessionNote: '', items: buildDP() };
            }
        });
    }, [updateLocalProject]);

    const logDpDefect = useCallback((pid, date, itemId, note, severity, cat) => {
        updateLocalProject(proj => {
            const log = {
                id: Date.now() + '',
                defectCategory: cat || 'Other',
                itemName: itemId,
                failNote: note || '',
                severity: severity || 'Major',
                failDate: new Date().toISOString(),
                reworkStatus: 'OPEN',
                reworkAssignedTo: '',
                reworkNote: '',
                origin: 'daily',
                dailyDate: date,
            };
            proj.rejectLog = [log, ...(proj.rejectLog || [])];
        });
    }, [updateLocalProject]);

    /* ctx value — same shape as MQCtx */
    const ctx = {
        projects: localProject ? [localProject] : [],
        updateProject,
        addProject: () => {},
        ensureDpEntry: ensureDate,
        view: 'detail', setView: () => {},
        projectId: joUID,
        detailTab: tab, setDetailTab: setTab,
        jobsTab: 'active', setJobsTab: () => {},
        dailyDate, setDailyDate,
        modal: null, setModal: () => {},
        _fail, setFail,
        _dpFail, setDpFail,
        _rework, setRework,
        _photo, setPhoto,
        _msOpen: false, setMsOpen: () => {},
        rejectFilter: 'ALL', setRejectFilter: () => {},
        npForm: {}, setNpForm: () => {},
        openDetail: () => {}, goBack: () => {},
        createProject: () => {},
        ensureDate, logDpDefect,
        operators,
    };

    if (isLoading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 240, color: '#94a3b8' }}>
            <Spinner /> Loading workspace…
        </div>
    );

    if (isError || !apiProject) return (
        <div style={{ padding: 24, color: '#ef4444', background: '#fef2f2', borderRadius: 12 }}>
            Failed to load project.{' '}
            <button onClick={() => navigate('/projects')} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#f97316' }}>← Back</button>
        </div>
    );

    if (!localProject) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 240, color: '#94a3b8' }}>
            <Spinner /> Initializing QC workspace…
        </div>
    );

    return (
        <MQCtx.Provider value={ctx}>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 0 }}>

                {/* ── Header ── */}
                <div style={{ background: '#fff', borderRadius: '12px 12px 0 0', padding: '14px 20px', boxShadow: '0 1px 4px rgba(0,0,0,.07)', borderLeft: '4px solid #f97316' }}>
                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: 12 }}>
                        <button onClick={() => navigate('/projects')}
                            style={{ background: '#fff7ed', border: '1px solid #fed7aa', borderRadius: 8, padding: '7px 9px', cursor: 'pointer', display: 'flex', alignItems: 'center', color: '#c2410c', outline: 'none' }}>
                            <ArrowLeft size={15} />
                        </button>
                        <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                                <h2 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#0f172a', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {apiProject.project_name}
                                </h2>
                                <span style={{ fontSize: 10, fontWeight: 700, padding: '2px 8px', borderRadius: 999, background: '#fff7ed', color: '#c2410c', border: '1px solid #fed7aa' }}>
                                    🧸 Mascot QC
                                </span>
                                <StatusBadge status={localProject.status} />
                            </div>
                            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 3 }}>
                                JO #{apiProject.job_number || apiProject.uid?.slice(0, 8)}
                                {' · '}{apiProject.total_unit ?? '—'} units
                                {apiProject.deadline && <> · Due: <strong style={{ color: '#64748b' }}>{apiProject.deadline}</strong></>}
                            </div>
                        </div>
                        <div style={{ textAlign: 'right' }}>
                            <div style={{ fontSize: 20, fontWeight: 800, color: '#6366f1' }}>{localProject.progress || 0}%</div>
                            <div style={{ fontSize: 10, color: '#94a3b8' }}>progress</div>
                        </div>
                    </div>

                    {/* progress bar */}
                    <div style={{ height: 4, background: '#e2e8f0', borderRadius: 999, marginTop: 10, overflow: 'hidden' }}>
                        <div style={{ height: '100%', width: `${localProject.progress || 0}%`, background: '#f97316', borderRadius: 999, transition: 'width .3s' }} />
                    </div>
                </div>

                {/* ── Tab bar ── */}
                <div style={{ background: '#fff', borderBottom: '1px solid #f1f5f9', display: 'flex' }}>
                    {[
                        { id: 'daily',     label: '📋 Daily Progress' },
                        { id: 'finishing', label: '✅ Finishing Checklist' },
                    ].map(t => (
                        <button key={t.id} onClick={() => setTab(t.id)}
                            style={{
                                padding: '11px 20px', fontSize: 12, fontWeight: 600, border: 'none', cursor: 'pointer', outline: 'none',
                                background: 'none', whiteSpace: 'nowrap',
                                color: tab === t.id ? '#6366f1' : '#64748b',
                                borderBottom: tab === t.id ? '2px solid #6366f1' : '2px solid transparent',
                            }}>
                            {t.label}
                        </button>
                    ))}
                </div>

                {/* ── Tab content ── */}
                <div style={{ background: '#f8fafc', borderRadius: '0 0 12px 12px', padding: '16px 20px', minHeight: 320 }}>
                    {tab === 'daily'     && <DailyProgressTab      project={localProject} />}
                    {tab === 'finishing' && <FinishingChecklistTab project={localProject} />}
                </div>
            </div>

            {/* modals */}
            {_fail   && <DefectLogModal type="checklist" />}
            {_dpFail && <DefectLogModal type="daily" />}
            {_rework && <ReworkModal />}
            {_photo  && <PhotoModal />}
        </MQCtx.Provider>
    );
}

function StatusBadge({ status }) {
    const map = {
        Delivered: { bg: '#d1fae5', text: '#065f46' },
        Rejected:  { bg: '#fee2e2', text: '#991b1b' },
        'On Hold': { bg: '#fef3c7', text: '#92400e' },
    };
    const c = map[status] ?? { bg: '#ede9fe', text: '#5b21b6' };
    return (
        <span style={{ padding: '3px 10px', borderRadius: 999, fontSize: 11, fontWeight: 700, background: c.bg, color: c.text }}>
            {status ?? 'In Progress'}
        </span>
    );
}

function Spinner() {
    return (
        <div style={{ width: 22, height: 22, border: '3px solid #e2e8f0', borderTopColor: '#f97316', borderRadius: '50%', animation: 'spin 1s linear infinite', marginRight: 10 }} />
    );
}
