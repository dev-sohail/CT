'use client';

import type { HTMLAttributes } from 'react';

export interface SpinnerProps extends HTMLAttributes<HTMLSpanElement> {
    size?: number;
}

export function Spinner({ size = 18, style, className = '', ...props }: SpinnerProps) {
    return (
        <span
            role="status"
            aria-label="Loading"
            className={`nx-spinner ${className}`}
            style={{ width: size, height: size, ...style }}
            {...props}
        />
    );
}
