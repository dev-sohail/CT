'use client';

import { useEffect, useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import { LogOut, Moon, Search, Sun, X } from 'lucide-react';
import { useTheme } from '@ctlab/ctlab-theme';
import { roadmapGroups } from './roadmap-data';

const allModules = roadmapGroups.flatMap((g) =>
    g.items.map((item) => ({ id: item.id, label: item.label, group: g.label, icon: item.icon }))
);

export default function Topbar() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<typeof allModules>([]);
    const [open, setOpen] = useState(false);
    const [selectedIdx, setSelectedIdx] = useState(-1);
    const inputRef = useRef<HTMLInputElement>(null);
    const boxRef = useRef<HTMLDivElement>(null);
    const router = useRouter();
    const { mode, toggle } = useTheme();

    useEffect(() => {
        const q = query.trim().toLowerCase();
        if (q.length < 2) { setResults([]); setOpen(false); setSelectedIdx(-1); return; }
        const matches = allModules.filter(
            (m) => m.label.toLowerCase().includes(q) || m.group.toLowerCase().includes(q)
        ).slice(0, 20);
        setResults(matches);
        setOpen(matches.length > 0);
        setSelectedIdx(-1);
    }, [query]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (boxRef.current && !boxRef.current.contains(e.target as Node)) setOpen(false);
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function navigate(id: string) {
        setQuery('');
        setOpen(false);
        router.push(`/modules/${id}`);
    }

    function handleKeyDown(e: React.KeyboardEvent) {
        if (!open || results.length === 0) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setSelectedIdx((i) => (i + 1) % results.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSelectedIdx((i) => (i - 1 + results.length) % results.length);
        } else if (e.key === 'Enter' && selectedIdx >= 0) {
            e.preventDefault();
            navigate(results[selectedIdx].id);
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    }

    return (
        <header className="h-14 shrink-0 border-b border-[var(--color-border)] flex items-center justify-between px-6 gap-4">
            <div className="flex-1 max-w-md relative" ref={boxRef}>
                <form onSubmit={(e) => { e.preventDefault(); if (selectedIdx >= 0) navigate(results[selectedIdx].id); else if (results.length > 0) navigate(results[0].id); }}>
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 flex text-[var(--color-text-faint)] pointer-events-none" aria-hidden="true">
                        <Search size={14} />
                    </span>
                    <input
                        ref={inputRef}
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onKeyDown={handleKeyDown}
                        onFocus={() => { if (results.length > 0) setOpen(true); }}
                        placeholder="Search modules..."
                        className="w-full rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] pl-9 pr-8 py-1.5 text-sm text-[var(--color-text)] placeholder:text-[var(--color-muted)]/60 focus:border-[var(--color-accent)] outline-none"
                    />
                    {query && (
                        <button type="button" onClick={() => { setQuery(''); setOpen(false); inputRef.current?.focus(); }} className="absolute right-2 top-1/2 -translate-y-1/2 text-[var(--color-muted)] hover:text-[var(--color-text)] cursor-pointer">
                            <X size={14} />
                        </button>
                    )}
                </form>
                {open && results.length > 0 && (
                    <div className="absolute z-50 mt-1 w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] shadow-lg max-h-80 overflow-y-auto">
                        {results.map((m, i) => (
                            <button
                                key={m.id}
                                type="button"
                                onClick={() => navigate(m.id)}
                                className={`flex items-center gap-3 w-full text-left px-4 py-2.5 text-sm transition-colors cursor-pointer ${
                                    i === selectedIdx
                                        ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-hover)]'
                                        : 'text-[var(--color-text)] hover:bg-[var(--color-surface-2)]'
                                }`}
                            >
                                <span className="shrink-0 text-[var(--color-muted)]">{m.icon}</span>
                                <div className="min-w-0">
                                    <div className="truncate font-medium">{m.label}</div>
                                    <div className="truncate text-xs text-[var(--color-muted)]">{m.group}</div>
                                </div>
                                <span className="ml-auto text-xs text-[var(--color-muted)]">#{m.id}</span>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <div className="flex items-center gap-1">
                <button
                    type="button"
                    onClick={toggle}
                    aria-label={`Switch to ${mode === 'dark' ? 'light' : 'dark'} mode`}
                    className="flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-muted)] hover:bg-[var(--color-surface-2)] hover:text-[var(--color-text)] cursor-pointer"
                >
                    {mode === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
                </button>
                <button
                    type="button"
                    onClick={() => {
                        localStorage.removeItem('ctlab_token');
                        window.location.replace('/login');
                    }}
                    aria-label="Sign out"
                    className="flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-muted)] hover:bg-[var(--color-surface-2)] hover:text-[var(--color-bad)] cursor-pointer"
                >
                    <LogOut size={18} />
                </button>
            </div>
        </header>
    );
}
