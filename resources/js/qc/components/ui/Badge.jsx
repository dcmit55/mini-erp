import React from 'react';

const VARIANTS = {
    default:     { bg: '#6366f1', text: '#fff',     border: 'none' },
    secondary:   { bg: '#f1f5f9', text: '#475569',  border: 'none' },
    success:     { bg: '#dcfce7', text: '#15803d',  border: 'none' },
    warning:     { bg: '#fef9c3', text: '#854d0e',  border: 'none' },
    destructive: { bg: '#fee2e2', text: '#dc2626',  border: 'none' },
    outline:     { bg: 'transparent', text: '#475569', border: '1px solid #e2e8f0' },
    info:        { bg: '#eff6ff', text: '#1d4ed8',  border: 'none' },
};

export function Badge({ variant = 'default', children, style, ...rest }) {
    const v = VARIANTS[variant] ?? VARIANTS.default;
    return (
        <span style={{
            display: 'inline-flex', alignItems: 'center',
            padding: '2px 8px', borderRadius: 999,
            fontSize: 11, fontWeight: 600,
            background: v.bg, color: v.text, border: v.border,
            whiteSpace: 'nowrap',
            ...style,
        }} {...rest}>
            {children}
        </span>
    );
}
