'use client';

import { forwardRef, useId, type InputHTMLAttributes, type ReactNode } from 'react';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    hint?: string;
    error?: string;
    invalid?: boolean;
    icon?: ReactNode;
    right?: ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
    { label, hint, error, invalid, icon, right, id, className = '', ...props },
    ref,
) {
    const autoId = useId();
    const inputId = id ?? autoId;
    return (
        <div className="nx-field">
            {label && (
                <label htmlFor={inputId} className="nx-label">
                    {label}
                </label>
            )}
            <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                {icon && (
                    <span
                        style={{
                            position: 'absolute',
                            left: '0.75rem',
                            display: 'inline-flex',
                            color: 'hsl(var(--ctlab-text-faint))',
                            pointerEvents: 'none',
                        }}
                    >
                        {icon}
                    </span>
                )}
                <input
                    ref={ref}
                    id={inputId}
                    data-invalid={String(Boolean(invalid || error))}
                    className={`nx-input ${icon ? 'nx-input-with-icon' : ''} ${className}`}
                    aria-invalid={Boolean(invalid || error)}
                    aria-describedby={hint || error ? `${inputId}-hint` : undefined}
                    style={icon ? { paddingLeft: '2.25rem' } : undefined}
                    {...props}
                />
                {right && <span style={{ position: 'absolute', right: '0.75rem' }}>{right}</span>}
            </div>
            {hint && !error && (
                <p id={`${inputId}-hint`} className="nx-hint">
                    {hint}
                </p>
            )}
            {error && (
                <p id={`${inputId}-hint`} className="nx-error">
                    {error}
                </p>
            )}
        </div>
    );
});
