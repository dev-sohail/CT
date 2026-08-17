'use client';

import Link from 'next/link';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';
import { PageHeader, EmptyState } from '@ctlab/ctlab-ui';
import { roadmapGroups } from '@/components/roadmap-data';
import { RecordCrudPage } from '@/components/modules/RecordCrudPage';
import { moduleRegistry } from '@/components/modules/module-registry';
import WikiModulePage from '@/components/modules/WikiModulePage';

export default function ModulePageClient({ moduleId }: { moduleId: string }) {
    const roadmapItem = roadmapGroups.flatMap((g) => g.items).find((i) => i.id === moduleId);
    const config = moduleRegistry[moduleId];

    if (!roadmapItem) {
        return (
            <div className="space-y-6">
                <Link href="/modules" className="inline-flex items-center gap-2 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)]">
                    <ArrowLeft size={15} /> All modules
                </Link>
                <EmptyState description={`Module #${moduleId} not found.`} />
            </div>
        );
    }

    if (moduleId === '26') {
        return (
            <div className="space-y-6">
                <Link href="/modules" className="inline-flex items-center gap-2 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)]">
                    <ArrowLeft size={15} /> All modules
                </Link>
                <WikiModulePage />
            </div>
        );
    }

    if (!config) {
        return (
            <div className="space-y-6">
                <Link href="/modules" className="inline-flex items-center gap-2 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)]">
                    <ArrowLeft size={15} /> All modules
                </Link>
                <PageHeader title={`#${roadmapItem.id} ${roadmapItem.label}`} subtitle={roadmapItem.domain} />
                <div className="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-6">
                    <div className="flex items-center gap-3 text-[var(--color-ok)]">
                        <CheckCircle2 size={20} />
                        <span className="font-medium">Implemented and available through the CTLabs API</span>
                    </div>
                    <p className="mt-4 text-sm text-[var(--color-muted)]">
                        This module has backend endpoints but the interactive UI has not been configured yet.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <Link href="/modules" className="inline-flex items-center gap-2 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)]">
                <ArrowLeft size={15} /> All modules
            </Link>
            <RecordCrudPage
                title={config.title}
                subtitle={config.subtitle}
                basePath={config.basePath}
                fields={config.fields ?? []}
                tableColumns={config.tableColumns}
                stats={config.stats}
                actions={config.actions}
                nameKey={config.nameKey}
                statsOnly={config.statsOnly}
                statsEndpoints={config.statsEndpoints}
            />
        </div>
    );
}
