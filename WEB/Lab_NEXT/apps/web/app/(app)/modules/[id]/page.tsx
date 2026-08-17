import { roadmapGroups } from '@/components/roadmap-data';
import ModulePageClient from './ModulePageClient';

export function generateStaticParams() {
    return roadmapGroups.flatMap((group) => group.items.map((item) => ({ id: item.id })));
}

export default function ModuleDetailPage({ params }: { params: { id: string } }) {
    return <ModulePageClient moduleId={params.id} />;
}
