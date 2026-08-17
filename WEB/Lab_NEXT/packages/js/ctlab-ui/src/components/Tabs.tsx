'use client';

import { useState, type ReactNode } from 'react';

export interface TabsProps {
    tabs: { key: string; label: ReactNode; icon?: ReactNode }[];
    active?: string;
    defaultActive?: string;
    onChange?: (key: string) => void;
    className?: string;
}

export function Tabs({ tabs, active, defaultActive, onChange, className = '' }: TabsProps) {
    const [internal, setInternal] = useState(defaultActive ?? tabs[0]?.key);
    const current = active ?? internal;

    function select(key: string) {
        if (active === undefined) setInternal(key);
        onChange?.(key);
    }

    return (
        <div role="tablist" className={`nx-tabs ${className}`}>
            {tabs.map((tab) => (
                <button
                    key={tab.key}
                    type="button"
                    role="tab"
                    aria-selected={current === tab.key}
                    data-active={String(current === tab.key)}
                    className="nx-tab"
                    onClick={() => select(tab.key)}
                >
                    {tab.icon}
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
