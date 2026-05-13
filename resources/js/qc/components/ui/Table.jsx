import React from 'react';

export function Table({ children, style }) {
    return (
        <div style={{ width: '100%', overflowX: 'auto', borderRadius: 10, border: '1px solid #f1f5f9', ...style }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                {children}
            </table>
        </div>
    );
}

export function TableHeader({ children }) {
    return <thead style={{ background: '#f8fafc' }}>{children}</thead>;
}

export function TableBody({ children }) {
    return <tbody>{children}</tbody>;
}

export function TableRow({ children, style, onClick }) {
    return (
        <tr
            onClick={onClick}
            style={{
                borderBottom: '1px solid #f1f5f9',
                transition: onClick ? 'background .1s' : undefined,
                cursor: onClick ? 'pointer' : undefined,
                ...style,
            }}
        >
            {children}
        </tr>
    );
}

export function TableHead({ children, style }) {
    return (
        <th style={{
            padding: '10px 14px', textAlign: 'left',
            fontSize: 11, fontWeight: 600, color: '#94a3b8',
            textTransform: 'uppercase', letterSpacing: '.05em',
            whiteSpace: 'nowrap',
            ...style,
        }}>
            {children}
        </th>
    );
}

export function TableCell({ children, style }) {
    return (
        <td style={{ padding: '10px 14px', color: '#334155', verticalAlign: 'middle', ...style }}>
            {children}
        </td>
    );
}
