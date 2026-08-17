'use client';

import type { CSSProperties, HTMLAttributes } from 'react';

export interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
    width?: string | number;
    height?: string | number;
    borderRadius?: string;
}

export function Skeleton({ width = '100%', height = '0.75rem', borderRadius, style, className = '', ...props }: SkeletonProps) {
    const merged: CSSProperties = {
        width,
        height,
        borderRadius: borderRadius ?? 'var(--ctlab-radius-md)',
        ...style,
    };
    return <div className={`nx-skeleton ${className}`} style={merged} aria-hidden="true" {...props} />;
}
