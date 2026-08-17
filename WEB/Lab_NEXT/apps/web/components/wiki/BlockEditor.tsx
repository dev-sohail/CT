'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import { ChevronDown, ChevronUp, Eye, EyeOff, GripVertical, Maximize2, Minimize2, Redo2, Undo2 } from 'lucide-react';
import { Button } from '@ctlab/ctlab-ui';
import { api } from '@/lib/api';
import type { Block } from './types';
import {
    BlockText, BlockHeading, BlockList, BlockChecklist, BlockQuote, BlockDivider,
    BlockCode, BlockImage, BlockTable, BlockCallout, type BlockCallbacks,
} from './blocks';

interface SlashState {
    visible: boolean;
    blockId: number | string | null;
    top: number;
    left: number;
    query: string;
    highlighted: number;
}

const SLASH_OPTIONS = [
    { type: 'text', label: 'Text', icon: '📝', hint: 'Plain text' },
    { type: 'heading', label: 'Heading', icon: '🔠', hint: 'H1–H3' },
    { type: 'list', label: 'List', icon: '📋', hint: 'Bulleted list' },
    { type: 'checklist', label: 'Checklist', icon: '✅', hint: 'Task list' },
    { type: 'quote', label: 'Quote', icon: '💬', hint: 'Blockquote' },
    { type: 'divider', label: 'Divider', icon: '➖', hint: 'Horizontal rule' },
    { type: 'code', label: 'Code', icon: '💻', hint: 'Syntax highlighted' },
    { type: 'image', label: 'Image', icon: '🖼️', hint: 'Image by URL' },
    { type: 'table', label: 'Table', icon: '📊', hint: 'Editable table' },
    { type: 'callout', label: 'Callout', icon: '💡', hint: 'Highlighted note' },
];

const BLOCK_COMPONENTS: Record<string, (props: any) => React.ReactNode> = {
    text: BlockText,
    heading: BlockHeading,
    list: BlockList,
    checklist: BlockChecklist,
    quote: BlockQuote,
    divider: BlockDivider,
    code: BlockCode,
    image: BlockImage,
    table: BlockTable,
    callout: BlockCallout,
};

function generateId(): string {
    return Date.now().toString(36) + Math.random().toString(36).substring(2);
}

interface BlockEditorProps {
    pageId: number;
    initialBlocks?: Block[];
    pageIndex?: Record<string, number>;
    readModeDefault?: boolean;
}

export default function BlockEditor({ pageId, initialBlocks = [], pageIndex = {}, readModeDefault = false }: BlockEditorProps) {
    const [blocks, setBlocks] = useState<Block[]>([]);
    const [focusedBlockId, setFocusedBlockId] = useState<number | string | null>(null);
    const [readMode, setReadMode] = useState(readModeDefault);
    const [focusMode, setFocusMode] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [lastSaved, setLastSaved] = useState<Date | null>(null);
    const [undoStack, setUndoStack] = useState<string[]>([]);
    const [redoStack, setRedoStack] = useState<string[]>([]);
    const [slashMenu, setSlashMenu] = useState<SlashState>({ visible: false, blockId: null, top: 0, left: 0, query: '', highlighted: 0 });
    const [dragState, setDragState] = useState<number | string | null>(null);

    const blocksRef = useRef<Block[]>(blocks);
    const slashRef = useRef<SlashState>(slashMenu);
    const undoRef = useRef<string[]>(undoStack);
    const redoRef = useRef<string[]>(redoStack);
    const saveTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => { blocksRef.current = blocks; }, [blocks]);
    useEffect(() => { slashRef.current = slashMenu; }, [slashMenu]);
    useEffect(() => { undoRef.current = undoStack; }, [undoStack]);
    useEffect(() => { redoRef.current = redoStack; }, [redoStack]);

    const pushUndo = useCallback(() => {
        const snap = JSON.stringify(blocksRef.current);
        setUndoStack((prev) => [...prev.slice(-49), snap]);
        setRedoStack([]);
    }, []);

    const renumberFrom = useCallback((startIndex: number) => {
        setBlocks((prev) => prev.map((b, i) => (i >= startIndex ? { ...b, sort_order: i } : b)));
    }, []);

    const triggerSave = useCallback(() => {
        if (saveTimeout.current) clearTimeout(saveTimeout.current);
        setIsSaving(true);
        saveTimeout.current = setTimeout(() => { void saveBlocks(); }, 800);
    }, []);

    async function saveBlocks() {
        const current = blocksRef.current;
        try {
            const payload = current.map((block, index) => ({
                id: typeof block.id === 'number' && block.id > 0 ? block.id : null,
                type: block.type,
                content: block.content,
                meta: block.meta || {},
                sort_order: block.sort_order ?? index,
            }));
            const res = await api<{ data: Block[]; errors?: any }>(`/api/v1/pages/${pageId}/blocks/sync`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ blocks: payload }),
            });
            if (res.errors) throw new Error('Save failed');
            const saved = res.data || [];
            setBlocks((prev) => {
                const next = prev.map((b) => ({ ...b }));
                saved.forEach((savedBlock) => {
                    let index = next.findIndex((b) => b.id === savedBlock.id);
                    if (index === -1) index = next.findIndex((b) => b.type === savedBlock.type && b.sort_order === savedBlock.sort_order);
                    if (index === -1) return;
                    const block = next[index];
                    const previousId = block.id;
                    block.id = savedBlock.id;
                    block.created_at = savedBlock.created_at;
                    block.updated_at = savedBlock.updated_at;
                    if (slashRef.current.blockId === previousId) slashRef.current.blockId = savedBlock.id;
                    if (focusedBlockId === previousId) setFocusedBlockId(savedBlock.id);
                });
                return next;
            });
            setLastSaved(new Date());
        } catch (error) {
            console.error('Failed to save blocks', error);
        } finally {
            setIsSaving(false);
        }
    }

    function addBlock(type = 'text', afterId: number | string | null = null) {
        pushUndo();
        const current = blocksRef.current;
        const before = afterId ? current.find((b) => b.id === afterId) : null;
        const sortOrder = before ? before.sort_order + 1 : current.length;
        const block: Block = {
            id: `local-${generateId()}`,
            _key: `blk-${generateId()}`,
            page_id: pageId,
            type,
            content: '',
            meta: {},
            sort_order: sortOrder,
        };
        applyTypeDefaults(block, type);

        if (afterId) {
            const index = current.findIndex((b) => b.id === afterId);
            setBlocks((prev) => {
                const next = [...prev];
                next.splice(index + 1, 0, block);
                return next;
            });
            renumberFrom(index + 1);
        } else {
            setBlocks((prev) => [...prev, block]);
        }
        closeSlashMenu();
        setFocusedBlockId(block.id);
        triggerSave();
    }

    function applyTypeDefaults(block: Block, type: string) {
        if (type === 'checklist') block.content = '[]';
        else if (type === 'list') { block.content = '[]'; block.meta = { bullet: true }; }
        else if (type === 'code') block.meta = { language: 'javascript' };
        else if (type === 'table') {
            block.content = '2x2';
            block.meta = { rows: 2, cols: 2, cells: Array.from({ length: 4 }).fill('') };
        } else if (type === 'image') block.meta = { url: '', alt: '' };
    }

    function convertBlock(id: number | string, spec: { type: string; content?: string; meta?: Record<string, any> }) {
        const index = blocksRef.current.findIndex((b) => b.id === id);
        if (index === -1) return;
        pushUndo();
        setBlocks((prev) => {
            const next = prev.map((b) => ({ ...b }));
            const block = { ...next[index] };
            block.type = spec.type;
            block.content = spec.content ?? '';
            block.meta = spec.meta || {};
            if (spec.type === 'code') block.meta = { ...block.meta, language: spec.meta?.language || 'javascript' };
            else if (spec.type === 'table') block.meta = { ...block.meta, rows: 2, cols: 2, cells: Array.from({ length: 4 }).fill('') };
            else if (spec.type === 'image') block.meta = { ...block.meta, url: '', alt: '' };
            else if (spec.type === 'checklist') block.content = '[]';
            else if (spec.type === 'list') { block.content = block.content || '[]'; block.meta = { ...block.meta, bullet: true }; }
            next[index] = block;
            return next;
        });
        triggerSave();
        setFocusedBlockId(id);
    }

    function removeBlock(id: number | string) {
        const index = blocksRef.current.findIndex((b) => b.id === id);
        if (index === -1) return;
        pushUndo();
        setBlocks((prev) => prev.filter((b) => b.id !== id));
        renumberFrom(index);
        triggerSave();
    }

    function updateBlock(id: number | string, updates: Partial<Block>) {
        setBlocks((prev) => prev.map((b) => (b.id === id ? { ...b, ...updates } : b)));
        triggerSave();
    }

    function moveBlock(id: number | string, direction: number) {
        const index = blocksRef.current.findIndex((b) => b.id === id);
        const target = index + direction;
        if (target < 0 || target >= blocksRef.current.length) return;
        pushUndo();
        setBlocks((prev) => {
            const next = [...prev];
            const temp = next[index];
            next[index] = next[target];
            next[target] = temp;
            return next;
        });
        renumberFrom(Math.min(index, target));
        triggerSave();
    }

    function onDrop(targetId: number | string) {
        const sourceId = dragState;
        setDragState(null);
        if (!sourceId || sourceId === targetId) return;
        const current = blocksRef.current;
        const fromIndex = current.findIndex((b) => b.id === sourceId);
        const toIndex = current.findIndex((b) => b.id === targetId);
        if (fromIndex === -1 || toIndex === -1) return;
        pushUndo();
        setBlocks((prev) => {
            const next = [...prev];
            const [moved] = next.splice(fromIndex, 1);
            const targetIndex = next.findIndex((b) => b.id === targetId);
            next.splice(targetIndex + 1, 0, moved);
            return next;
        });
        renumberFrom(0);
        triggerSave();
    }

    function openSlashMenu(blockId: number | string, position: { top: number; left: number }) {
        setSlashMenu({ visible: true, blockId, top: position.top, left: position.left, query: '', highlighted: 0 });
    }

    function updateSlashQuery(query: string) {
        setSlashMenu((prev) => (prev.visible ? { ...prev, query, highlighted: 0 } : prev));
    }

    function closeSlashMenu() {
        setSlashMenu({ visible: false, blockId: null, top: 0, left: 0, query: '', highlighted: 0 });
    }

    function selectSlashOption(option: (typeof SLASH_OPTIONS)[number]) {
        const blockId = slashRef.current.blockId;
        closeSlashMenu();
        if (blockId !== null) convertBlock(blockId, { type: option.type });
        else addBlock(option.type);
    }

    function handleKeydown(event: KeyboardEvent) {
        const slash = slashRef.current;
        const filtered = slash.visible
            ? (() => {
                  const q = slash.query.trim().toLowerCase();
                  if (!q) return SLASH_OPTIONS;
                  return SLASH_OPTIONS.filter((opt) => opt.type.toLowerCase().includes(q) || opt.label.toLowerCase().includes(q));
              })()
            : [];
        if (slash.visible) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setSlashMenu((prev) => ({ ...prev, highlighted: (prev.highlighted + 1) % filtered.length }));
                return;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                setSlashMenu((prev) => ({ ...prev, highlighted: (prev.highlighted - 1 + filtered.length) % filtered.length }));
                return;
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (filtered.length > 0) selectSlashOption(filtered[slash.highlighted]);
                return;
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeSlashMenu();
                return;
            }
        }

        if ((event.metaKey || event.ctrlKey) && event.key === 'z' && !event.shiftKey) {
            event.preventDefault();
            undo();
        } else if ((event.metaKey || event.ctrlKey) && (event.key === 'y' || (event.key === 'z' && event.shiftKey))) {
            event.preventDefault();
            redo();
        }
    }

    function undo() {
        const stack = undoRef.current;
        if (stack.length === 0) return;
        const snapshot = JSON.stringify(blocksRef.current);
        setBlocks(JSON.parse(stack[stack.length - 1]));
        setUndoStack(stack.slice(0, -1));
        setRedoStack((prev) => [...prev, snapshot]);
        triggerSave();
    }

    function redo() {
        const stack = redoRef.current;
        if (stack.length === 0) return;
        const snapshot = JSON.stringify(blocksRef.current);
        setBlocks(JSON.parse(stack[stack.length - 1]));
        setRedoStack(stack.slice(0, -1));
        setUndoStack((prev) => [...prev, snapshot]);
        triggerSave();
    }

    function handleClickOutside(event: MouseEvent) {
        if (slashRef.current.visible && !(event.target as HTMLElement).closest('.slash-menu') && !(event.target as HTMLElement).closest('.block-text__input')) {
            closeSlashMenu();
        }
    }

    useEffect(() => {
        document.addEventListener('keydown', handleKeydown);
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('keydown', handleKeydown);
            document.removeEventListener('mousedown', handleClickOutside);
            if (saveTimeout.current) clearTimeout(saveTimeout.current);
        };
    }, []);

    useEffect(() => {
        if (initialBlocks.length) {
            setBlocks(initialBlocks.map((block) => ({
                ...block,
                _key: block._key || `blk-${generateId()}`,
                id: block.id || `local-${generateId()}`,
            })));
        } else if (blocksRef.current.length === 0) {
            addBlock('text');
        }
    }, [initialBlocks]);

    const cb = useCallback((id: number | string, index: number): BlockCallbacks => ({
        onUpdate: (updates) => updateBlock(id, updates),
        onRemove: () => removeBlock(id),
        onMoveUp: () => moveBlock(id, -1),
        onMoveDown: () => moveBlock(id, 1),
        onFocus: () => setFocusedBlockId(id),
        onSlash: (position) => openSlashMenu(id, position),
        onSlashInput: (query) => updateSlashQuery(query),
        onSlashClose: closeSlashMenu,
        onConvert: (spec) => convertBlock(id, spec),
        onEnter: () => addBlock('text', id),
        onBackspace: () => {
            const current = blocksRef.current;
            const idx = current.findIndex((b) => b.id === id);
            if (idx !== -1 && !current[idx].content && current.length > 1) removeBlock(id);
        },
    }), [blocksRef.current, pageIndex]);

    const filteredOptions = (() => {
        const q = slashMenu.query.trim().toLowerCase();
        if (!q) return SLASH_OPTIONS;
        return SLASH_OPTIONS.filter((opt) => opt.type.toLowerCase().includes(q) || opt.label.toLowerCase().includes(q));
    })();

    return (
        <div className={`block-editor flex flex-col min-h-[300px] ${readMode ? 'block-editor--read' : ''}`}>
            {!readMode && (
                <div className="flex flex-wrap gap-1 p-2 border-b border-[var(--color-border)] bg-[var(--color-surface-2)] sticky top-0 z-10">
                    {['text', 'heading', 'list', 'checklist', 'code', 'table', 'callout', 'image', 'quote', 'divider'].map((t) => (
                        <Button key={t} type="button" variant="ghost" size="sm" onClick={() => addBlock(t)}>
                            {t}
                        </Button>
                    ))}
                    <div className="flex-1" />
                    <Button type="button" variant="ghost" size="sm" disabled={undoStack.length === 0} onClick={undo} icon={<Undo2 size={14} />} title="Undo (Ctrl+Z)">
                        Undo
                    </Button>
                    <Button type="button" variant="ghost" size="sm" disabled={redoStack.length === 0} onClick={redo} icon={<Redo2 size={14} />} title="Redo (Ctrl+Shift+Z)">
                        Redo
                    </Button>
                    <Button type="button" variant="ghost" size="sm" onClick={() => setFocusMode((v) => !v)} icon={focusMode ? <Minimize2 size={14} /> : <Maximize2 size={14} />}>
                        {focusMode ? 'Exit Focus' : 'Focus'}
                    </Button>
                    <Button type="button" variant="ghost" size="sm" onClick={() => setReadMode((v) => !v)} icon={readMode ? <EyeOff size={14} /> : <Eye size={14} />}>
                        {readMode ? 'Edit' : 'Read'}
                    </Button>
                    <span className="text-xs text-[var(--color-muted)] self-center px-1">{isSaving ? 'Saving...' : lastSaved ? `Saved ${lastSaved.toLocaleTimeString()}` : ''}</span>
                </div>
            )}

            <div className={`${focusMode ? 'max-w-[680px] mx-auto w-full' : ''}`}>
                <div className="block-editor__content px-2 py-1">
                    {blocks.length === 0 && (
                        <p className="text-sm text-[var(--color-muted)]/60 py-4 text-center">Press &quot;/&quot; for commands or start typing to add a block.</p>
                    )}
                    {blocks.map((block, index) => {
                        const Comp = BLOCK_COMPONENTS[block.type] || BlockText;
                        return (
                            <div
                                key={block._key || block.id}
                                className={`group flex gap-2 py-0.5 ${focusedBlockId === block.id ? 'bg-[var(--color-surface-2)]/60 rounded' : ''} ${dragState === block.id ? 'opacity-40' : ''}`}
                                draggable={!readMode}
                                onDragStart={(e) => { setDragState(block.id); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(block.id)); }}
                                onDragOver={(e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; }}
                                onDrop={(e) => { e.preventDefault(); onDrop(block.id); }}
                            >
                                <div className="flex flex-col items-center shrink-0 w-8 opacity-0 group-hover:opacity-100 pt-2">
                                    <span className="cursor-grab text-[var(--color-muted)]" title="Drag to reorder"><GripVertical size={14} /></span>
                                    <button type="button" className="text-[var(--color-muted)] hover:text-[var(--color-text)]" disabled={index === 0} onClick={() => moveBlock(block.id, -1)} title="Move up"><ChevronUp size={14} /></button>
                                    <button type="button" className="text-[var(--color-muted)] hover:text-[var(--color-text)]" disabled={index === blocks.length - 1} onClick={() => moveBlock(block.id, 1)} title="Move down"><ChevronDown size={14} /></button>
                                </div>
                                <div className="flex-1 min-w-0 py-0.5">
                                    <Comp
                                        block={block}
                                        readMode={readMode}
                                        pageIndex={pageIndex}
                                        cb={cb(block.id, index)}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {slashMenu.visible && (
                <div className="slash-menu absolute z-30 w-64 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] shadow-xl overflow-hidden" style={{ top: slashMenu.top, left: slashMenu.left }}>
                    {slashMenu.query && <div className="px-3 py-1 text-xs text-[var(--color-muted)] border-b border-[var(--color-border)]">Filter: &quot;/{slashMenu.query}&quot;</div>}
                    <div className="max-h-72 overflow-y-auto py-1">
                        {filteredOptions.map((option, index) => (
                            <button
                                key={option.type}
                                type="button"
                                className={`w-full flex items-center gap-2 px-3 py-1.5 text-left text-sm ${index === slashMenu.highlighted ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-hover)]' : 'text-[var(--color-text)] hover:bg-[var(--color-surface-2)]'}`}
                                onMouseDown={(e) => { e.preventDefault(); selectSlashOption(option); }}
                            >
                                <span>{option.icon}</span>
                                <span className="font-medium">{option.label}</span>
                                <span className="ml-auto text-xs text-[var(--color-muted)]">{option.hint}</span>
                            </button>
                        ))}
                        {filteredOptions.length === 0 && <div className="px-3 py-2 text-sm text-[var(--color-muted)]">No matching blocks</div>}
                    </div>
                </div>
            )}
        </div>
    );
}
