'use client';

import { useEffect, useState, useCallback, useMemo } from 'react';
import { Plus, Pencil, Trash2, Search, MoreHorizontal } from 'lucide-react';
import { Button, Card, EmptyState, Input, Modal, PageHeader, Table, Badge, Spinner, Switch, Select, ToastProvider, useToast, DropdownMenu, type Column } from '@ctlab/ctlab-ui';
import { wikiApi } from '@/components/wiki/api';

export interface FieldDef {
    key: string;
    label: string;
    type?: 'text' | 'number' | 'date' | 'datetime-local' | 'select' | 'textarea' | 'toggle';
    options?: { value: string; label: string }[];
    required?: boolean;
    placeholder?: string;
    hint?: string;
    columnClassName?: string;
}

export interface ActionDef {
    label: string;
    method: 'post' | 'put' | 'delete';
    path: (row: Record<string, any>) => string;
    body?: (row: Record<string, any>) => unknown;
    after?: () => void;
}

export interface StatsDef {
    path: string;
    render: (data: Record<string, any>) => React.ReactNode;
}

export interface RecordCrudPageProps {
    title: string;
    subtitle?: string;
    basePath: string;
    fields: FieldDef[];
    tableColumns?: Column<Record<string, any>>[];
    stats?: StatsDef;
    actions?: ActionDef[];
    nameKey?: string;
    rowKey?: (row: Record<string, any>) => string | number;
    statsOnly?: boolean;
    statsEndpoints?: { key: string; label: string; path: string }[];
}

function emptyRow(fields: FieldDef[]): Record<string, any> {
    const row: Record<string, any> = {};
    fields.forEach((f) => {
        if (f.type === 'toggle') row[f.key] = false;
        else if (f.type === 'number') row[f.key] = null;
        else row[f.key] = '';
    });
    return row;
}

function RecordCrudPageInner({
    title,
    subtitle,
    basePath,
    fields,
    tableColumns,
    stats,
    actions,
    nameKey = 'name',
    rowKey = (r) => r.id,
    statsOnly = false,
    statsEndpoints,
}: RecordCrudPageProps) {
    const { notify } = useToast();
    const [rows, setRows] = useState<Record<string, any>[]>([]);
    const [statsData, setStatsData] = useState<Record<string, any> | null>(null);
    const [multiStats, setMultiStats] = useState<Record<string, any>[]>([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editRow, setEditRow] = useState<Record<string, any> | null>(null);
    const [form, setForm] = useState<Record<string, any>>({});
    const [saving, setSaving] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState<Record<string, any> | null>(null);
    const [search, setSearch] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        try {
            if (statsOnly && statsEndpoints?.length) {
                const results = await Promise.all(
                    statsEndpoints.map((ep) => wikiApi.get<Record<string, any>>(ep.path).catch(() => ({ __label: ep.label, __error: true })))
                );
                setMultiStats(results);
            } else {
                const [listData] = await Promise.all([
                    wikiApi.list<Record<string, any>>(basePath),
                    stats ? wikiApi.get<Record<string, any>>(stats.path).catch(() => null) : Promise.resolve(null),
                ]);
                setRows(listData);
                if (stats) {
                    try {
                        setStatsData(await wikiApi.get<Record<string, any>>(stats.path));
                    } catch { /* ignore */ }
                }
            }
        } catch { /* ignore */ }
        setLoading(false);
    }, [basePath, statsOnly, JSON.stringify(statsEndpoints?.map(e => e.path)), stats?.path]);

    useEffect(() => { load(); }, [load]);

    function openCreate() {
        setEditRow(null);
        setForm(emptyRow(fields));
        setShowForm(true);
    }

    function openEdit(row: Record<string, any>) {
        setEditRow(row);
        const f: Record<string, any> = {};
        fields.forEach((fd) => {
            f[fd.key] = row[fd.key] ?? (fd.type === 'toggle' ? false : fd.type === 'number' ? null : '');
        });
        setForm(f);
        setShowForm(true);
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSaving(true);
        try {
            const body: Record<string, any> = {};
            fields.forEach((fd) => {
                const v = form[fd.key];
                if (v !== null && v !== undefined && v !== '') body[fd.key] = v;
            });
            if (editRow) {
                await wikiApi.put(`${basePath}/${rowKey(editRow)}`, body);
                notify(`${title.replace(/s$/, '')} updated`, 'success');
            } else {
                await wikiApi.post(basePath, body);
                notify(`${title.replace(/s$/, '')} created`, 'success');
            }
            setShowForm(false);
            load();
        } catch (err: any) {
            notify(err.message || 'Save failed', 'danger');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(row: Record<string, any>) {
        try {
            await wikiApi.del(`${basePath}/${rowKey(row)}`);
            setConfirmDelete(null);
            notify(`${title.replace(/s$/, '')} deleted`, 'info');
            load();
        } catch (err: any) {
            notify(err.message || 'Delete failed', 'danger');
        }
    }

    function setField(key: string, value: any) {
        setForm((prev) => ({ ...prev, [key]: value }));
    }

    const filteredRows = useMemo(() => {
        if (!search.trim()) return rows;
        const q = search.toLowerCase();
        return rows.filter((row) =>
            fields.some((fd) => {
                const v = row[fd.key];
                if (v == null) return false;
                if (fd.type === 'select' && fd.options) {
                    const opt = fd.options.find((o) => o.value === String(v));
                    return opt?.label.toLowerCase().includes(q) || String(v).toLowerCase().includes(q);
                }
                return String(v).toLowerCase().includes(q);
            })
        );
    }, [rows, search, fields]);

    const displayColumns: Column<Record<string, any>>[] = tableColumns ?? fields.slice(0, 5).map((fd) => ({
        key: fd.key,
        header: fd.label,
        render: (row) => {
            const v = row[fd.key];
            if (fd.type === 'toggle') return v ? <Badge tone="success">Yes</Badge> : <Badge tone="neutral">No</Badge>;
            if (fd.type === 'select' && fd.options) {
                const opt = fd.options.find((o) => o.value === v);
                return opt ? <Badge tone="primary">{opt.label}</Badge> : (v ?? '—');
            }
            if (fd.key === 'status') return <Badge tone="info">{String(v ?? '—')}</Badge>;
            if (fd.key.includes('date') || fd.key.includes('_at')) {
                if (!v) return '—';
                const d = new Date(v);
                return isNaN(d.getTime()) ? v : d.toLocaleDateString();
            }
            if (fd.key === 'amount' || fd.key === 'target_amount' || fd.key === 'current_amount') {
                return v != null ? `$${Number(v).toFixed(2)}` : '—';
            }
            return v ?? '—';
        },
        className: fd.columnClassName,
    }));

    const menuItems = [
        { key: 'edit', label: 'Edit', icon: <Pencil size={13} /> },
        ...(actions?.map((act) => ({ key: `act_${act.label}`, label: act.label })) ?? []),
        { key: 'delete', label: 'Delete', icon: <Trash2 size={13} />, danger: true },
    ];

    displayColumns.push({
        key: '__actions',
        header: '',
        className: 'w-12 text-right',
        render: (row) => (
            <DropdownMenu
                trigger={
                    <button className="p-1 rounded hover:bg-[var(--color-surface-2)] text-[var(--color-muted)] cursor-pointer">
                        <MoreHorizontal size={16} />
                    </button>
                }
                items={menuItems.map((item) => ({
                    ...item,
                    onSelect: () => {
                        if (item.key === 'edit') openEdit(row);
                        else if (item.key === 'delete') setConfirmDelete(row);
                        else {
                            const act = actions?.find((a) => `act_${a.label}` === item.key);
                            if (act) {
                                wikiApi[act.method === 'delete' ? 'del' : act.method === 'post' ? 'post' : 'put'](
                                    act.path(row), act.body?.(row),
                                ).then(() => { if (act.after) act.after(); else load(); })
                                  .catch((err: any) => notify(err.message, 'danger'));
                            }
                        }
                    },
                }))}
                align="end"
            />
        ),
    });

    return (
        <div className="space-y-6">
            <PageHeader
                title={title}
                subtitle={
                    <span className="flex items-center gap-3">
                        {subtitle}
                        {statsData && stats && stats.render(statsData)}
                        {!statsOnly && <span className="text-[var(--color-muted)]">{rows.length} record{rows.length !== 1 ? 's' : ''}</span>}
                    </span>
                }
                actions={
                    statsOnly ? null : (
                        <Button icon={<Plus size={16} />} onClick={openCreate}>
                            Add {title.replace(/s$/, '')}
                        </Button>
                    )
                }
            />

            {loading ? (
                <Card>
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Spinner size={24} />
                        <span className="text-sm text-[var(--color-muted)]">Loading {title.toLowerCase()}...</span>
                    </div>
                </Card>
            ) : statsOnly ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {multiStats.map((data, i) => {
                        const ep = statsEndpoints?.[i];
                        return (
                            <Card key={ep?.key ?? i}>
                                <div className="p-4">
                                    <h3 className="text-sm font-medium text-[var(--color-muted)] mb-2">{ep?.label ?? 'Data'}</h3>
                                    {data?.__error ? (
                                        <p className="text-xs text-[var(--color-muted)]">No data available</p>
                                    ) : (
                                        <pre className="text-xs text-[var(--color-text)] bg-[var(--color-surface-2)] rounded p-2 overflow-auto max-h-48">
                                            {JSON.stringify(data, null, 2)}
                                        </pre>
                                    )}
                                </div>
                            </Card>
                        );
                    })}
                    {multiStats.length === 0 && (
                        <Card className="md:col-span-2 lg:col-span-3">
                            <EmptyState description="No analytics data available yet." />
                        </Card>
                    )}
                </div>
            ) : (
                <>
                    {rows.length > 0 && (
                        <div className="max-w-sm">
                            <Input
                                icon={<Search size={14} />}
                                placeholder={`Search ${title.toLowerCase()}...`}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    )}

                    {filteredRows.length === 0 && !search ? (
                        <EmptyState
                            description={`No ${title.toLowerCase()} yet. Click "Add ${title.replace(/s$/, '')}" to create your first record.`}
                        />
                    ) : filteredRows.length === 0 && search ? (
                        <EmptyState
                            title="No results"
                            description={`No ${title.toLowerCase()} match "${search}". Try a different search.`}
                        />
                    ) : (
                        <Card>
                            <Table
                                columns={displayColumns}
                                rows={filteredRows}
                                rowKey={rowKey}
                                empty="No records."
                            />
                        </Card>
                    )}
                </>
            )}

            {showForm && (
                <Modal
                    open
                    onClose={() => setShowForm(false)}
                    title={editRow ? `Edit ${title.replace(/s$/, '')}` : `New ${title.replace(/s$/, '')}`}
                    size="lg"
                    footer={
                        <>
                            <Button variant="ghost" size="sm" onClick={() => setShowForm(false)}>Cancel</Button>
                            <Button size="sm" loading={saving} disabled={saving} onClick={() => {
                                const formEl = document.querySelector<HTMLFormElement>('[data-crud-form]');
                                formEl?.requestSubmit();
                            }}>
                                {editRow ? 'Update' : 'Create'}
                            </Button>
                        </>
                    }
                >
                    <form data-crud-form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {fields.map((fd) => {
                                const val = form[fd.key];
                                if (fd.type === 'toggle') {
                                    return (
                                        <div key={fd.key} className="flex items-center justify-between md:col-span-2 p-3 rounded-lg border border-[var(--color-border)]">
                                            <div>
                                                <p className="text-sm font-medium text-[var(--color-text)]">{fd.label}</p>
                                                {fd.hint && <p className="text-xs text-[var(--color-muted)]">{fd.hint}</p>}
                                            </div>
                                            <Switch checked={!!val} onChange={(v) => setField(fd.key, v)} />
                                        </div>
                                    );
                                }
                                if (fd.type === 'select') {
                                    return (
                                        <Select
                                            key={fd.key}
                                            label={fd.label}
                                            value={val ?? ''}
                                            onChange={(e) => setField(fd.key, e.target.value)}
                                            options={fd.options ?? []}
                                        />
                                    );
                                }
                                if (fd.type === 'textarea') {
                                    return (
                                        <div key={fd.key} className="md:col-span-2">
                                            <Input
                                                label={fd.label}
                                                value={val ?? ''}
                                                onChange={(e) => setField(fd.key, e.target.value)}
                                                required={fd.required}
                                                placeholder={fd.placeholder}
                                                hint={fd.hint}
                                            />
                                        </div>
                                    );
                                }
                                return (
                                    <Input
                                        key={fd.key}
                                        label={fd.label}
                                        type={fd.type === 'datetime-local' ? 'datetime-local' : fd.type === 'date' ? 'date' : fd.type === 'number' ? 'number' : 'text'}
                                        value={val ?? ''}
                                        onChange={(e) => setField(fd.key, fd.type === 'number' ? (e.target.value ? Number(e.target.value) : null) : e.target.value)}
                                        required={fd.required}
                                        placeholder={fd.placeholder}
                                        hint={fd.hint}
                                        step={fd.type === 'number' ? 'any' : undefined}
                                    />
                                );
                            })}
                        </div>
                    </form>
                </Modal>
            )}

            {confirmDelete && (
                <Modal
                    open
                    onClose={() => setConfirmDelete(null)}
                    title={`Delete ${title.replace(/s$/, '')}`}
                    size="sm"
                    footer={
                        <>
                            <Button variant="ghost" size="sm" onClick={() => setConfirmDelete(null)}>Cancel</Button>
                            <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={() => handleDelete(confirmDelete)}>Delete</Button>
                        </>
                    }
                >
                    <p className="text-sm text-[var(--color-muted)]">
                        Are you sure you want to delete <strong>{confirmDelete[nameKey] ?? 'this record'}</strong>? This action cannot be undone.
                    </p>
                </Modal>
            )}
        </div>
    );
}

export function RecordCrudPage(props: RecordCrudPageProps) {
    return (
        <ToastProvider>
            <RecordCrudPageInner {...props} />
        </ToastProvider>
    );
}
