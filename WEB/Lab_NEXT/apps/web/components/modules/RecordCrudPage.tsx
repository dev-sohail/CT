'use client';

import { useEffect, useState, useCallback } from 'react';
import { Plus, Pencil, Trash2, X } from 'lucide-react';
import { Button, Card, EmptyState, Input, Modal, PageHeader, Table, Badge, type Column } from '@ctlab/ctlab-ui';
import { wikiApi } from '@/components/wiki/api';
import { fieldCls, selectCls } from '@/components/wiki/ui';

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

export function RecordCrudPage({
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
    const [rows, setRows] = useState<Record<string, any>[]>([]);
    const [statsData, setStatsData] = useState<Record<string, any> | null>(null);
    const [multiStats, setMultiStats] = useState<Record<string, any>[]>([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editRow, setEditRow] = useState<Record<string, any> | null>(null);
    const [form, setForm] = useState<Record<string, any>>({});
    const [saving, setSaving] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState<Record<string, any> | null>(null);

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
                if (stats && statsData !== null) setStatsData(statsData);
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
            } else {
                await wikiApi.post(basePath, body);
            }
            setShowForm(false);
            load();
        } catch (err: any) {
            alert(err.message || 'Save failed');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(row: Record<string, any>) {
        try {
            await wikiApi.del(`${basePath}/${rowKey(row)}`);
            setConfirmDelete(null);
            load();
        } catch (err: any) {
            alert(err.message || 'Delete failed');
        }
    }

    function setField(key: string, value: any) {
        setForm((prev) => ({ ...prev, [key]: value }));
    }

    const displayColumns: Column<Record<string, any>>[] = tableColumns ?? fields.slice(0, 5).map((fd) => ({
        key: fd.key,
        header: fd.label,
        render: (row) => {
            const v = row[fd.key];
            if (fd.type === 'toggle') return v ? '✓' : '—';
            if (fd.type === 'select' && fd.options) {
                const opt = fd.options.find((o) => o.value === v);
                return opt ? <Badge>{opt.label}</Badge> : (v ?? '—');
            }
            if (fd.key === 'status') return <Badge>{String(v ?? '—')}</Badge>;
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

    displayColumns.push({
        key: '__actions',
        header: '',
        className: 'w-24 text-right',
        render: (row) => (
            <div className="flex items-center justify-end gap-1">
                <button onClick={(e) => { e.stopPropagation(); openEdit(row); }} className="p-1 rounded hover:bg-[var(--color-surface-2)] text-[var(--color-muted)]" title="Edit">
                    <Pencil size={14} />
                </button>
                <button onClick={(e) => { e.stopPropagation(); setConfirmDelete(row); }} className="p-1 rounded hover:bg-[var(--color-bad)]/10 text-[var(--color-muted)] hover:text-[var(--color-bad)]" title="Delete">
                    <Trash2 size={14} />
                </button>
                {actions?.map((act) => (
                    <button
                        key={act.label}
                        onClick={async (e) => {
                            e.stopPropagation();
                            try {
                                await wikiApi[act.method === 'delete' ? 'del' : act.method === 'post' ? 'post' : 'put'](
                                    act.path(row),
                                    act.body?.(row),
                                );
                                if (act.after) act.after();
                                else load();
                            } catch (err: any) { alert(err.message); }
                        }}
                        className="p-1 rounded hover:bg-[var(--color-surface-2)] text-[var(--color-muted)] text-xs"
                        title={act.label}
                    >
                        {act.label}
                    </button>
                ))}
            </div>
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
                        {!statsOnly && <span className="text-[var(--color-muted)]">{rows.length} records</span>}
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
                <Card><p className="text-[var(--color-muted)] text-sm py-8 text-center">Loading...</p></Card>
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
                            <p className="text-[var(--color-muted)] text-sm py-8 text-center">No analytics data available yet.</p>
                        </Card>
                    )}
                </div>
            ) : rows.length === 0 ? (
                <EmptyState
                    description={`No ${title.toLowerCase()} yet. Click "Add ${title.replace(/s$/, '')}" to create your first record.`}
                />
            ) : (
                <Card>
                    <Table
                        columns={displayColumns}
                        rows={rows}
                        rowKey={rowKey}
                        empty="No records."
                    />
                </Card>
            )}

            {showForm && (
                <Modal
                    open
                    onClose={() => setShowForm(false)}
                    title={editRow ? `Edit ${title.replace(/s$/, '')}` : `New ${title.replace(/s$/, '')}`}
                    size="lg"
                >
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {fields.map((fd) => {
                                const val = form[fd.key];
                                if (fd.type === 'toggle') {
                                    return (
                                        <label key={fd.key} className="flex items-center gap-3 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={!!val}
                                                onChange={(e) => setField(fd.key, e.target.checked)}
                                                className="accent-[var(--color-accent)]"
                                            />
                                            <span className="text-sm text-[var(--color-text)]">{fd.label}</span>
                                        </label>
                                    );
                                }
                                if (fd.type === 'select') {
                                    return (
                                        <div key={fd.key}>
                                            <label className="nx-label">{fd.label}</label>
                                            <select value={val ?? ''} onChange={(e) => setField(fd.key, e.target.value)} className={selectCls}>
                                                <option value="">Select...</option>
                                                {fd.options?.map((o) => (
                                                    <option key={o.value} value={o.value}>{o.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                    );
                                }
                                if (fd.type === 'textarea') {
                                    return (
                                        <div key={fd.key} className="md:col-span-2">
                                            <label className="nx-label">{fd.label}</label>
                                            <textarea
                                                value={val ?? ''}
                                                onChange={(e) => setField(fd.key, e.target.value)}
                                                required={fd.required}
                                                placeholder={fd.placeholder}
                                                rows={3}
                                                className={`${fieldCls} resize-y`}
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
                        <div className="flex justify-end gap-2 pt-2 border-t border-[var(--color-border)]">
                            <Button variant="secondary" type="button" onClick={() => setShowForm(false)}>Cancel</Button>
                            <Button type="submit" disabled={saving}>{saving ? 'Saving...' : editRow ? 'Update' : 'Create'}</Button>
                        </div>
                    </form>
                </Modal>
            )}

            {confirmDelete && (
                <Modal
                    open
                    onClose={() => setConfirmDelete(null)}
                    title="Confirm Delete"
                    size="sm"
                >
                    <p className="text-sm text-[var(--color-text)] mb-4">
                        Are you sure you want to delete <strong>{confirmDelete[nameKey] ?? 'this record'}</strong>?
                    </p>
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" onClick={() => setConfirmDelete(null)}>Cancel</Button>
                        <Button variant="danger" onClick={() => handleDelete(confirmDelete)}>Delete</Button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
