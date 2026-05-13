import React from 'react';

export function Card({ children, style, ...rest }) {
    return (
        <div style={{
            background: '#fff', borderRadius: 12,
            border: '1px solid #f1f5f9',
            boxShadow: '0 1px 4px rgba(0,0,0,.06)',
            ...style,
        }} {...rest}>
            {children}
        </div>
    );
}

export function CardHeader({ children, style, ...rest }) {
    return (
        <div style={{
            padding: '14px 20px',
            borderBottom: '1px solid #f1f5f9',
            display: 'flex', alignItems: 'center', justifyContent: 'space-between',
            ...style,
        }} {...rest}>
            {children}
        </div>
    );
}

export function CardTitle({ children, style }) {
    return (
        <h3 style={{ margin: 0, fontSize: 15, fontWeight: 600, color: '#1e293b', ...style }}>
            {children}
        </h3>
    );
}

export function CardContent({ children, style, ...rest }) {
    return (
        <div style={{ padding: '16px 20px', ...style }} {...rest}>
            {children}
        </div>
    );
}

export function CardFooter({ children, style, ...rest }) {
    return (
        <div style={{
            padding: '12px 20px',
            borderTop: '1px solid #f1f5f9',
            display: 'flex', alignItems: 'center', gap: 8,
            ...style,
        }} {...rest}>
            {children}
        </div>
    );
}
