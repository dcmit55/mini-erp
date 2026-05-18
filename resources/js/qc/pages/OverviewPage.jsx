import React from 'react';
import { useApp } from '../context/AppContext';
import MascotOverviewPage from './MascotOverviewPage';
import CostumeOverviewPage from './CostumeOverviewPage';

export default function OverviewPage() {
    const { context } = useApp();
    return context === 'costume'
        ? <CostumeOverviewPage />
        : <MascotOverviewPage />;
}
