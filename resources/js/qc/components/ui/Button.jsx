import React from 'react';

const VARIANTS = {
    default:     { bg: '#6366f1', text: '#fff',     border: 'transparent' },
    secondary:   { bg: '#f1f5f9', text: '#475569',  border: 'transparent' },
    ghost:       { bg: 'transparent', text: '#475569', border: 'transparent' },
    destructive: { bg: '#ef4444', text: '#fff',     border: 'transparent' },
    outline:     { bg: '#fff',    text: '#1e293b',  border: '#e2e8f0'     },
};

const SIZES = {
    sm:   { padding: '5px 11px',  fontSize: 12, borderRadius: 6, gap: 4 },
    md:   { padding: '8px 16px',  fontSize: 13, borderRadius: 8, gap: 6 },
    lg:   { padding: '10px 20px', fontSize: 14, borderRadius: 9, gap: 7 },
    icon: { padding: '7px',       fontSize: 13, borderRadius: 8, gap: 0 },
};

export function Button({
    variant  = 'default',
    size     = 'md',
    disabled = false,
    children,
    style,
    onClick,
    type    = 'button',
    ...rest
}) {
    const v = VARIANTS[variant] ?? VARIANTS.default;
    const s = SIZES[size]     ?? SIZES.md;

    return (
        <button
            type={type}
            disabled={disabled}
            onClick={onClick}
            style={{
                padding: s.padding, fontSize: s.fontSize, borderRadius: s.borderRadius,
                gap: s.gap,
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                fontWeight: 500, lineHeight: 'normal',
                background: disabled ? '#e2e8f0' : v.bg,
                color:      disabled ? '#94a3b8' : v.text,
                border:     `1px solid ${v.border}`,
                cursor:  disabled ? 'not-allowed' : 'pointer',
                outline: 'none',
                opacity: disabled ? .65 : 1,
                transition: 'opacity .15s, background .15s',
                whiteSpace: 'nowrap',
                ...style,
            }}
            {...rest}
        >
            {children}
        </button>
    );
}
