// ─── Constants ────────────────────────────────────────────────────────────────

export const STAGES = ['cutting', 'sewing', 'finishing'];

export const STAGE_LABELS = {
    cutting: 'Cutting',
    sewing: 'Sewing',
    finishing: 'Finishing',
};

export const STAGE_COLORS = {
    cutting: { bg: '#eff6ff', border: '#3b82f6', text: '#1d4ed8' },
    sewing: { bg: '#f0fdf4', border: '#22c55e', text: '#15803d' },
    finishing: { bg: '#fdf4ff', border: '#a855f7', text: '#7e22ce' },
};

export const TABS = ['dashboard', 'production', 'inspection', 'rework', 'gallery', 'history'];

export const TAB_LABELS = {
    dashboard: 'Dashboard',
    production: 'Production',
    inspection: 'Inspection',
    rework: 'Rework',
    gallery: 'Gallery',
    history: 'History',
};

export const SEVERITY_LEVELS = ['minor', 'major', 'critical'];

export const REJECT_STATUSES = [
    'open',
    'in_repair',
    're_inspection',
    'closed',
];

export const REJECT_STATUS_LABELS = {
    open: 'Open',
    in_repair: 'In Repair',
    re_inspection: 'Re-Inspection',
    closed: 'Closed',
};

export const REJECT_STATUS_COLORS = {
    open: { bg: '#fef2f2', text: '#dc2626' },
    in_repair: { bg: '#fff7ed', text: '#ea580c' },
    re_inspection: { bg: '#eff6ff', text: '#2563eb' },
    closed: { bg: '#f0fdf4', text: '#16a34a' },
};

export const DEFECT_CATEGORIES = [
    'Critical',
    'Minor',
    'Major',
];

// ─── Factory functions ────────────────────────────────────────────────────────

/**
 * @returns {import('./types').RejectLog}
 */
export function createRejectLog(overrides = {}) {
    return {
        id: null,
        uid: null,
        project_uid: null,
        stage: 'cutting',
        reject_code: '',          // e.g. REJ-001
        component: '',            // Component / Part
        defect_category: '',
        defect_description: '',
        severity: 'major',
        qty_reject: 0,
        photo_refs: [],
        root_cause: '',
        corrective_action: '',
        assigned_to: '',
        target_date: '',
        status: 'open',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
        ...overrides,
    };
}

/**
 * @returns {import('./types').DailyProgressRecord}
 */
export function createDailyProgressRecord(overrides = {}) {
    return {
        id: null,
        uid: null,
        project_uid: null,
        stage: 'cutting',
        date: new Date().toISOString().slice(0, 10),
        operator: '',
        part: '',
        qty_produced: 0,
        qty_target: 0,
        notes: '',
        status: 'pending',      // pending | inspected
        created_at: new Date().toISOString(),
        ...overrides,
    };
}

/**
 * @returns {import('./types').InspectionLog}
 */
export function createInspectionLog(overrides = {}) {
    return {
        id: null,
        uid: null,
        project_uid: null,
        stage: 'cutting',
        production_record_id: null,
        inspector: '',
        qty_pass: 0,
        qty_fail: 0,
        defect_category: '',
        defect_description: '',
        severity: 'minor',
        notes: '',
        photos: [],
        timestamp: new Date().toISOString(),
        ...overrides,
    };
}

/**
 * @returns {import('./types').GalleryItem}
 */
export function createGalleryItem(overrides = {}) {
    return {
        id: null,
        uid: null,
        project_uid: null,
        stage: 'cutting',
        photo_url: '',
        thumbnail_url: '',
        caption: '',
        uploaded_by: '',
        uploaded_at: new Date().toISOString(),
        tags: [],
        ...overrides,
    };
}

/**
 * @returns {import('./types').ActivityLog}
 */
export function createActivityLog(overrides = {}) {
    return {
        id: null,
        uid: null,
        project_uid: null,
        stage: null,
        action: '',           // e.g. 'created_reject_log', 'updated_status'
        description: '',
        user: '',
        metadata: {},
        timestamp: new Date().toISOString(),
        ...overrides,
    };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

export function isStageValid(stage) {
    return STAGES.includes(stage);
}

export function isTabValid(tab) {
    return TABS.includes(tab);
}

/** Derive a numeric stage progress from array of stage completion objects */
export function calcOverallProgress(stageCompletions = {}) {
    const scores = STAGES.map(s => stageCompletions[s] ?? 0);
    return Math.round(scores.reduce((a, b) => a + b, 0) / STAGES.length);
}
