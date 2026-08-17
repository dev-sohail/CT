'use client';

import type { HTMLAttributes, ReactNode } from 'react';

export interface CardProps extends Omit<HTMLAttributes<HTMLDivElement>, 'title'> {
    title?: ReactNode;
    subtitle?: ReactNode;
    footer?: ReactNode;
    interactive?: boolean;
}

export function Card({ title, subtitle, footer, interactive = false, children, className = '', ...props }: CardProps) {
    return (
        <div data-interactive={String(interactive)} className={`nx-card ${className}`} {...props}>
            {(title || subtitle) && (
                <div className="nx-card-header">
                    {title && <div className="nx-card-title">{title}</div>}
                    {subtitle && <div className="nx-card-subtitle">{subtitle}</div>}
                </div>
            )}
            <div className="nx-card-body">{children}</div>
            {footer && <div className="nx-card-footer">{footer}</div>}
        </div>
    );
}
