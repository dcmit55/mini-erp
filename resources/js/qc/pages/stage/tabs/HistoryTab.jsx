import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { getStageHistory } from '../../../api/stageProduction';
import { STAGE_COLORS } from '../../../data/models';
import { Activity, CheckCircle2, XCircle, Wrench, Clock } from 'lucide-react';

const ICON_MAP = {
    'activity':    Activity,
    'check-circle': CheckCircle2,
    'x-circle':    XCircle,
    'wrench':      Wrench,
};

const TYPE_COLOR = {
    production: '#6366f1',
    pass:       '#22c55e',
    fail:       '#ef4444',
    rework:     '#f59e0b',
};

function TimelineEntry({ event }) {
    const Icon  = ICON_MAP[event.icon] ?? Activity;
    const color = TYPE_COLOR[event.type] ?? '#94a3b8';

    return (
        <div style={{ display: 'flex', gap: 14, position: 'relative' }}>
            {/* Icon + line */}
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', flexShrink: 0 }}>
                <div style={{ width: 34, height: 34, borderRadius: '50%', background: color + '18', border: `2px solid ${color}40`, display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1, flexShrink: 0 }}>
                    <Icon size={15} color={color} />
                </div>
                <div style={{ flex: 1, width: 2, background: '#f1f5f9', minHeight: 16 }} />
            </div>

            {/* Content */}
            <div style={{ paddingBottom: 18, flex: 1, minWidth: 0 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 8 }}>
                    <div style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>{event.title}</div>
                    <div style={{ fontSize: 11, color: '#94a3b8', whiteSpace: 'nowrap', flexShrink: 0 }}>
                        {event.ts ? new Date(event.ts).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'}
                    </div>
                </div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 2 }}>{event.detail}</div>
                {event.user && event.user !== '—' && (
                    <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 4 }}>by {event.user}</div>
                )}
            </div>
        </div>
    );
}

export default function HistoryTab({ projectUid, stage }) {
    const color = STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;

    const { data: events = [], isLoading } = useQuery({
        queryKey: ['stage-history', projectUid, stage],
        queryFn: () => getStageHistory(projectUid, stage),
        staleTime: 30_000,
    });

    if (isLoading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 160, color: '#94a3b8', gap: 8 }}>
            <div style={{ width: 16, height: 16, border: '2px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
            Loading history…
        </div>
    );

    if (events.length === 0) return (
        <div style={{ textAlign: 'center', padding: '60px 0', color: '#94a3b8', fontSize: 13 }}>
            <Clock size={32} color="#e2e8f0" style={{ marginBottom: 10, display: 'block', margin: '0 auto 10px' }} />
            No activity yet for this stage.
        </div>
    );

    return (
        <div style={{ display: 'flex', flexDirection: 'column', paddingTop: 4 }}>
            {events.map((ev, i) => <TimelineEntry key={i} event={ev} />)}
        </div>
    );
}
