'use client';

import type { HTMLAttributes, ReactNode } from 'react';

export type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'primary';

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
    tone?: BadgeTone;
    icon?: ReactNode;
}

export function Badge({ tone = 'neutral', icon, children, className = '', ...props }: BadgeProps) {
    return (
        <span data-tone={tone} className={`nx-badge ${className}`} {...props}>
            {icon}
            {children}
        </span>
    );
}
