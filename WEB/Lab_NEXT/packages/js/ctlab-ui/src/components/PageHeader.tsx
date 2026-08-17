'use client';

import type { ReactNode } from 'react';

export interface PageHeaderProps {
    title: ReactNode;
    subtitle?: ReactNode;
    actions?: ReactNode;
}

export function PageHeader({ title, subtitle, actions }: PageHeaderProps) {
    return (
        <div className="nx-page-header">
            <div>
                <h1 className="nx-page-title">{title}</h1>
                {subtitle && <p className="nx-page-subtitle">{subtitle}</p>}
            </div>
            {actions && <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>{actions}</div>}
        </div>
    );
}
