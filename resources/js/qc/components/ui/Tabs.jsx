import React, { createContext, useContext } from 'react';

const TabsCtx = createContext(null);

export function Tabs({ value, onValueChange, children, style }) {
    return (
        <TabsCtx.Provider value={{ value, onValueChange }}>
            <div style={style}>{children}</div>
        </TabsCtx.Provider>
    );
}

export function TabsList({ children, style }) {
    return (
        <div role="tablist" style={{
            display: 'flex', gap: 2,
            background: '#f1f5f9', borderRadius: 9, padding: 3,
            width: 'fit-content',
            ...style,
        }}>
            {children}
        </div>
    );
}

export function TabsTrigger({ value, children, style }) {
    const { value: active, onValueChange } = useContext(TabsCtx);
    const isActive = active === value;
    return (
        <button
            role="tab"
            aria-selected={isActive}
            onClick={() => onValueChange?.(value)}
            style={{
                padding: '6px 14px', fontSize: 13, fontWeight: 500,
                borderRadius: 7, border: 'none', cursor: 'pointer', outline: 'none',
                background: isActive ? '#fff' : 'transparent',
                color: isActive ? '#1e293b' : '#64748b',
                boxShadow: isActive ? '0 1px 2px rgba(0,0,0,.08)' : 'none',
                transition: 'all .15s',
                whiteSpace: 'nowrap',
                ...style,
            }}
        >
            {children}
        </button>
    );
}

export function TabsContent({ value, children, style }) {
    const { value: active } = useContext(TabsCtx);
    if (active !== value) return null;
    return <div role="tabpanel" style={style}>{children}</div>;
}
