'use client';

export interface ProgressBarProps {
    value?: number;
    max?: number;
    indeterminate?: boolean;
    label?: string;
    className?: string;
}

export function ProgressBar({ value, max = 100, indeterminate = false, label, className = '' }: ProgressBarProps) {
    const pct = value === undefined ? 0 : Math.min(100, Math.max(0, (value / max) * 100));
    return (
        <div
            role="progressbar"
            aria-label={label}
            aria-valuenow={indeterminate ? undefined : Math.round(pct)}
            aria-valuemin={0}
            aria-valuemax={100}
            data-indeterminate={String(indeterminate)}
            className={`nx-progress ${className}`}
        >
            <div className="nx-progress-bar" style={indeterminate ? undefined : { width: `${pct}%` }} />
        </div>
    );
}
