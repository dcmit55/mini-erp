import React, { useState } from 'react';

const PLACEMENTS = {
    top:    { bottom: 'calc(100% + 6px)', left: '50%', transform: 'translateX(-50%)' },
    bottom: { top:    'calc(100% + 6px)', left: '50%', transform: 'translateX(-50%)' },
    left:   { right:  'calc(100% + 6px)', top:  '50%', transform: 'translateY(-50%)' },
    right:  { left:   'calc(100% + 6px)', top:  '50%', transform: 'translateY(-50%)' },
};

export function Tooltip({ content, children, placement = 'top', disabled }) {
    const [visible, setVisible] = useState(false);

    if (!content || disabled) return <>{children}</>;

    return (
        <span
            style={{ position: 'relative', display: 'inline-flex' }}
            onMouseEnter={() => setVisible(true)}
            onMouseLeave={() => setVisible(false)}
        >
            {children}
            {visible && (
                <span style={{
                    position: 'absolute', ...PLACEMENTS[placement] ?? PLACEMENTS.top,
                    background: '#1e293b', color: '#f8fafc',
                    fontSize: 11, fontWeight: 500,
                    borderRadius: 6, padding: '4px 9px',
                    whiteSpace: 'nowrap', pointerEvents: 'none',
                    zIndex: 99999,
                    boxShadow: '0 2px 8px rgba(0,0,0,.2)',
                }}>
                    {content}
                </span>
            )}
        </span>
    );
}
