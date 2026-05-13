import { useState, useCallback } from 'react';

export function useLocalStorage(key, initialValue) {
    const [stored, setStored] = useState(() => {
        try {
            const item = window.localStorage.getItem(key);
            return item !== null ? JSON.parse(item) : initialValue;
        } catch {
            return initialValue;
        }
    });

    const setValue = useCallback((value) => {
        try {
            const v = value instanceof Function ? value(stored) : value;
            setStored(v);
            window.localStorage.setItem(key, JSON.stringify(v));
        } catch (e) {
            console.warn(`useLocalStorage: could not write "${key}"`, e);
        }
    }, [key, stored]);

    const remove = useCallback(() => {
        try {
            setStored(initialValue);
            window.localStorage.removeItem(key);
        } catch { /* noop */ }
    }, [key, initialValue]);

    return [stored, setValue, remove];
}
