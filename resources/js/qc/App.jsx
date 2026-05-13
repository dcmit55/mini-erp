import React from 'react';
import {
    HashRouter, Routes, Route, Navigate,
    useLocation, useNavigate,
} from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { LayoutDashboard, Layers } from 'lucide-react';
import { AppProvider } from './context/AppContext';
import { useMediaQuery } from './hooks/useMediaQuery';

import OverviewPage      from './pages/OverviewPage';
import JobsPage          from './pages/JobsPage';
import JoWorkspacePage   from './pages/JoWorkspacePage';
import StagePage         from './pages/StagePage';
import StageTabOutlet    from './pages/stage/StageTabOutlet';

// ─── React Query client ───────────────────────────────────────────────────────

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { staleTime: 30_000, retry: 1 },
    },
});

// ─── Nav config ───────────────────────────────────────────────────────────────

const NAV_TABS = [
    { path: '/overview', label: 'Overview', Icon: LayoutDashboard },
    { path: '/projects', label: 'Projects',  Icon: Layers         },
];

// ─── Shell ────────────────────────────────────────────────────────────────────

function Shell({ children }) {
    const loc      = useLocation();
    const nav      = useNavigate();
    const isMobile = useMediaQuery('(max-width: 640px)');

    // Inside a workspace the global nav is hidden to save vertical space
    const hideNav = loc.pathname.startsWith('/projects/') || loc.pathname.startsWith('/jobs/');

    const isActive = (path) =>
        loc.pathname === path || (path === '/projects' && loc.pathname === '/jobs');

    if (isMobile) {
        return (
            <div style={{ paddingBottom: hideNav ? 0 : 64 }}>
                <main>{children}</main>

                {!hideNav && (
                    <nav style={{
                        position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 200,
                        height: 60,
                        background: 'rgba(255,255,255,.96)',
                        backdropFilter: 'blur(8px)',
                        borderTop: '1px solid rgba(226,232,240,.8)',
                        display: 'flex',
                    }}>
                        {NAV_TABS.map(({ path, label, Icon }) => {
                            const active = isActive(path);
                            return (
                                <button
                                    key={path}
                                    onClick={() => nav(path)}
                                    style={{
                                        flex: 1,
                                        display: 'flex', flexDirection: 'column',
                                        alignItems: 'center', justifyContent: 'center', gap: 3,
                                        border: 'none', background: 'none', cursor: 'pointer', outline: 'none',
                                        color: active ? '#6366f1' : '#94a3b8',
                                        fontSize: 10, fontWeight: 600,
                                        transition: 'color .15s',
                                    }}
                                >
                                    <Icon size={20} strokeWidth={active ? 2.5 : 1.5} />
                                    {label}
                                </button>
                            );
                        })}
                    </nav>
                )}
            </div>
        );
    }

    // Desktop — top-left pill nav
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {!hideNav && (
                <nav style={{
                    display: 'flex', gap: 4,
                    background: 'rgba(255,255,255,.9)',
                    backdropFilter: 'blur(6px)',
                    borderRadius: 10, padding: 4,
                    width: 'fit-content',
                    boxShadow: '0 1px 3px rgba(0,0,0,.06)',
                    border: '1px solid rgba(226,232,240,.8)',
                }}>
                    {NAV_TABS.map(({ path, label }) => {
                        const active = isActive(path);
                        return (
                            <button
                                key={path}
                                onClick={() => nav(path)}
                                style={{
                                    padding: '6px 18px', fontSize: 13, fontWeight: 500,
                                    borderRadius: 7, border: 'none', cursor: 'pointer', outline: 'none',
                                    background: active ? '#6366f1' : 'transparent',
                                    color: active ? '#fff' : '#64748b',
                                    transition: 'background .15s, color .15s',
                                }}
                            >
                                {label}
                            </button>
                        );
                    })}
                </nav>
            )}
            <main>{children}</main>
        </div>
    );
}

// ─── App ──────────────────────────────────────────────────────────────────────

export default function App({ context = 'mascot', authUser = null }) {
    return (
        <QueryClientProvider client={queryClient}>
            <AppProvider context={context} authUser={authUser}>
                <HashRouter>
                    <Shell>
                        <Routes>
                            <Route index element={<Navigate to="/overview" replace />} />

                            <Route path="/overview"  element={<OverviewPage />} />
                            <Route path="/projects"  element={<JobsPage />} />

                            <Route path="/projects/:joUID" element={<JoWorkspacePage />} />

                            <Route path="/projects/:joUID/:stage" element={<StagePage />}>
                                <Route index element={<Navigate to="dashboard" replace />} />
                                <Route path=":tab" element={<StageTabOutlet />} />
                            </Route>

                            {/* Legacy aliases */}
                            <Route path="/jobs" element={<Navigate to="/projects" replace />} />
                            <Route path="/jobs/:uid" element={<LegacyJobRedirect />} />

                            <Route path="*" element={<Navigate to="/overview" replace />} />
                        </Routes>
                    </Shell>
                </HashRouter>
            </AppProvider>
        </QueryClientProvider>
    );
}

function LegacyJobRedirect() {
    const loc = useLocation();
    const uid = loc.pathname.split('/').at(-1);
    return <Navigate to={`/projects/${uid}`} replace />;
}
