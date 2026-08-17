'use client';

import type { ReactNode } from 'react';

export interface EmptyStateProps {
    title?: string;
    description?: string;
    action?: ReactNode;
    icon?: ReactNode;
}

export function EmptyState({ title = 'Nothing here yet', description, action, icon }: EmptyStateProps) {
    return (
        <div className="nx-empty">
            {icon && <div style={{ fontSize: '1.5rem', opacity: 0.6 }}>{icon}</div>}
            <div style={{ fontWeight: 600, color: 'hsl(var(--ctlab-text))' }}>{title}</div>
            {description && <div style={{ fontSize: '0.8125rem' }}>{description}</div>}
            {action && <div style={{ marginTop: '0.5rem' }}>{action}</div>}
        </div>
    );
}
