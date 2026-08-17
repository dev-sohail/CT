'use client';

import Link from 'next/link';
import { CheckCircle2, Layers } from 'lucide-react';
import { PageHeader } from '@ctlab/ctlab-ui';
import { roadmapGroups } from '@/components/roadmap-data';

export default function ModulesPage() {
    const total = roadmapGroups.reduce((count, group) => count + group.items.length, 0);

    return (
        <div className="space-y-8">
            <PageHeader title="Modules" subtitle={`${total} implemented modules across the CTLabs ecosystem.`} />
            {roadmapGroups.map((group) => (
                <section key={group.id}>
                    <div className="flex items-center gap-2 mb-3 text-[var(--color-muted)]">
                        {group.icon}
                        <h2 className="text-sm font-semibold uppercase tracking-wider">{group.label}</h2>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        {group.items.map((item) => (
                            <Link key={item.id} href={`/modules/${item.id}`} className="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4 hover:border-[var(--color-accent)]/50 transition-colors">
                                <div className="flex items-start gap-3">
                                    <span className="text-[var(--color-ok)]">{item.icon}</span>
                                    <div className="min-w-0">
                                        <p className="font-medium truncate">#{item.id} {item.label}</p>
                                        <p className="mt-1 text-xs text-[var(--color-muted)] font-mono truncate">{item.domain}</p>
                                    </div>
                                    <CheckCircle2 size={15} className="ml-auto shrink-0 text-[var(--color-ok)]" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            ))}
        </div>
    );
}
