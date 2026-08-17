'use client';

import type { HTMLAttributes, ReactNode } from 'react';

export interface TagProps extends Omit<HTMLAttributes<HTMLSpanElement>, 'onRemove'> {
    tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'primary';
    icon?: ReactNode;
    onRemove?: () => void;
    removable?: boolean;
}

export function Tag({ tone = 'neutral', icon, onRemove, removable = Boolean(onRemove), children, className = '', ...props }: TagProps) {
    return (
        <span data-tone={tone} className={`nx-tag ${className}`} {...props}>
            {icon}
            {children}
            {removable && (
                <button
                    type="button"
                    className="nx-tag-remove"
                    aria-label="Remove tag"
                    onClick={(e) => {
                        e.stopPropagation();
                        onRemove?.();
                    }}
                >
                    ×
                </button>
            )}
        </span>
    );
}
