import React from 'react';

export function Input({ style, ...rest }) {
    return (
        <input
            style={{
                width: '100%', boxSizing: 'border-box',
                border: '1px solid #e2e8f0', borderRadius: 8,
                padding: '8px 12px', fontSize: 13,
                background: '#fff', color: '#1e293b',
                outline: 'none',
                transition: 'border-color .15s',
                ...style,
            }}
            {...rest}
        />
    );
}
