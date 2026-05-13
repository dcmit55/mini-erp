import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updatePackingItem, addCustomPackingItem, deletePackingItem, verifyPacking } from '../../api/packing';
import { CheckSquare, Square, Plus, Trash2, EyeOff, ShieldCheck } from 'lucide-react';

const btnStyle = (bg, color = '#fff') => ({
    padding: '6px 12px', borderRadius: 8, border: 'none', cursor: 'pointer',
    background: bg, color, fontSize: 12, fontWeight: 500, outline: 'none',
    display: 'flex', alignItems: 'center', gap: 5, transition: 'opacity .15s',
});

function PackingItemRow({ item, projectUid }) {
    const qc = useQueryClient();

    const toggleMut = useMutation({
        mutationFn: (val) => updatePackingItem(projectUid, item.uid, { is_checked: val }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['project', projectUid] }),
    });

    const removeMut = useMutation({
        mutationFn: () => deletePackingItem(projectUid, item.uid),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['project', projectUid] }),
    });

    if (item.is_hidden) return null;

    return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 10px', borderRadius: 8, background: item.is_checked ? '#f0fdf4' : '#fff', transition: 'background .15s' }}>
            <button onClick={() => toggleMut.mutate(!item.is_checked)} disabled={toggleMut.isPending}
                style={{ display: 'flex', alignItems: 'center', gap: 8, flex: 1, background: 'none', border: 'none', cursor: 'pointer', outline: 'none', padding: 0, textAlign: 'left' }}>
                {item.is_checked
                    ? <CheckSquare size={16} style={{ color: '#22c55e', flexShrink: 0 }} />
                    : <Square size={16} style={{ color: '#cbd5e1', flexShrink: 0 }} />}
                <span style={{ fontSize: 13, color: item.is_checked ? '#64748b' : '#334155', textDecoration: item.is_checked ? 'line-through' : 'none' }}>
                    {item.name}
                </span>
                {item.type !== 'required' && (
                    <span style={{ fontSize: 11, color: item.type === 'custom' ? '#6366f1' : '#94a3b8' }}>
                        ({item.type})
                    </span>
                )}
            </button>
            <button onClick={() => removeMut.mutate()} disabled={removeMut.isPending}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#e2e8f0', padding: 4, borderRadius: 6, outline: 'none', display: 'flex', alignItems: 'center', marginLeft: 4 }}
                onMouseEnter={e => e.currentTarget.style.color = '#ef4444'}
                onMouseLeave={e => e.currentTarget.style.color = '#e2e8f0'}>
                {item.type === 'custom' ? <Trash2 size={13} /> : <EyeOff size={13} />}
            </button>
        </div>
    );
}

export default function PackingTab({ project }) {
    const qc = useQueryClient();
    const [newName, setNewName] = useState('');

    const items = project.packing_items ?? [];
    const checkedCount = items.filter(i => !i.is_hidden && i.is_checked).length;
    const totalVisible = items.filter(i => !i.is_hidden).length;
    const progress = totalVisible > 0 ? Math.round((checkedCount / totalVisible) * 100) : 0;

    const addMut = useMutation({
        mutationFn: () => addCustomPackingItem(project.uid, { name: newName.trim() }),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['project', project.uid] }); setNewName(''); },
    });

    const verifyMut = useMutation({
        mutationFn: (v) => verifyPacking(project.uid, v),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['project', project.uid] }),
    });

    const groups = ['required', 'optional', 'custom'].filter(t => items.some(i => i.type === t && !i.is_hidden));

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            {/* Header */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <span style={{ fontSize: 12, color: '#94a3b8' }}>{checkedCount} / {totalVisible} packed</span>
                {project.packing_verified
                    ? <span style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12, color: '#16a34a', fontWeight: 600, background: '#f0fdf4', padding: '4px 10px', borderRadius: 999 }}>
                        <ShieldCheck size={13} /> Verified
                      </span>
                    : <button onClick={() => verifyMut.mutate(true)}
                        disabled={verifyMut.isPending || checkedCount < totalVisible}
                        style={{ ...btnStyle('#6366f1'), opacity: (verifyMut.isPending || checkedCount < totalVisible) ? 0.4 : 1 }}>
                        <ShieldCheck size={13} /> Verify Packing
                      </button>
                }
            </div>

            {/* Progress bar */}
            <div style={{ height: 5, background: '#e2e8f0', borderRadius: 999 }}>
                <div style={{ width: `${progress}%`, height: '100%', background: '#6366f1', borderRadius: 999, transition: 'width .3s' }} />
            </div>

            {/* Items by group */}
            {groups.map(type => (
                <div key={type}>
                    <div style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 6 }}>
                        {type}
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                        {items.filter(i => i.type === type).map(item => (
                            <PackingItemRow key={item.uid} item={item} projectUid={project.uid} />
                        ))}
                    </div>
                </div>
            ))}

            {/* Add custom */}
            <div style={{ display: 'flex', gap: 8, paddingTop: 8, borderTop: '1px solid #f1f5f9' }}>
                <input type="text" placeholder="Add custom item…"
                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '8px 12px', fontSize: 13, outline: 'none', background: '#fff' }}
                    value={newName} onChange={e => setNewName(e.target.value)}
                    onKeyDown={e => { if (e.key === 'Enter' && newName.trim()) addMut.mutate(); }} />
                <button onClick={() => addMut.mutate()} disabled={!newName.trim() || addMut.isPending}
                    style={{ ...btnStyle('#6366f1'), opacity: (!newName.trim() || addMut.isPending) ? 0.4 : 1 }}>
                    <Plus size={16} />
                </button>
            </div>
        </div>
    );
}
