import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import './MascotQC.css';
import { useMascotStorage } from './hooks/useMascotStorage';
import {
    mkProject, todayStr, buildDP, calcProg,
    DAILY_SECTIONS, rStatus,
} from './constants/mascotConstants';
import { getEmployees } from '../../qc/api/employees';
import MascotSidebar   from './components/MascotSidebar';
import MascotOverview  from './components/MascotOverview';
import MascotJobs      from './components/MascotJobs';
import MascotDetail    from './components/MascotDetail';
import DefectLogModal  from './components/DefectLogModal';
import ReworkModal     from './components/ReworkModal';
import PhotoModal      from './components/PhotoModal';

/* ── Context ────────────────────────────────────────────────────────────── */
export const MQCtx = createContext(null);
export const useMQ = () => useContext(MQCtx);

/* ── Root ───────────────────────────────────────────────────────────────── */
export default function MascotQC() {
    const storage = useMascotStorage();

    /* fetch employees from API to use as operator list */
    const [operators, setOperators] = useState([]);
    useEffect(() => {
        getEmployees()
            .then(data => {
                const names = (Array.isArray(data) ? data : data?.data ?? [])
                    .map(e => e.name || e.full_name || e.employee_name || '')
                    .filter(Boolean);
                if (names.length) setOperators(names);
            })
            .catch(() => { /* fall back to empty — handled in UI */ });
    }, []);
    const { projects, updateProject, addProject } = storage;

    /* navigation */
    const [view,      setView]      = useState('overview'); // 'overview'|'jobs'|'detail'
    const [projectId, setProjectId] = useState(null);
    const [detailTab, setDetailTab] = useState('daily');    // 'daily'|'finishing'
    const [jobsTab,   setJobsTab]   = useState('active');   // 'active'|'done'

    /* date for daily tab */
    const [dailyDate, setDailyDate] = useState(todayStr());

    /* modals */
    const [modal,    setModal]    = useState(null); // null | 'newProject'
    const [_fail,    setFail]     = useState(null); // defect modal for checklist items
    const [_dpFail,  setDpFail]   = useState(null); // defect modal for daily items
    const [_rework,  setRework]   = useState(null); // rework status modal
    const [_photo,   setPhoto]    = useState(null); // photo lightbox {urls, idx}
    const [_msOpen,  setMsOpen]   = useState(false);// mobile sidebar

    /* filter */
    const [rejectFilter, setRejectFilter] = useState('ALL');

    /* ── helpers ── */
    const openDetail = useCallback((id, tab = 'daily') => {
        setProjectId(id);
        setDetailTab(tab);
        setView('detail');
    }, []);

    const goBack = useCallback(() => {
        setView(prev => prev === 'detail' ? 'jobs' : 'overview');
        if (view !== 'detail') setProjectId(null);
    }, [view]);

    /* ── new project form state ── */
    const [npForm, setNpForm] = useState({
        jobNumber: '', projectName: '', supervisor: '',
        inspectionDate: '', deadline: '', mascotType: 'Mascot', totalUnit: 1,
    });

    const createProject = useCallback(() => {
        if (!npForm.projectName.trim()) return;
        const p = mkProject(npForm);
        addProject(p);
        setNpForm({ jobNumber: '', projectName: '', supervisor: '', inspectionDate: '', deadline: '', mascotType: 'Mascot', totalUnit: 1 });
        setModal(null);
        openDetail(p.id);
    }, [npForm, addProject, openDetail]);

    /* ── daily progress helpers ── */
    const ensureDate = useCallback((pid, date) => {
        storage.ensureDpEntry(pid, date);
    }, [storage]);

    /* auto defect on DP fail */
    const logDpDefect = useCallback((pid, date, itemId, note, severity, cat) => {
        updateProject(pid, p => {
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
            p.rejectLog = [log, ...(p.rejectLog || [])];
        });
    }, [updateProject]);

    /* ── ctx value ── */
    const ctx = {
        ...storage,
        operators,
        view, setView,
        projectId, setProjectId,
        detailTab, setDetailTab,
        jobsTab, setJobsTab,
        dailyDate, setDailyDate,
        modal, setModal,
        _fail, setFail,
        _dpFail, setDpFail,
        _rework, setRework,
        _photo, setPhoto,
        _msOpen, setMsOpen,
        rejectFilter, setRejectFilter,
        npForm, setNpForm,
        openDetail, goBack,
        createProject,
        ensureDate, logDpDefect,
    };

    const activeProject = projects.find(p => p.id === projectId) || null;

    return (
        <MQCtx.Provider value={ctx}>
            <div className="mq-root mq-layout">
                <MascotSidebar />

                <main className="mq-main">
                    {view === 'overview' && <MascotOverview />}
                    {view === 'jobs'     && <MascotJobs />}
                    {view === 'detail'   && activeProject && <MascotDetail project={activeProject} />}
                </main>
            </div>

            {/* ── Modals ── */}
            {modal === 'newProject' && <NewProjectModal />}
            {_fail    && <DefectLogModal type="checklist" />}
            {_dpFail  && <DefectLogModal type="daily" />}
            {_rework  && <ReworkModal />}
            {_photo   && <PhotoModal />}
        </MQCtx.Provider>
    );
}

/* ── New Project Modal ──────────────────────────────────────────────────── */
function NewProjectModal() {
    const { npForm, setNpForm, createProject, setModal } = useMQ();
    const f = (k, v) => setNpForm(p => ({ ...p, [k]: v }));

    return (
        <div className="mq-overlay" onClick={() => setModal(null)}>
            <div className="mq-mbox" style={{ maxWidth: 480 }} onClick={e => e.stopPropagation()}>
                <div className="mq-mbox-hd">
                    <span>New Mascot Project</span>
                    <button className="mq-close" onClick={() => setModal(null)}>✕</button>
                </div>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                    <Row label="Job Number">
                        <input className="mq-input" value={npForm.jobNumber}
                            onChange={e => f('jobNumber', e.target.value)} placeholder="e.g. JO-001" />
                    </Row>
                    <Row label="Project Name *">
                        <input className="mq-input" value={npForm.projectName}
                            onChange={e => f('projectName', e.target.value)} placeholder="e.g. Mascot Beruang Polar" />
                    </Row>
                    <Row label="Supervisor">
                        <input className="mq-input" value={npForm.supervisor}
                            onChange={e => f('supervisor', e.target.value)} />
                    </Row>
                    <Row label="Type">
                        <select className="mq-input" value={npForm.mascotType}
                            onChange={e => f('mascotType', e.target.value)}>
                            <option value="Mascot">Mascot</option>
                            <option value="Inflatable">Inflatable</option>
                        </select>
                    </Row>
                    <Row label="Total Unit">
                        <input className="mq-input" type="number" min={1} value={npForm.totalUnit}
                            onChange={e => f('totalUnit', e.target.value)} />
                    </Row>
                    <Row label="Inspection Date">
                        <input className="mq-input" type="date" value={npForm.inspectionDate}
                            onChange={e => f('inspectionDate', e.target.value)} />
                    </Row>
                    <Row label="Deadline">
                        <input className="mq-input" type="date" value={npForm.deadline}
                            onChange={e => f('deadline', e.target.value)} />
                    </Row>
                </div>
                <div style={{ display: 'flex', gap: 8, marginTop: 16, justifyContent: 'flex-end' }}>
                    <button className="mq-btn-ghost" onClick={() => setModal(null)}>Cancel</button>
                    <button className="mq-btn-primary" onClick={createProject}
                        disabled={!npForm.projectName.trim()}>Create Project</button>
                </div>
            </div>
        </div>
    );
}

function Row({ label, children }) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            <label style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>{label}</label>
            {children}
        </div>
    );
}
