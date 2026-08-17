'use client';

import { forwardRef, useId, type InputHTMLAttributes, type ReactNode } from 'react';

export interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: ReactNode;
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
    { label, id, className = '', ...props },
    ref,
) {
    const autoId = useId();
    const inputId = id ?? autoId;
    return (
        <div className={`nx-checkbox-field ${className}`}>
            <input ref={ref} id={inputId} type="checkbox" className="nx-checkbox" {...props} />
            {label && (
                <label htmlFor={inputId} className="nx-checkbox-label">
                    {label}
                </label>
            )}
        </div>
    );
});
