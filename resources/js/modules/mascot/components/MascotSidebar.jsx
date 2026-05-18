import React from 'react';
import { useMQ } from '../MascotQC';

const NAV = [
    { id: 'overview', label: 'Overview',  icon: '📊' },
    { id: 'jobs',     label: 'Projects',  icon: '🗂️' },
];

export default function MascotSidebar() {
    const { view, setView, projects, setModal, _msOpen, setMsOpen } = useMQ();

    const total  = projects.length;
    const wip    = projects.filter(p => p.status === 'WIP').length;
    const done   = projects.filter(p => p.status === 'DONE').length;

    return (
        <>
            {/* mobile overlay */}
            {_msOpen && (
                <div className="mq-sidebar-overlay" onClick={() => setMsOpen(false)} />
            )}

            <aside className={`mq-sidebar${_msOpen ? ' open' : ''}`}>
                <div className="mq-sidebar-logo">
                    <span className="mq-logo-icon">🧸</span>
                    <div>
                        <div style={{ fontWeight: 700, fontSize: 15, color: '#1e293b' }}>Mascot QC</div>
                        <div style={{ fontSize: 11, color: '#94a3b8' }}>Quality Control System</div>
                    </div>
                </div>

                <div className="mq-sidebar-stats">
                    <div className="mq-sb-stat"><span className="mq-sb-num">{total}</span><span className="mq-sb-lbl">Total</span></div>
                    <div className="mq-sb-stat"><span className="mq-sb-num" style={{ color: '#f59e0b' }}>{wip}</span><span className="mq-sb-lbl">WIP</span></div>
                    <div className="mq-sb-stat"><span className="mq-sb-num" style={{ color: '#10b981' }}>{done}</span><span className="mq-sb-lbl">Done</span></div>
                </div>

                <nav style={{ flex: 1 }}>
                    {NAV.map(n => (
                        <button
                            key={n.id}
                            className={`mq-nav-item${view === n.id ? ' active' : ''}`}
                            onClick={() => { setView(n.id); setMsOpen(false); }}
                        >
                            <span className="mq-nav-icon">{n.icon}</span>
                            <span>{n.label}</span>
                        </button>
                    ))}
                </nav>

                <div style={{ padding: '0 12px 16px' }}>
                    <button className="mq-btn-primary" style={{ width: '100%' }}
                        onClick={() => { setModal('newProject'); setMsOpen(false); }}>
                        + New Project
                    </button>
                </div>
            </aside>

            {/* mobile top bar */}
            <button className="mq-hamburger" onClick={() => setMsOpen(v => !v)}>
                ☰
            </button>
        </>
    );
}
