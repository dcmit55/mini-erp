import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

const container = document.getElementById('qc-app');
if (container) {
    const context = container.dataset.context || 'mascot';
    const authUser = (() => {
        try { return JSON.parse(container.dataset.authUser || 'null'); }
        catch { return null; }
    })();
    createRoot(container).render(
        <React.StrictMode>
            <App context={context} authUser={authUser} />
        </React.StrictMode>
    );
}
