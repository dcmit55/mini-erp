import React from 'react';

export function Progress({ value = 0, max = 100, color, height = 6, style }) {
    const pct = Math.min(100, Math.max(0, (value / max) * 100));

    // Colour ramp: green ≥ 80 %, yellow ≥ 40 %, red < 40 %
    const fill = color ?? (pct >= 80 ? '#22c55e' : pct >= 40 ? '#f59e0b' : '#ef4444');

    return (
        <div style={{
            width: '100%', height, background: '#f1f5f9',
            borderRadius: 999, overflow: 'hidden',
            ...style,
        }}>
            <div style={{
                width: `${pct}%`, height: '100%',
                background: fill, borderRadius: 999,
                transition: 'width .35s ease',
            }} />
        </div>
    );
}
