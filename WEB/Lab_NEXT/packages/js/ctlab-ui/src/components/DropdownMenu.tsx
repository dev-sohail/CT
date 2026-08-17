'use client';

import { useEffect, useRef, useState, type ReactNode } from 'react';

export interface DropdownMenuProps {
    trigger: ReactNode;
    items: {
        key: string;
        label: ReactNode;
        icon?: ReactNode;
        danger?: boolean;
        disabled?: boolean;
        onSelect?: () => void;
    }[];
    align?: 'start' | 'end';
    className?: string;
}

export function DropdownMenu({ trigger, items, align = 'start', className = '' }: DropdownMenuProps) {
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        function onDocClick(event: MouseEvent) {
            if (rootRef.current && !rootRef.current.contains(event.target as Node)) setOpen(false);
        }
        function onKey(event: KeyboardEvent) {
            if (event.key === 'Escape') setOpen(false);
        }
        document.addEventListener('mousedown', onDocClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDocClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    return (
        <div ref={rootRef} className={`nx-dropdown ${className}`}>
            <button type="button" aria-haspopup="menu" aria-expanded={open} className="nx-dropdown-trigger" onClick={() => setOpen((v) => !v)}>
                {trigger}
            </button>
            {open && (
                <div role="menu" data-align={align} className="nx-dropdown-menu">
                    {items.map((item) => (
                        <button
                            key={item.key}
                            type="button"
                            role="menuitem"
                            data-danger={String(Boolean(item.danger))}
                            disabled={item.disabled}
                            className="nx-dropdown-item"
                            onClick={() => {
                                setOpen(false);
                                item.onSelect?.();
                            }}
                        >
                            {item.icon}
                            {item.label}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
