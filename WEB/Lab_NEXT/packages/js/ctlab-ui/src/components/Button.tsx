'use client';

import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { Spinner } from './Spinner';

export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost';
export type ButtonSize = 'sm' | 'md' | 'lg';

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    block?: boolean;
    loading?: boolean;
    icon?: ReactNode;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    { variant = 'primary', size = 'md', block = false, loading = false, icon, children, className = '', disabled, ...props },
    ref,
) {
    return (
        <button
            ref={ref}
            type="button"
            data-variant={variant}
            data-size={size}
            data-block={String(block)}
            className={`nx-btn ${className}`}
            disabled={disabled || loading}
            {...props}
        >
            {loading ? <Spinner /> : icon}
            {children}
        </button>
    );
});
