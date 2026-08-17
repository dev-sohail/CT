'use client';

import { useId, type ReactNode } from 'react';

export type TooltipSide = 'top' | 'bottom' | 'left' | 'right';

export interface TooltipProps {
    content: ReactNode;
    side?: TooltipSide;
    children: ReactNode;
    className?: string;
}

export function Tooltip({ content, side = 'top', children, className = '' }: TooltipProps) {
    const id = useId();
    return (
        <span className={`nx-tooltip-wrap ${className}`}>
            <span aria-describedby={id}>{children}</span>
            <span role="tooltip" id={id} data-side={side} className="nx-tooltip">
                {content}
            </span>
        </span>
    );
}
