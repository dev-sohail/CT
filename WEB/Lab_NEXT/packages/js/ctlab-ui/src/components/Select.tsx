'use client';

import { forwardRef, useId, type SelectHTMLAttributes, type ReactNode } from 'react';

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    hint?: string;
    error?: string;
    invalid?: boolean;
    options?: { value: string; label: string }[];
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
    { label, hint, error, invalid, options, children, id, className = '', ...props },
    ref,
) {
    const autoId = useId();
    const selectId = id ?? autoId;
    return (
        <div className="nx-field">
            {label && (
                <label htmlFor={selectId} className="nx-label">
                    {label}
                </label>
            )}
            <div className="nx-select-wrap">
                <select
                    ref={ref}
                    id={selectId}
                    data-invalid={String(Boolean(invalid || error))}
                    aria-invalid={Boolean(invalid || error)}
                    aria-describedby={hint || error ? `${selectId}-hint` : undefined}
                    className={`nx-select ${className}`}
                    {...props}
                >
                    {options ? options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>) : children}
                </select>
                <span className="nx-select-chevron" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                </span>
            </div>
            {hint && !error && (
                <p id={`${selectId}-hint`} className="nx-hint">
                    {hint}
                </p>
            )}
            {error && (
                <p id={`${selectId}-hint`} className="nx-error">
                    {error}
                </p>
            )}
        </div>
    );
});
