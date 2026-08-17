'use client';

import { useEffect, useState, useCallback, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Plus, Trash2, Edit2, X, Check } from 'lucide-react';
import { wikiApi, formatDate } from '@/components/wiki/api';
import { PageHeader, Badge } from '@ctlab/ctlab-ui';
import { fieldCls, primaryBtn, ghostBtn, Modal } from '@/components/wiki/ui';

interface SectionDef {
    key: string;
    label: string;
    icon: React.ReactNode;
    color: string;
    nameKey: string;
    fields: { key: string; label: string; type?: string; required?: boolean; options?: { value: string; label: string }[] }[];
}

const sections: SectionDef[] = [
    { key: 'workspaces', label: 'Workspaces', icon: <span className="text-lg">🗂️</span>, color: '#0ea5e9', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'description', label: 'Description' },
    ]},
    { key: 'pages', label: 'Pages', icon: <span className="text-lg">📄</span>, color: '#8b5cf6', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'content', label: 'Content' },
        { key: 'icon', label: 'Icon' },
    ]},
    { key: 'notebooks', label: 'Notebooks', icon: <span className="text-lg">📓</span>, color: '#10b981', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'description', label: 'Description' },
    ]},
    { key: 'projects', label: 'Projects', icon: <span className="text-lg">📂</span>, color: '#f59e0b', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'description', label: 'Description' },
        { key: 'status', label: 'Status', type: 'select', options: [
            { value: 'active', label: 'Active' },
            { value: 'completed', label: 'Completed' },
            { value: 'archived', label: 'Archived' },
        ]},
    ]},
    { key: 'tasks', label: 'Tasks', icon: <span className="text-lg">✅</span>, color: '#ef4444', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'description', label: 'Description' },
        { key: 'priority', label: 'Priority', type: 'select', options: [
            { value: 'low', label: 'Low' },
            { value: 'medium', label: 'Medium' },
            { value: 'high', label: 'High' },
        ]},
        { key: 'due_date', label: 'Due Date', type: 'date' },
        { key: 'done', label: 'Done', type: 'toggle' },
    ]},
    { key: 'reviews', label: 'Reviews', icon: <span className="text-lg">⭐</span>, color: '#ec4899', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'content', label: 'Content' },
        { key: 'status', label: 'Status', type: 'select', options: [
            { value: 'pending', label: 'Pending' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'done', label: 'Done' },
        ]},
    ]},
    { key: 'templates', label: 'Templates', icon: <span className="text-lg">🏷️</span>, color: '#6366f1', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'content', label: 'Template Content' },
    ]},
    { key: 'questions', label: 'Questions', icon: <span className="text-lg">❓</span>, color: '#14b8a6', nameKey: 'question', fields: [
        { key: 'question', label: 'Question', required: true },
        { key: 'answer', label: 'Answer' },
    ]},
    { key: 'references', label: 'References', icon: <span className="text-lg">🔖</span>, color: '#f97316', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'url', label: 'URL' },
        { key: 'description', label: 'Description' },
    ]},
    { key: 'sections', label: 'Sections', icon: <span className="text-lg">📝</span>, color: '#84cc16', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'content', label: 'Content' },
    ]},
];

function WikiSectionView({ section }: { section: SectionDef }) {
    const [items, setItems] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [showCreate, setShowCreate] = useState(false);
    const [editItem, setEditItem] = useState<any | null>(null);
    const [form, setForm] = useState<Record<string, any>>({});
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const data: any = await wikiApi.list(`/${section.key}`);
            setItems(Array.isArray(data) ? data : data?.data ?? []);
        } catch {
            setItems([]);
        } finally {
            setLoading(false);
        }
    }, [section.key]);

    useEffect(() => { load(); }, [load]);

    function openCreate() {
        setForm({});
        setShowCreate(true);
    }

    function openEdit(item: any) {
        const f: Record<string, any> = {};
        for (const field of section.fields) {
            f[field.key] = item[field.key] ?? '';
        }
        f.id = item.id;
        setForm(f);
        setEditItem(item);
    }

    async function handleSave(e: React.FormEvent) {
        e.preventDefault();
        setSaving(true);
        try {
            if (editItem) {
                await wikiApi.put(`/${section.key}/${editItem.id}`, form);
            } else {
                await wikiApi.post(`/${section.key}`, form);
            }
            setShowCreate(false);
            setEditItem(null);
            setForm({});
            load();
        } catch (err: any) {
            alert(err.message || 'Save failed');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(item: any) {
        if (!confirm(`Delete "${item[section.nameKey]}"?`)) return;
        try {
            await wikiApi.del(`/${section.key}/${item.id}`);
            load();
        } catch (err: any) {
            alert(err.message || 'Delete failed');
        }
    }

    const displayFields = section.fields.filter((f) => f.key !== section.nameKey);

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    {section.icon}
                    <h2 className="text-lg font-semibold text-[var(--color-text)]">{section.label}</h2>
                    <Badge>{items.length}</Badge>
                </div>
                <button onClick={openCreate} className={primaryBtn}>
                    <Plus size={14} className="mr-1" /> New {section.label.slice(0, -1)}
                </button>
            </div>

            {loading ? (
                <p className="text-sm text-[var(--color-muted)] py-8 text-center">Loading...</p>
            ) : items.length === 0 ? (
                <div className="text-center py-12 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)]">
                    <p className="text-[var(--color-muted)] mb-4">No {section.label.toLowerCase()} yet.</p>
                    <button onClick={openCreate} className={primaryBtn}>Create first {section.label.slice(0, -1)}</button>
                </div>
            ) : (
                <div className="rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] divide-y divide-[var(--color-border)]">
                    {items.map((item) => (
                        <div key={item.id} className="flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-surface-2)] transition-colors group">
                            <span className="text-lg shrink-0">{section.icon}</span>
                            <div className="min-w-0 flex-1">
                                <p className="font-medium text-[var(--color-text)] truncate">{item[section.nameKey]}</p>
                                {displayFields.slice(0, 2).map((f) => item[f.key] && (
                                    <p key={f.key} className="text-xs text-[var(--color-muted)] truncate">{String(item[f.key]).slice(0, 100)}</p>
                                ))}
                            </div>
                            {item.updated_at && <span className="text-xs text-[var(--color-muted)] shrink-0">{formatDate(item.updated_at)}</span>}
                            <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onClick={() => openEdit(item)} className="p-1.5 rounded hover:bg-[var(--color-surface)] text-[var(--color-muted)] hover:text-[var(--color-text)] cursor-pointer" title="Edit">
                                    <Edit2 size={14} />
                                </button>
                                <button onClick={() => handleDelete(item)} className="p-1.5 rounded hover:bg-[var(--color-bad)]/10 text-[var(--color-muted)] hover:text-[var(--color-bad)] cursor-pointer" title="Delete">
                                    <Trash2 size={14} />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {(showCreate || editItem) && (
                <Modal title={editItem ? `Edit ${section.label.slice(0, -1)}` : `New ${section.label.slice(0, -1)}`} onClose={() => { setShowCreate(false); setEditItem(null); setForm({}); }}>
                    <form onSubmit={handleSave} className="space-y-4">
                        {section.fields.map((f) => (
                            <label key={f.key} className="block space-y-1">
                                <span className="text-xs text-[var(--color-muted)]">{f.label}</span>
                                {f.type === 'select' && f.options ? (
                                    <select value={form[f.key] ?? ''} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} className={fieldCls} required={f.required}>
                                        <option value="">Select...</option>
                                        {f.options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                    </select>
                                ) : f.type === 'toggle' ? (
                                    <div className="flex items-center gap-2">
                                        <button type="button" onClick={() => setForm({ ...form, [f.key]: !form[f.key] })} className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors cursor-pointer ${form[f.key] ? 'bg-[var(--color-ok)]' : 'bg-[var(--color-surface-2)]'}`}>
                                            <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${form[f.key] ? 'translate-x-6' : 'translate-x-1'}`} />
                                        </button>
                                        <span className="text-sm text-[var(--color-muted)]">{form[f.key] ? 'Yes' : 'No'}</span>
                                    </div>
                                ) : f.type === 'date' ? (
                                    <input type="date" value={form[f.key] ?? ''} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} className={fieldCls} required={f.required} />
                                ) : f.key === 'content' || f.key === 'description' || f.key === 'answer' ? (
                                    <textarea value={form[f.key] ?? ''} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} rows={4} className={fieldCls} required={f.required} />
                                ) : (
                                    <input type="text" value={form[f.key] ?? ''} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} className={fieldCls} required={f.required} />
                                )}
                            </label>
                        ))}
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => { setShowCreate(false); setEditItem(null); setForm({}); }} className={ghostBtn}>Cancel</button>
                            <button type="submit" disabled={saving} className={primaryBtn}>{saving ? 'Saving...' : editItem ? 'Update' : 'Create'}</button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}

function WikiModuleInner() {
    const searchParams = useSearchParams();
    const activeSection = searchParams.get('section');

    const sectionDef = activeSection ? sections.find((s) => s.key === activeSection) : null;

    if (sectionDef) {
        return (
            <div className="space-y-6">
                <div className="flex items-center gap-3">
                    <Link href="/modules/26" className="inline-flex items-center gap-1 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)]">
                        <ArrowLeft size={14} /> Overview
                    </Link>
                </div>
                <WikiSectionView section={sectionDef} />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <PageHeader title="Second Brain / Personal Wiki" subtitle="Your knowledge base and note-taking system" />
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                {sections.map((s) => (
                    <Link
                        key={s.key}
                        href={`/modules/26?section=${s.key}`}
                        className="group flex items-center gap-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4 hover:border-[var(--color-accent)]/50 transition-colors"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg" style={{ backgroundColor: `${s.color}15`, color: s.color }}>
                            {s.icon}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="font-medium text-[var(--color-text)] group-hover:text-[var(--color-accent-hover)] truncate">{s.label}</p>
                        </div>
                    </Link>
                ))}
            </div>
        </div>
    );
}

export default function WikiModulePage() {
    return (
        <Suspense fallback={<p className="text-sm text-[var(--color-muted)] py-8 text-center">Loading...</p>}>
            <WikiModuleInner />
        </Suspense>
    );
}
