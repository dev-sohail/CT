'use client';

import { useRef, useState, useEffect } from 'react';
import type { Block } from './types';
import { highlight } from './highlight';

export interface BlockCallbacks {
    onUpdate: (updates: Partial<Block>) => void;
    onRemove: () => void;
    onMoveUp: () => void;
    onMoveDown: () => void;
    onFocus: () => void;
    onSlash: (position: { top: number; left: number }) => void;
    onSlashInput: (query: string) => void;
    onSlashClose: () => void;
    onConvert: (spec: { type: string; content?: string; meta?: Record<string, any> }) => void;
    onEnter: () => void;
    onBackspace: () => void;
}

export interface BlockViewProps {
    block: Block;
    readMode: boolean;
    pageIndex: Record<string, number>;
    cb: BlockCallbacks;
}

const inputCls =
    'w-full bg-transparent text-[var(--color-text)] placeholder:text-[var(--color-muted)]/50 outline-none font-inherit resize-y';
const selectCls =
    'px-2 py-1 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-xs cursor-pointer text-[var(--color-text)]';
const ghostBtn =
    'px-2 py-1 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-xs hover:bg-[var(--color-surface)] cursor-pointer';

function parseItems(raw: string | null | undefined): { text: string; checked: boolean }[] {
    if (!raw) return [{ text: '', checked: false }];
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            return parsed.map((it: any) => ({ text: String(it.text ?? ''), checked: !!it.checked }));
        }
    } catch {
        return raw
            .split('\n')
            .filter(Boolean)
            .map((text) => ({ text, checked: false }));
    }
    return [{ text: '', checked: false }];
}

function detectMarkdownShortcut(value: string): { type: string; content?: string; meta?: Record<string, any> } | null {
    if (value === '') return null;
    let match = value.match(/^(#{1,6})\s$/);
    if (match) return { type: 'heading', content: '', meta: { level: Math.min(match[1].length, 3) } };
    match = value.match(/^([-*])\s$/);
    if (match) return { type: 'list', content: '[]', meta: { bullet: true } };
    match = value.match(/^\[\s*\]\s$/);
    if (match) return { type: 'checklist', content: '[]', meta: {} };
    match = value.match(/^>\s$/);
    if (match) return { type: 'quote', content: '', meta: {} };
    match = value.match(/^```\s*$/);
    if (match) return { type: 'code', content: '', meta: { language: 'javascript' } };
    match = value.match(/^---\s*$/);
    if (match) return { type: 'divider', content: '', meta: {} };
    return null;
}

export function BlockText({ block, readMode, pageIndex, cb }: BlockViewProps) {
    const slashTyped = useRef(false);
    const debounce = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => () => { if (debounce.current) clearTimeout(debounce.current); }, []);

    function onInput(e: React.FormEvent<HTMLTextAreaElement>) {
        const value = (e.target as HTMLTextAreaElement).value;
        if (slashTyped.current && value.startsWith('/')) {
            cb.onSlashInput(value.substring(1));
            return;
        } else if (slashTyped.current) {
            slashTyped.current = false;
            cb.onSlashClose();
        }
        const conversion = detectMarkdownShortcut(value);
        if (conversion) {
            cb.onConvert(conversion);
            return;
        }
        if (debounce.current) clearTimeout(debounce.current);
        debounce.current = setTimeout(() => cb.onUpdate({ content: value }), 800);
    }

    function onKeydown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === '/') {
            slashTyped.current = true;
            const rect = (e.target as HTMLTextAreaElement).getBoundingClientRect();
            cb.onSlash({ top: rect.bottom + 4, left: Math.min(rect.left, window.innerWidth - 260) });
        } else if (e.key === 'Enter' && !e.shiftKey) {
            const value = (e.target as HTMLTextAreaElement).value;
            if (value.match(/^```\s*$/) || value.match(/^---\s*$/)) {
                e.preventDefault();
                cb.onConvert(value.match(/^```/)
                    ? { type: 'code', content: '', meta: { language: 'javascript' } }
                    : { type: 'divider', content: '', meta: {} });
                return;
            }
            e.preventDefault();
            cb.onEnter();
        } else if (e.key === 'Backspace') {
            if (slashTyped.current) {
                slashTyped.current = false;
                cb.onSlashClose();
            }
            if (!(e.target as HTMLTextAreaElement).value) {
                e.preventDefault();
                cb.onBackspace();
            }
        }
    }

    function onBlur() {
        if (slashTyped.current) {
            slashTyped.current = false;
            cb.onSlashClose();
        }
    }

    if (!readMode) {
        return (
            <textarea
                defaultValue={block.content ?? ''}
                placeholder="Type '/' for commands, '# ' for heading..."
                spellCheck={false}
                className={`${inputCls} min-h-[2rem] text-base leading-7`}
                onInput={onInput}
                onKeyDown={onKeydown}
                onFocus={cb.onFocus}
                onBlur={onBlur}
            />
        );
    }

    const parts = (block.content ?? '').split(/(\[\[.+?\]\])/g).filter(Boolean);
    return (
        <div className="py-1 text-base leading-7 whitespace-pre-wrap">
            {parts.length ? (
                parts.map((part, idx) => {
                    const m = part.match(/^\[\[(.+?)\]\]$/);
                    if (m) {
                        const target = m[1].trim();
                        const id = pageIndex[target.toLowerCase()] ?? null;
                        return (
                            <a
                                key={idx}
                                href={id ? `/wiki/pages?id=${id}` : undefined}
                                className={id ? 'text-[var(--color-info)] underline decoration-dotted hover:decoration-solid' : 'text-[var(--color-muted)] underline decoration-dotted'}
                                onClick={id ? undefined : (e) => e.preventDefault()}
                            >
                                {target}
                            </a>
                        );
                    }
                    return <span key={idx}>{part}</span>;
                })
            ) : (
                <span className="text-[var(--color-muted)]/60">Empty block</span>
            )}
        </div>
    );
}

export function BlockHeading({ block, readMode, cb }: BlockViewProps) {
    const [level, setLevel] = useState(block.meta?.level || 1);

    function onInput(e: React.FormEvent<HTMLInputElement>) {
        cb.onUpdate({ content: (e.target as HTMLInputElement).value, meta: { ...block.meta, level } });
    }

    if (!readMode) {
        return (
            <div className="flex items-center gap-3 py-1">
                <select
                    value={level}
                    onChange={(e) => { const l = Number(e.target.value); setLevel(l); cb.onUpdate({ meta: { ...block.meta, level: l } }); }}
                    className={selectCls}
                >
                    <option value={1}>H1</option>
                    <option value={2}>H2</option>
                    <option value={3}>H3</option>
                </select>
                <input
                    defaultValue={block.content ?? ''}
                    placeholder={`Heading ${level}`}
                    className={`${inputCls} font-semibold text-xl`}
                    onInput={onInput}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); cb.onEnter(); }
                        else if (e.key === 'Backspace' && !(e.target as HTMLInputElement).value) { e.preventDefault(); cb.onBackspace(); }
                    }}
                    onFocus={cb.onFocus}
                />
            </div>
        );
    }

    const Tag = (`h${Math.min(level, 6)}`) as keyof JSX.IntrinsicElements;
    return (
        <div className="py-1">
            <Tag className="font-semibold leading-tight" style={{ fontSize: { 1: '1.5rem', 2: '1.25rem', 3: '1.125rem' }[Math.min(level, 3)] ?? '1rem' }}>
                {block.content || 'Heading'}
            </Tag>
        </div>
    );
}

export function BlockCode({ block, readMode, cb }: BlockViewProps) {
    const [language, setLanguage] = useState(block.meta?.language || 'javascript');

    function onInput(e: React.FormEvent<HTMLTextAreaElement>) {
        cb.onUpdate({ content: (e.target as HTMLTextAreaElement).value, meta: { ...block.meta, language } });
    }

    function onKeydown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const target = e.target as HTMLTextAreaElement;
            const start = target.selectionStart;
            const end = target.selectionEnd;
            const value = target.value;
            target.value = value.substring(0, start) + '  ' + value.substring(end);
            target.selectionStart = target.selectionEnd = start + 2;
            cb.onUpdate({ content: target.value, meta: { ...block.meta, language } });
        }
    }

    if (!readMode) {
        return (
            <div className="rounded-lg border border-[var(--color-border)] overflow-hidden">
                <select
                    value={language}
                    onChange={(e) => { setLanguage(e.target.value); cb.onUpdate({ meta: { ...block.meta, language: e.target.value } }); }}
                    className={`${selectCls} w-full rounded-none border-0 border-b border-[var(--color-border)] bg-[var(--color-surface-2)]`}
                >
                    {['javascript', 'typescript', 'python', 'php', 'html', 'css', 'json', 'sql', 'bash', 'java', 'c', 'cpp'].map((l) => (
                        <option key={l} value={l}>{l.charAt(0).toUpperCase() + l.slice(1)}</option>
                    ))}
                </select>
                <textarea
                    defaultValue={block.content ?? ''}
                    placeholder="Code..."
                    spellCheck={false}
                    className="w-full min-h-[8rem] p-4 bg-[#1f2430] text-[#e6e6e6] font-mono text-sm leading-6 outline-none resize-y"
                    onInput={onInput}
                    onKeyDown={onKeydown}
                    onFocus={cb.onFocus}
                />
            </div>
        );
    }

    const html = highlight(block.content ?? '', language);
    return (
        <pre className="m-0 p-4 rounded-lg bg-[#1f2430] text-[#e6e6e6] font-mono text-sm leading-6 overflow-x-auto">
            <code
                className="[&_.hl-k]:text-[#c792ea] [&_.hl-t]:text-[#82aaff] [&_.hl-s]:text-[#c3e88d] [&_.hl-c]:text-[#5c6773] [&_.hl-n]:text-[#f78c6c] [&_.hl-v]:text-[#f07178] [&_.hl-h]:text-[#ffcb6b] [&_.hl-p]:text-[#89ddff]"
                dangerouslySetInnerHTML={{ __html: html || block.content }}
            />
        </pre>
    );
}

export function BlockChecklist({ block, readMode, cb }: BlockViewProps) {
    const [items, setItems] = useState(() => parseItems(block.content));
    const [newItem, setNewItem] = useState('');

    function sync(next: { text: string; checked: boolean }[]) {
        setItems(next);
        cb.onUpdate({ content: JSON.stringify(next) });
    }

    function addItem() {
        if (!newItem.trim()) return;
        sync([...items, { text: newItem.trim(), checked: false }]);
        setNewItem('');
    }

    if (!readMode) {
        return (
            <div className="flex flex-col gap-1 py-1">
                {items.map((item, index) => (
                    <div key={index} className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={item.checked}
                            onChange={() => {
                                const next = items.map((it, i) => (i === index ? { ...it, checked: !it.checked } : it));
                                sync(next);
                            }}
                            className="accent-[var(--color-accent)]"
                        />
                        <input
                            defaultValue={item.text}
                            placeholder="Checklist item"
                            className={`${inputCls} text-base`}
                            onChange={(e) => {
                                const next = items.map((it, i) => (i === index ? { ...it, text: e.target.value } : it));
                                setItems(next);
                                cb.onUpdate({ content: JSON.stringify(next) });
                            }}
                            onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addItem(); } }}
                        />
                        <button type="button" className="text-[var(--color-bad)] text-xl leading-none hover:opacity-70" onClick={() => sync(items.filter((_, i) => i !== index))}>×</button>
                    </div>
                ))}
                <div className="flex items-center gap-2 mt-0.5">
                    <input
                        value={newItem}
                        placeholder="Add item..."
                        className={`${inputCls} text-base`}
                        onChange={(e) => setNewItem(e.target.value)}
                        onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addItem(); } }}
                    />
                    <button type="button" className={ghostBtn} onClick={addItem}>Add</button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-0.5 py-1">
            {items.map((item, index) => (
                <div key={index} className="flex items-start gap-2">
                    <span className="text-[var(--color-muted)]">{item.checked ? '☑' : '☐'}</span>
                    <span className={item.checked ? 'line-through text-[var(--color-muted)]' : ''}>{item.text}</span>
                </div>
            ))}
        </div>
    );
}

export function BlockList({ block, readMode, cb }: BlockViewProps) {
    const [items, setItems] = useState(() => parseItems(block.content));
    const [newItem, setNewItem] = useState('');
    const bullet = block.meta?.bullet ?? false;

    function sync(next: { text: string; checked: boolean }[]) {
        setItems(next);
        cb.onUpdate({ content: JSON.stringify(next) });
    }

    function addItem() {
        if (!newItem.trim()) return;
        sync([...items, { text: newItem.trim(), checked: false }]);
        setNewItem('');
    }

    if (!readMode) {
        return (
            <div className="flex flex-col gap-1 py-1">
                {items.map((item, index) => (
                    <div key={index} className="flex items-center gap-2">
                        <span className="text-[var(--color-muted)]">{bullet ? '•' : '1.'}</span>
                        <input
                            type="checkbox"
                            checked={item.checked}
                            onChange={() => {
                                const next = items.map((it, i) => (i === index ? { ...it, checked: !it.checked } : it));
                                sync(next);
                            }}
                            className="accent-[var(--color-accent)]"
                        />
                        <input
                            defaultValue={item.text}
                            placeholder="List item"
                            className={`${inputCls} text-base`}
                            onChange={(e) => {
                                const next = items.map((it, i) => (i === index ? { ...it, text: e.target.value } : it));
                                setItems(next);
                                cb.onUpdate({ content: JSON.stringify(next) });
                            }}
                            onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addItem(); } }}
                        />
                        <button type="button" className="text-[var(--color-bad)] text-xl leading-none hover:opacity-70" onClick={() => sync(items.filter((_, i) => i !== index))}>×</button>
                    </div>
                ))}
                <div className="flex items-center gap-2 mt-0.5">
                    <input
                        value={newItem}
                        placeholder="Add item..."
                        className={`${inputCls} text-base`}
                        onChange={(e) => setNewItem(e.target.value)}
                        onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addItem(); } }}
                    />
                    <button type="button" className={ghostBtn} onClick={addItem}>Add</button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-0.5 py-1">
            {items.map((item, index) => (
                <div key={index} className="flex items-start gap-2">
                    <span className="text-[var(--color-muted)]">{bullet ? '•' : '✓'}</span>
                    <span className={item.checked ? 'line-through text-[var(--color-muted)]' : ''}>{item.text}</span>
                </div>
            ))}
        </div>
    );
}

export function BlockQuote({ block, readMode, cb }: BlockViewProps) {
    if (!readMode) {
        return (
            <div className="py-1">
                <textarea
                    defaultValue={block.content ?? ''}
                    placeholder="Quote..."
                    className={`${inputCls} min-h-[2rem] border-l-2 border-[var(--color-muted)] pl-3 italic text-base leading-7`}
                    onInput={(e) => cb.onUpdate({ content: (e.target as HTMLTextAreaElement).value })}
                    onFocus={cb.onFocus}
                />
            </div>
        );
    }
    return (
        <blockquote className="border-l-2 border-[var(--color-muted)] pl-3 italic text-[var(--color-muted)] py-1">
            {block.content || 'Quote'}
        </blockquote>
    );
}

export function BlockDivider({ readMode }: BlockViewProps) {
    return <hr className="border-0 border-t border-[var(--color-border)] my-3" />;
}

export function BlockImage({ block, readMode, cb }: BlockViewProps) {
    const [url, setUrl] = useState(block.meta?.url || '');
    const [alt, setAlt] = useState(block.meta?.alt || '');

    function syncMeta() {
        cb.onUpdate({ meta: { ...block.meta, url, alt } });
    }

    if (!readMode) {
        return (
            <div className="space-y-2 py-1">
                <input
                    value={url}
                    placeholder="Image URL..."
                    className={`${inputCls} rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] px-2 py-1 text-sm`}
                    onChange={(e) => { setUrl(e.target.value); cb.onUpdate({ meta: { ...block.meta, url: e.target.value, alt } }); }}
                />
                <input
                    value={alt}
                    placeholder="Alt text..."
                    className={`${inputCls} rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] px-2 py-1 text-sm`}
                    onChange={(e) => { setAlt(e.target.value); cb.onUpdate({ meta: { ...block.meta, url, alt: e.target.value } }); }}
                />
                {url && <img src={url} alt={alt} className="max-w-full rounded-lg" />}
            </div>
        );
    }
    if (url) return <img src={url} alt={alt} className="max-w-full rounded-lg my-2" />;
    return <div className="text-sm text-[var(--color-muted)]/60 py-1">No image URL provided</div>;
}

export function BlockTable({ block, readMode, cb }: BlockViewProps) {
    const meta = block.meta || {};
    const [rows, setRows] = useState(Number(meta.rows) || 2);
    const [cols, setCols] = useState(Number(meta.cols) || 2);
    const [cells, setCells] = useState<string[]>(
        Array.isArray(meta.cells) && meta.cells.length === (Number(meta.rows) || 2) * (Number(meta.cols) || 2)
            ? meta.cells
            : Array.from<string>({ length: (Number(meta.rows) || 2) * (Number(meta.cols) || 2) }).fill('')
    );

    function syncCell(r: number, c: number, value: string) {
        const next = [...cells];
        next[r * cols + c] = value;
        setCells(next);
        cb.onUpdate({ meta: { ...meta, rows, cols, cells: next } });
    }

    function addRow() {
        const next = [...cells, ...Array.from<string>({ length: cols }).fill('')];
        const nr = rows + 1;
        setRows(nr);
        setCells(next);
        cb.onUpdate({ meta: { ...meta, rows: nr, cols, cells: next } });
    }

    function addCol() {
        const nc = cols + 1;
        const next: string[] = [];
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < nc; c++) {
                next.push(c < cols ? cells[r * cols + c] : '');
            }
        }
        setCols(nc);
        setCells(next);
        cb.onUpdate({ meta: { ...meta, rows, cols: nc, cells: next } });
    }

    return (
        <div className="py-1">
            {!readMode && (
                <div className="flex items-center gap-2 mb-2">
                    <button type="button" className={ghostBtn} onClick={addRow}>+ Row</button>
                    <button type="button" className={ghostBtn} onClick={addCol}>+ Column</button>
                    <span className="text-xs text-[var(--color-muted)]">{rows}×{cols}</span>
                </div>
            )}
            <div className="overflow-x-auto">
                <table className="border-collapse w-full text-sm">
                    <tbody>
                        {Array.from({ length: rows }).map((_, r) => (
                            <tr key={r}>
                                {Array.from({ length: cols }).map((_, c) => (
                                    <td key={c} className="border border-[var(--color-border)] p-1 min-w-[4rem]">
                                        {!readMode ? (
                                            <input
                                                defaultValue={cells[r * cols + c] ?? ''}
                                                className={`${inputCls} px-1 py-0.5 text-sm`}
                                                onChange={(e) => syncCell(r, c, e.target.value)}
                                            />
                                        ) : (
                                            <span className="px-1 py-0.5 block">{cells[r * cols + c] || '—'}</span>
                                        )}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

const CALLOUT_TONES = ['tip', 'warning', 'error', 'success', 'info', 'note'];
const CALLOUT_ICONS: Record<string, string> = {
    tip: '💡',
    warning: '⚠️',
    error: '❌',
    success: '✅',
    info: 'ℹ️',
    note: '📝',
};

export function BlockCallout({ block, readMode, cb }: BlockViewProps) {
    const meta = block.meta || {};
    const [tone, setTone] = useState<string>(meta.tone || 'info');
    const [icon, setIcon] = useState<string>(meta.icon || 'ℹ️');

    function updateMeta(nextTone?: string, nextIcon?: string) {
        const t = nextTone ?? tone;
        const i = nextIcon ?? icon;
        cb.onUpdate({ meta: { ...meta, tone: t, icon: i } });
    }

    const toneBg: Record<string, string> = {
        tip: 'border-[#34d399]/40 bg-[#34d399]/10',
        warning: 'border-[#fbbf24]/40 bg-[#fbbf24]/10',
        error: 'border-[#f87171]/40 bg-[#f87171]/10',
        success: 'border-[#34d399]/40 bg-[#34d399]/10',
        info: 'border-[#38bdf8]/40 bg-[#38bdf8]/10',
        note: 'border-[#8f82f5]/40 bg-[#8f82f5]/10',
    };

    return (
        <div className={`rounded-lg border p-3 ${toneBg[tone] ?? toneBg.info}`}>
            {!readMode && (
                <div className="flex items-center gap-2 mb-2">
                    <select
                        value={icon}
                        onChange={(e) => { setIcon(e.target.value); updateMeta(undefined, e.target.value); }}
                        className={selectCls}
                    >
                        {Object.entries(CALLOUT_ICONS).map(([key, val]) => (
                            <option key={key} value={val}>{val} {key.charAt(0).toUpperCase() + key.slice(1)}</option>
                        ))}
                    </select>
                    <select
                        value={tone}
                        onChange={(e) => { setTone(e.target.value); updateMeta(e.target.value, undefined); }}
                        className={selectCls}
                    >
                        {CALLOUT_TONES.map((t) => (
                            <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>
                        ))}
                    </select>
                </div>
            )}
            {!readMode ? (
                <textarea
                    defaultValue={block.content ?? ''}
                    placeholder="Callout text..."
                    className={`${inputCls} min-h-[2rem] text-sm leading-6`}
                    onInput={(e) => cb.onUpdate({ content: (e.target as HTMLTextAreaElement).value })}
                    onFocus={cb.onFocus}
                />
            ) : (
                <div className="flex items-start gap-2 text-sm">
                    <span className="text-xl">{icon}</span>
                    <p className="my-0">{block.content || 'Callout'}</p>
                </div>
            )}
        </div>
    );
}
