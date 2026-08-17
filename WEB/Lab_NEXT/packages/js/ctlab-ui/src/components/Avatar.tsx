'use client';

import type { HTMLAttributes } from 'react';

export interface AvatarProps extends Omit<HTMLAttributes<HTMLSpanElement>, 'children'> {
    name: string;
    src?: string;
    size?: 'sm' | 'md' | 'lg';
}

export function Avatar({ name, src, size = 'md', className = '', ...props }: AvatarProps) {
    const initials = name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');

    return (
        <span data-size={size} className={`nx-avatar ${className}`} title={name} {...props}>
            {src ? <img src={src} alt={name} className="nx-avatar-img" /> : initials}
        </span>
    );
}
