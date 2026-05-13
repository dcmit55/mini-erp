import React from 'react';

/**
 * Lightweight native-select wrapper with Shadcn-style API.
 * For a full custom dropdown, swap the implementation without changing call sites.
 */
export function Select({ children, style, ...rest }) {
    return (
        <select
            style={{
                width: '100%', boxSizing: 'border-box',
                border: '1px solid #e2e8f0', borderRadius: 8,
                padding: '8px 12px', fontSize: 13,
                background: '#fff', color: '#1e293b',
                outline: 'none', cursor: 'pointer',
                transition: 'border-color .15s',
                ...style,
            }}
            {...rest}
        >
            {children}
        </select>
    );
}

export function SelectItem({ value, children, disabled }) {
    return (
        <option value={value} disabled={disabled}>
            {children}
        </option>
    );
}

export function SelectPlaceholder({ children = '— Select —' }) {
    return <option value="">{children}</option>;
}
