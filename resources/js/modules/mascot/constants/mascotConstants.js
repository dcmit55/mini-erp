export const OPERATORS = []; // populated at runtime from employees API
export const STORAGE_KEY = 'mascot_projects';

export const DEFECT_CATS = [
    'Structural', 'Stitching', 'Surface', 'Cutting',
    'Accessory', 'Material', 'Label', 'Dimension', 'Color/Print', 'Other',
];

export const DAILY_DEFECT_CATS = [
    'Structural', 'Stitching', 'Wrapping', 'Painting',
    'Assembly', 'Hardware', 'Finishing', 'Other',
];

export const GRADS = [
    'linear-gradient(135deg,#667eea,#764ba2)',
    'linear-gradient(135deg,#f093fb,#f5576c)',
    'linear-gradient(135deg,#4facfe,#00f2fe)',
    'linear-gradient(135deg,#43e97b,#38f9d7)',
    'linear-gradient(135deg,#fa709a,#fee140)',
    'linear-gradient(135deg,#a18cd1,#fbc2eb)',
    'linear-gradient(135deg,#fccb90,#d57eeb)',
    'linear-gradient(135deg,#fd7043,#ff8a65)',
];

export const SECTIONS = [
    { id: 1, title: 'Design & Visual Accuracy', icon: '👁', items: [
        { id: 1,  l: 'Proportions & Shape match approved design' },
        { id: 2,  l: 'Facial Features (Eyes, Nose, Mouth) Symmetrical' },
        { id: 3,  l: 'Fabric Color Consistency matches design' },
        { id: 4,  l: 'Logo: Position precise, size correct, not tilted' },
        { id: 5,  l: '360° Visual Inspection' },
    ]},
    { id: 2, title: 'Material Quality', icon: '🧱', items: [
        { id: 6,  l: 'Fabric: Clean, no stains / pulled threads' },
        { id: 7,  l: 'Foam/Structure: Stable, not too soft or hard' },
        { id: 8,  l: 'Internal Structure: Head frame strong & safe' },
    ]},
    { id: 3, title: 'Stitching Quality', icon: '✂️', items: [
        { id: 9,  l: 'Stitching neat, straight, no loose threads' },
        { id: 10, l: 'No open seams' },
        { id: 11, l: 'Stress Point Strength' },
        { id: 12, l: 'Head Joint Strength' },
        { id: 13, l: 'Standard: Double stitching & Hidden seam' },
    ]},
    { id: 4, title: 'Adhesive & Finishing Details', icon: '💧', items: [
        { id: 14, l: 'No visible glue overflow or yellowing' },
        { id: 15, l: 'Eyes, Eyebrows, Nose firmly attached' },
        { id: 16, l: 'Accessories & Ears permanently attached' },
    ]},
    { id: 5, title: 'Comfort & Wearability', icon: '🚶', items: [
        { id: 17, l: 'Adequate ventilation' },
        { id: 18, l: 'Performer visibility clear & wide' },
        { id: 19, l: 'Weight does not hinder movement' },
        { id: 20, l: 'Movement Test: Walking & Waving' },
        { id: 21, l: 'Movement Test: Sitting & Standing' },
    ]},
    { id: 6, title: 'Durability Test', icon: '🛡', items: [
        { id: 22, l: 'Light pull strength on arm areas' },
        { id: 23, l: 'All fabric joint strength' },
        { id: 24, l: 'Zipper / Velcro function smooth & strong' },
    ]},
    { id: 7, title: 'Cleanliness & Appearance', icon: '✨', items: [
        { id: 25, l: 'Free from dust & thread remnants' },
        { id: 26, l: 'Fabric fur neatly groomed' },
        { id: 27, l: 'No strong glue odor' },
    ]},
    { id: 8, title: 'Packaging Standards', icon: '📦', items: [
        { id: 28, l: 'Inner: Thick polybag + Silica gel' },
        { id: 29, l: 'Head: Bubble wrap protection' },
        { id: 30, l: 'Box: Thick corrugated & FRAGILE label' },
        { id: 31, l: 'Label: Product Name & Client visible' },
    ]},
    // id 9 = Packing List (handled separately)
    { id: 10, title: 'Final Approval', icon: '✅', items: [
        { id: 36, l: 'Visual, Material & Stitching APPROVED' },
        { id: 37, l: 'Comfort test PASSED' },
        { id: 38, l: 'Cleanliness & Packaging SECURE' },
        { id: 39, l: 'QC Supervisor Sign-off' },
    ]},
];

export const DAILY_SECTIONS = [
    { id: 'ds1', title: 'Struktur & Material', icon: '🧱', items: [
        { id: 'dp1', l: 'Rangka / struktur sesuai dimensi spec dan terasa kuat' },
        { id: 'dp2', l: 'Material struktur (PVC / foam / wire mesh) bebas dari cacat atau retak' },
        { id: 'dp3', l: 'Foam / padding terpasang merata dan simetris pada semua sisi' },
        { id: 'dp4', l: 'Sambungan antar komponen struktur kuat dan tidak ada yang longgar' },
    ]},
    { id: 'ds2', title: 'Wrapping & Surface', icon: '🔲', items: [
        { id: 'dp5', l: 'Kain wrapping tertempel merata, tidak ada gelembung udara atau lipatan' },
        { id: 'dp6', l: 'Jahitan pada kain wrapping rapi, kuat, dan tidak terlihat dari depan' },
        { id: 'dp7', l: 'Permukaan bebas dari noda, sobekan, atau cacat yang tampak' },
        { id: 'dp8', l: 'Warna dan tekstur kain wrapping sesuai referensi warna job order' },
    ]},
    { id: 'ds3', title: 'Painting & Airbrush', icon: '🎨', items: [
        { id: 'dp9',  l: 'Warna cat / airbrush sesuai referensi warna dan konsisten di seluruh area' },
        { id: 'dp10', l: 'Tidak ada tetesan cat, area tidak rata, atau kebocoran warna' },
        { id: 'dp11', l: 'Transisi warna (shading / blending) sesuai desain yang disetujui' },
        { id: 'dp12', l: 'Permukaan cat kering sempurna, tidak lengket atau masih basah' },
    ]},
    { id: 'ds4', title: 'Assembly & Komponen', icon: '🔧', items: [
        { id: 'dp13', l: 'Mata / aksesori terpasang aman, posisi simetris dan sesuai desain' },
        { id: 'dp14', l: 'Jahitan tangan rapi, tersembunyi, dan kuat di semua titik assembly' },
        { id: 'dp15', l: 'Semua bagian / komponen terhubung kuat — tidak ada yang longgar' },
        { id: 'dp16', l: 'Dimensi keseluruhan sesuai spec (tinggi, lebar, proporsi)' },
    ]},
];

export const DAILY_ITEMS = DAILY_SECTIONS.flatMap(s => s.items);

export const PM_REQ_STRICT = [
    'body mascot', 'body suit', 'body pad', 'shirt', 'cable',
    'charger', 'fan', 'battery', 'shoe', 'cover shoes', 'standy', 'handle',
];
export const PM_OPT_FLEX = ['pants', 'dress', 'harness', 'hands', 'remote', 'tail'];
export const PM_ITEMS = [...PM_REQ_STRICT, ...PM_OPT_FLEX];

export const PI_ITEMS = [
    'body suit', 'vest', 'body', 'magnetic expression', 'cover shoes',
    'battery', 'fan', 'charger', 'standy', 'handle',
];

export const DEFAULT_PARTS = [
    'head', 'body', 'hand', 'legs', 'wings', 'tail',
    'accessories', 'shoe', 'arm', 'gloves', 'shirt', 'pants', 'dress',
];

export const CHECKLIST_SECTION_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

export function buildCL() {
    const it = {};
    SECTIONS.forEach(s => s.items.forEach(i => {
        it[i.id] = { status: null, note: '', photos: [], history: [] };
    }));
    return it;
}

export function buildDP() {
    const r = {};
    DAILY_ITEMS.forEach(i => {
        r[i.id] = { status: null, note: '', photos: [], operators: [], operatorParts: {}, partResults: {}, history: [] };
    });
    return r;
}

export function buildPackingItems(type) {
    const items = type === 'Mascot' ? PM_ITEMS : PI_ITEMS;
    const obj = {};
    items.forEach(n => { obj[n] = { checked: false, photos: [] }; });
    return obj;
}

export function ensurePacking(p) {
    if (!p.packingItems) {
        const items = p.mascotType === 'Mascot' ? PM_ITEMS : PI_ITEMS;
        p.packingItems = {};
        items.forEach(n => { p.packingItems[n] = { checked: (p.packingList || []).includes(n), photos: [] }; });
    }
    const stdItems = p.mascotType === 'Mascot' ? PM_ITEMS : PI_ITEMS;
    stdItems.forEach(n => { if (!p.packingItems[n]) p.packingItems[n] = { checked: false, photos: [] }; });
    if (!p.packingCustom) p.packingCustom = [];
    if (!p.packingVerifyPhotos) p.packingVerifyPhotos = [];
    if (!p.customParts) p.customParts = [];
}

export function calcProg(p) {
    const it = p.checklistItems;
    let t = 0, f = 0;
    for (let i = 1; i <= 31; i++) { t++; if (it[i] && it[i].status !== null) f++; }
    for (let i = 36; i <= 39; i++) { t++; if (it[i] && it[i].status !== null) f++; }
    t++; if (p.packingVerified) f++;
    return t > 0 ? Math.round(f / t * 100) : 0;
}

export function mkProject(d) {
    const idx = Math.floor(Math.random() * GRADS.length);
    const type = d.mascotType || 'Mascot';
    return {
        id: Date.now() + '',
        jobNumber: d.jobNumber, projectName: d.projectName,
        supervisor: d.supervisor, inspectionDate: d.inspectionDate,
        deadline: d.deadline, mascotType: type,
        totalUnit: parseInt(d.totalUnit) || 1,
        status: 'WIP', progress: 0,
        coverImage: null, coverGradient: GRADS[idx],
        checklistItems: buildCL(),
        packingList: [], packingItems: buildPackingItems(type),
        packingCustom: [], packingVerifyPhotos: [], packingVerified: false,
        customParts: [], rejectLog: [], finalDecision: null, dailyProgress: {},
        createdAt: new Date().toISOString(),
    };
}

export function todayStr() { return new Date().toISOString().slice(0, 10); }

export function fmtDate(d) {
    if (!d) return '—';
    try { return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return d; }
}

export function fmtDt(d) {
    if (!d) return '—';
    try {
        const dt = new Date(d);
        return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' +
            dt.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    } catch { return d; }
}

// Unified reject log field accessors
export const rStatus = r => r.reworkStatus || r.status || 'OPEN';
export const rCat    = r => r.defectCategory || r.cat || 'Other';
export const rItem   = r => r.itemName || r.item || '—';
export const rNote   = r => r.failNote || r.note || '—';
export const rCreated = r => r.failDate || r.createdAt || '';
export const rAssigned = r => r.reworkAssignedTo || r.assignedTo || '—';
export const rSeverity = r => r.severity || 'Major';
