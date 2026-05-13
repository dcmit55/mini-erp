import React from 'react';
import { useOutletContext, useParams } from 'react-router-dom';
import { STAGE_COLORS } from '../../data/models';
import { PlaceholderTab } from '../StagePage';

import DashboardTab           from './tabs/DashboardTab';
import ProductionTab          from './tabs/ProductionTab';
import FinishingProductionTab from './tabs/FinishingProductionTab';
import InspectionTab          from './tabs/InspectionTab';
import ReworkTab              from './tabs/ReworkTab';
import GalleryTab             from './tabs/GalleryTab';
import HistoryTab             from './tabs/HistoryTab';

export default function StageTabOutlet() {
    const ctx    = useOutletContext() ?? {};
    const params = useParams();

    const stage      = ctx.stage   ?? params.stage ?? 'cutting';
    const tab        = ctx.tab     ?? params.tab   ?? 'dashboard';
    const project    = ctx.project ?? null;
    const color      = ctx.color   ?? STAGE_COLORS[stage] ?? STAGE_COLORS.cutting;
    const projectUid = project?.uid ?? params.joUID;

    const props = { projectUid, stage, project, color };

    switch (tab) {
        case 'dashboard':  return <DashboardTab  {...props} />;
        case 'production':
            return stage === 'finishing'
                ? <FinishingProductionTab projectUid={projectUid} />
                : <ProductionTab {...props} />;
        case 'inspection': return <InspectionTab {...props} />;
        case 'rework':     return <ReworkTab     {...props} />;
        case 'gallery':    return <GalleryTab    {...props} />;
        case 'history':    return <HistoryTab    {...props} />;
        default:           return <PlaceholderTab stage={stage} tab={tab} color={color} />;
    }
}
