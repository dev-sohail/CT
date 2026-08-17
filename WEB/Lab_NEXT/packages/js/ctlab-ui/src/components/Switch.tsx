'use client';

import { useId, type ReactNode } from 'react';

export interface SwitchProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label?: ReactNode;
    disabled?: boolean;
    id?: string;
    className?: string;
}

export function Switch({ checked, onChange, label, disabled = false, id, className = '' }: SwitchProps) {
    const autoId = useId();
    const switchId = id ?? autoId;
    return (
        <div className={`nx-switch-field ${className}`}>
            <button
                type="button"
                role="switch"
                id={switchId}
                aria-checked={checked}
                aria-labelledby={label ? `${switchId}-label` : undefined}
                disabled={disabled}
                data-checked={String(checked)}
                className="nx-switch"
                onClick={() => onChange(!checked)}
            >
                <span className="nx-switch-thumb" />
            </button>
            {label && (
                <label id={`${switchId}-label`} htmlFor={switchId} className="nx-switch-label">
                    {label}
                </label>
            )}
        </div>
    );
}
