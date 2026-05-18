import { useState, useCallback, useRef } from 'react';
import {
    STORAGE_KEY, buildDP, ensurePacking, calcProg, todayStr,
} from '../constants/mascotConstants';

function loadProjects() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const projects = JSON.parse(raw);
        projects.forEach(p => ensurePacking(p));
        return projects;
    } catch {
        return [];
    }
}

export function useMascotStorage() {
    const [projects, setProjectsState] = useState(() => loadProjects());
    const saveTimer = useRef(null);

    const save = useCallback((updated) => {
        clearTimeout(saveTimer.current);
        saveTimer.current = setTimeout(() => {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(updated));
        }, 400);
    }, []);

    const setProjects = useCallback((updater) => {
        setProjectsState(prev => {
            const next = typeof updater === 'function' ? updater(prev) : updater;
            save(next);
            return next;
        });
    }, [save]);

    const updateProject = useCallback((id, mutate) => {
        setProjects(prev => prev.map(p => {
            if (p.id !== id) return p;
            const updated = { ...p };
            mutate(updated);
            updated.progress = calcProg(updated);
            return updated;
        }));
    }, [setProjects]);

    const addProject = useCallback((project) => {
        setProjects(prev => [project, ...prev]);
    }, [setProjects]);

    const ensureDpEntry = useCallback((projectId, date) => {
        setProjects(prev => prev.map(p => {
            if (p.id !== projectId) return p;
            const dp = { ...(p.dailyProgress || {}) };
            if (!dp[date]) dp[date] = { operators: [], sessionNote: '', items: buildDP() };
            return { ...p, dailyProgress: dp };
        }));
    }, [setProjects]);

    const getProject = useCallback((id) => {
        return projects.find(p => p.id === id) || null;
    }, [projects]);

    return { projects, setProjects, updateProject, addProject, ensureDpEntry, getProject };
}
