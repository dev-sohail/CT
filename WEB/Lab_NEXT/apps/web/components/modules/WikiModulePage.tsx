'use client';

import { useEffect, useState, useCallback, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Plus, Trash2, Edit2, Search } from 'lucide-react';
import { wikiApi, formatDate } from '@/components/wiki/api';
import { PageHeader, Badge, Button, Card, EmptyState, Input, Modal, Select, Switch, Spinner, ToastProvider, useToast, DropdownMenu } from '@ctlab/ctlab-ui';

interface SectionDef {
    key: string;
    label: string;
    icon: React.ReactNode;
    color: string;
    nameKey: string;
    fields: { key: string; label: string; type?: string; required?: boolean; placeholder?: string; options?: { value: string; label: string }[] }[];
}

const sections: SectionDef[] = [
    { key: 'workspaces', label: 'Workspaces', icon: <span className="text-lg">🗂️</span>, color: '#0ea5e9', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'description', label: 'Description', placeholder: 'Optional description' },
    ]},
    { key: 'pages', label: 'Pages', icon: <span className="text-lg">📄</span>, color: '#8b5cf6', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'content', label: 'Content', placeholder: 'Write your content here...' },
        { key: 'icon', label: 'Icon', placeholder: 'Emoji or icon name' },
    ]},
    { key: 'notebooks', label: 'Notebooks', icon: <span className="text-lg">📓</span>, color: '#10b981', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'description', label: 'Description', placeholder: 'Optional description' },
    ]},
    { key: 'projects', label: 'Projects', icon: <span className="text-lg">📂</span>, color: '#f59e0b', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'description', label: 'Description', placeholder: 'Project description' },
        { key: 'status', label: 'Status', type: 'select', options: [
            { value: 'active', label: 'Active' },
            { value: 'completed', label: 'Completed' },
            { value: 'archived', label: 'Archived' },
        ]},
    ]},
    { key: 'tasks', label: 'Tasks', icon: <span className="text-lg">✅</span>, color: '#ef4444', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'description', label: 'Description', placeholder: 'Task description' },
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
        { key: 'content', label: 'Content', placeholder: 'Review content...' },
        { key: 'status', label: 'Status', type: 'select', options: [
            { value: 'pending', label: 'Pending' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'done', label: 'Done' },
        ]},
    ]},
    { key: 'templates', label: 'Templates', icon: <span className="text-lg">🏷️</span>, color: '#6366f1', nameKey: 'name', fields: [
        { key: 'name', label: 'Name', required: true },
        { key: 'content', label: 'Template Content', placeholder: 'Template body...' },
    ]},
    { key: 'questions', label: 'Questions', icon: <span className="text-lg">❓</span>, color: '#14b8a6', nameKey: 'question', fields: [
        { key: 'question', label: 'Question', required: true },
        { key: 'answer', label: 'Answer', placeholder: 'Answer...' },
    ]},
    { key: 'references', label: 'References', icon: <span className="text-lg">🔖</span>, color: '#f97316', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'url', label: 'URL', placeholder: 'https://...' },
        { key: 'description', label: 'Description', placeholder: 'Description...' },
    ]},
    { key: 'sections', label: 'Sections', icon: <span className="text-lg">📝</span>, color: '#84cc16', nameKey: 'title', fields: [
        { key: 'title', label: 'Title', required: true },
        { key: 'content', label: 'Content', placeholder: 'Section content...' },
    ]},
];

function WikiSectionView({ section }: { section: SectionDef }) {
    const { notify } = useToast();
    const [items, setItems] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editItem, setEditItem] = useState<any | null>(null);
    const [form, setForm] = useState<Record<string, any>>({});
    const [saving, setSaving] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState<any | null>(null);
    const [search, setSearch] = useState('');

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
        setEditItem(null);
        setShowForm(true);
    }

    function openEdit(item: any) {
        const f: Record<string, any> = {};
        for (const field of section.fields) {
            f[field.key] = item[field.key] ?? '';
        }
        f.id = item.id;
        setForm(f);
        setEditItem(item);
        setShowForm(true);
    }

    function closeForm() {
        setShowForm(false);
        setEditItem(null);
        setForm({});
    }

    async function handleSave(e: React.FormEvent) {
        e.preventDefault();
        setSaving(true);
        try {
            if (editItem) {
                await wikiApi.put(`/${section.key}/${editItem.id}`, form);
                notify(`${section.label.slice(0, -1)} updated`, 'success');
            } else {
                await wikiApi.post(`/${section.key}`, form);
                notify(`${section.label.slice(0, -1)} created`, 'success');
            }
            closeForm();
            load();
        } catch (err: any) {
            notify(err.message || 'Save failed', 'danger');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete() {
        if (!confirmDelete) return;
        try {
            await wikiApi.del(`/${section.key}/${confirmDelete.id}`);
            notify(`${section.label.slice(0, -1)} deleted`, 'info');
            setConfirmDelete(null);
            load();
        } catch (err: any) {
            notify(err.message || 'Delete failed', 'danger');
        }
    }

    const filtered = search.trim()
        ? items.filter((item) => String(item[section.nameKey] ?? '').toLowerCase().includes(search.toLowerCase()))
        : items;

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    {section.icon}
                    <h2 className="text-lg font-semibold text-[var(--color-text)]">{section.label}</h2>
                    <Badge tone="neutral">{items.length}</Badge>
                </div>
                <Button size="sm" icon={<Plus size={14} />} onClick={openCreate}>New {section.label.slice(0, -1)}</Button>
            </div>

            {loading ? (
                <Card>
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Spinner size={24} />
                        <span className="text-sm text-[var(--color-muted)]">Loading {section.label.toLowerCase()}...</span>
                    </div>
                </Card>
            ) : items.length === 0 ? (
                <EmptyState
                    description={`No ${section.label.toLowerCase()} yet.`}
                    action={<Button size="sm" icon={<Plus size={14} />} onClick={openCreate}>Create first {section.label.slice(0, -1)}</Button>}
                />
            ) : (
                <>
                    {items.length > 3 && (
                        <div className="max-w-sm">
                            <Input
                                icon={<Search size={14} />}
                                placeholder={`Search ${section.label.toLowerCase()}...`}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    )}

                    {filtered.length === 0 ? (
                        <EmptyState title="No results" description={`No results for "${search}".`} />
                    ) : (
                        <Card className="[&_.nx-card-body]:p-0">
                            <div className="divide-y divide-[var(--color-border)]">
                                {filtered.map((item) => (
                                    <div key={item.id} className="flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-surface-2)] transition-colors group">
                                        <span className="text-lg shrink-0">{section.icon}</span>
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium text-[var(--color-text)] truncate text-sm">{item[section.nameKey]}</p>
                                            {section.fields.filter((f) => f.key !== section.nameKey).slice(0, 2).map((f) => item[f.key] && (
                                                <p key={f.key} className="text-xs text-[var(--color-muted)] truncate">{String(item[f.key]).slice(0, 100)}</p>
                                            ))}
                                        </div>
                                        {item.updated_at && <span className="text-xs text-[var(--color-muted)] shrink-0 hidden sm:block">{formatDate(item.updated_at)}</span>}
                                        <div className="shrink-0">
                                            <DropdownMenu
                                                trigger={
                                                    <button className="p-1.5 rounded hover:bg-[var(--color-surface)] text-[var(--color-muted)] cursor-pointer">
                                                        <span className="sr-only">Actions</span>
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="3" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13" r="1.5"/></svg>
                                                    </button>
                                                }
                                                items={[
                                                    { key: 'edit', label: 'Edit', icon: <Edit2 size={13} />, onSelect: () => openEdit(item) },
                                                    { key: 'delete', label: 'Delete', icon: <Trash2 size={13} />, danger: true, onSelect: () => setConfirmDelete(item) },
                                                ]}
                                                align="end"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}
                </>
            )}

            {showForm && (
                <Modal
                    open
                    onClose={closeForm}
                    title={editItem ? `Edit ${section.label.slice(0, -1)}` : `New ${section.label.slice(0, -1)}`}
                    footer={
                        <>
                            <Button variant="ghost" size="sm" onClick={closeForm}>Cancel</Button>
                            <Button size="sm" loading={saving} onClick={() => {
                                const formEl = document.querySelector<HTMLFormElement>('[data-wiki-form]');
                                formEl?.requestSubmit();
                            }}>
                                {editItem ? 'Update' : 'Create'}
                            </Button>
                        </>
                    }
                >
                    <form data-wiki-form onSubmit={handleSave} className="space-y-4">
                        {section.fields.map((f) => {
                            const val = form[f.key];
                            if (f.type === 'select' && f.options) {
                                return (
                                    <Select
                                        key={f.key}
                                        label={f.label}
                                        value={val ?? ''}
                                        onChange={(e) => setForm({ ...form, [f.key]: e.target.value })}
                                        options={f.options}
                                    />
                                );
                            }
                            if (f.type === 'toggle') {
                                return (
                                    <div key={f.key} className="flex items-center justify-between p-3 rounded-lg border border-[var(--color-border)]">
                                        <span className="text-sm font-medium text-[var(--color-text)]">{f.label}</span>
                                        <Switch checked={!!val} onChange={(v) => setForm({ ...form, [f.key]: v })} />
                                    </div>
                                );
                            }
                            if (f.type === 'date') {
                                return (
                                    <Input
                                        key={f.key}
                                        label={f.label}
                                        type="date"
                                        value={val ?? ''}
                                        onChange={(e) => setForm({ ...form, [f.key]: e.target.value })}
                                        required={f.required}
                                    />
                                );
                            }
                            if (f.key === 'content' || f.key === 'description' || f.key === 'answer') {
                                return (
                                    <div key={f.key}>
                                        <label className="block text-sm font-medium text-[var(--color-text)] mb-1.5">{f.label}</label>
                                        <textarea
                                            value={val ?? ''}
                                            onChange={(e) => setForm({ ...form, [f.key]: e.target.value })}
                                            rows={4}
                                            required={f.required}
                                            placeholder={f.placeholder}
                                            className="w-full px-3 py-2 text-sm rounded-md border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text)] placeholder:text-[var(--color-muted)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)] resize-y"
                                        />
                                    </div>
                                );
                            }
                            return (
                                <Input
                                    key={f.key}
                                    label={f.label}
                                    value={val ?? ''}
                                    onChange={(e) => setForm({ ...form, [f.key]: e.target.value })}
                                    required={f.required}
                                    placeholder={f.placeholder}
                                />
                            );
                        })}
                    </form>
                </Modal>
            )}

            {confirmDelete && (
                <Modal
                    open
                    onClose={() => setConfirmDelete(null)}
                    title={`Delete ${section.label.slice(0, -1)}`}
                    size="sm"
                    footer={
                        <>
                            <Button variant="ghost" size="sm" onClick={() => setConfirmDelete(null)}>Cancel</Button>
                            <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={handleDelete}>Delete</Button>
                        </>
                    }
                >
                    <p className="text-sm text-[var(--color-muted)]">
                        Are you sure you want to delete <strong>{confirmDelete[section.nameKey] ?? 'this item'}</strong>? This action cannot be undone.
                    </p>
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
                <Link href="/modules/26" className="inline-flex items-center gap-1 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)]">
                    <ArrowLeft size={14} /> Overview
                </Link>
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
        <ToastProvider>
            <Suspense fallback={<div className="flex flex-col items-center justify-center py-12 gap-3"><Spinner size={24} /><span className="text-sm text-[var(--color-muted)]">Loading...</span></div>}>
                <WikiModuleInner />
            </Suspense>
        </ToastProvider>
    );
}
