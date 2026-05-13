import React, { createContext, useContext, useReducer, useCallback } from 'react';
import { STAGES, TABS, isStageValid, isTabValid } from '../data/models';

// ─── State shape ─────────────────────────────────────────────────────────────

const initialState = {
    /** Currently open Job Order UID */
    activeJoUid: null,

    /** 'cutting' | 'sewing' | 'finishing' | null */
    activeStage: null,

    /** 'dashboard' | 'production' | 'inspection' | 'rework' | 'gallery' | 'history' */
    activeTab: 'dashboard',

    /** { type: string, data: any } | null */
    modal: null,

    /** Simple toast: { message, variant: 'success'|'error'|'info' } | null */
    toast: null,
};

// ─── Reducer ─────────────────────────────────────────────────────────────────

function reducer(state, action) {
    switch (action.type) {
        case 'SET_ACTIVE_JO':
            return { ...state, activeJoUid: action.uid, activeStage: null, activeTab: 'dashboard' };

        case 'SET_ACTIVE_STAGE':
            if (action.stage && !isStageValid(action.stage)) return state;
            return { ...state, activeStage: action.stage ?? null, activeTab: 'dashboard' };

        case 'SET_ACTIVE_TAB':
            if (!isTabValid(action.tab)) return state;
            return { ...state, activeTab: action.tab };

        case 'OPEN_MODAL':
            return { ...state, modal: { type: action.modalType, data: action.data ?? null } };

        case 'CLOSE_MODAL':
            return { ...state, modal: null };

        case 'SHOW_TOAST':
            return { ...state, toast: { message: action.message, variant: action.variant ?? 'info' } };

        case 'CLEAR_TOAST':
            return { ...state, toast: null };

        default:
            return state;
    }
}

// ─── Context ─────────────────────────────────────────────────────────────────

const AppContext = createContext(null);

export function AppProvider({ children, context = 'mascot', authUser = null }) {
    const [state, dispatch] = useReducer(reducer, initialState);

    const setActiveJo = useCallback((uid) =>
        dispatch({ type: 'SET_ACTIVE_JO', uid }), []);

    const setActiveStage = useCallback((stage) =>
        dispatch({ type: 'SET_ACTIVE_STAGE', stage }), []);

    const setActiveTab = useCallback((tab) =>
        dispatch({ type: 'SET_ACTIVE_TAB', tab }), []);

    const openModal = useCallback((modalType, data = null) =>
        dispatch({ type: 'OPEN_MODAL', modalType, data }), []);

    const closeModal = useCallback(() =>
        dispatch({ type: 'CLOSE_MODAL' }), []);

    const showToast = useCallback((message, variant = 'info') => {
        dispatch({ type: 'SHOW_TOAST', message, variant });
        setTimeout(() => dispatch({ type: 'CLEAR_TOAST' }), 3500);
    }, []);

    return (
        <AppContext.Provider value={{
            ...state,
            context,
            authUser,
            setActiveJo,
            setActiveStage,
            setActiveTab,
            openModal,
            closeModal,
            showToast,
        }}>
            {children}
            {state.toast && <Toast message={state.toast.message} variant={state.toast.variant} />}
        </AppContext.Provider>
    );
}

// ─── Hook ─────────────────────────────────────────────────────────────────────

export function useApp() {
    const ctx = useContext(AppContext);
    if (!ctx) throw new Error('useApp must be used inside <AppProvider>');
    return ctx;
}

// ─── Toast (inline, no external dep) ─────────────────────────────────────────

const TOAST_STYLES = {
    success: { background: '#f0fdf4', border: '#22c55e', color: '#15803d' },
    error:   { background: '#fef2f2', border: '#ef4444', color: '#dc2626' },
    info:    { background: '#eff6ff', border: '#3b82f6', color: '#1d4ed8' },
};

function Toast({ message, variant }) {
    const s = TOAST_STYLES[variant] ?? TOAST_STYLES.info;
    return (
        <div style={{
            position: 'fixed', bottom: 24, right: 24, zIndex: 99999,
            background: s.background,
            border: `1px solid ${s.border}`,
            color: s.color,
            borderRadius: 10,
            padding: '10px 18px',
            fontSize: 13,
            fontWeight: 500,
            boxShadow: '0 4px 16px rgba(0,0,0,.1)',
            animation: 'fadeInUp .2s ease',
            maxWidth: 340,
        }}>
            {message}
        </div>
    );
}
