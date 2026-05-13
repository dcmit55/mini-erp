import React from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';

export function Dialog({ open, onClose, children, maxWidth = 500 }) {
    if (!open) return null;

    return createPortal(
        <div
            onClick={onClose}
            style={{
                position: 'fixed', inset: 0, zIndex: 9999,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                background: 'rgba(0,0,0,.4)', padding: 16,
            }}
        >
            <div
                onClick={e => e.stopPropagation()}
                style={{
                    background: '#fff', borderRadius: 16,
                    boxShadow: '0 20px 60px rgba(0,0,0,.18)',
                    width: '100%', maxWidth,
                    maxHeight: '90vh', overflowY: 'auto',
                }}
            >
                {children}
            </div>
        </div>,
        document.body
    );
}

export function DialogHeader({ children, onClose, style }) {
    return (
        <div style={{
            padding: '16px 20px', borderBottom: '1px solid #f1f5f9',
            display: 'flex', alignItems: 'center', justifyContent: 'space-between',
            ...style,
        }}>
            <div>{children}</div>
            {onClose && (
                <button
                    onClick={onClose}
                    style={{
                        background: 'none', border: 'none', cursor: 'pointer',
                        color: '#94a3b8', padding: 4, borderRadius: 6, outline: 'none',
                        display: 'flex', alignItems: 'center',
                    }}
                >
                    <X size={16} />
                </button>
            )}
        </div>
    );
}

export function DialogTitle({ children, style }) {
    return (
        <h3 style={{ margin: 0, fontSize: 15, fontWeight: 600, color: '#1e293b', ...style }}>
            {children}
        </h3>
    );
}

export function DialogContent({ children, style }) {
    return (
        <div style={{ padding: '16px 20px', display: 'flex', flexDirection: 'column', gap: 14, ...style }}>
            {children}
        </div>
    );
}

export function DialogFooter({ children, style }) {
    return (
        <div style={{
            padding: '12px 20px', borderTop: '1px solid #f1f5f9',
            display: 'flex', gap: 8, justifyContent: 'flex-end',
            ...style,
        }}>
            {children}
        </div>
    );
}
