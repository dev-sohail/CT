'use client';

import { useState, useMemo } from 'react';
import {
    ShieldCheck,
    Plus,
    Search,
    Edit2,
    Trash2,
    X,
    Save,
    Key,
    Lock,
    GripVertical,
    Check,
    ChevronDown,
    Users,
} from 'lucide-react';
import { Button, Card, Input, Select, Modal, Switch, Spinner, Tabs, Badge, ToastProvider, useToast } from '@ctlab/ctlab-ui';
import { useRBAC, type Permission } from '@/components/RBACProvider';

// ── Helpers ─────────────────────────────────────────────────────────────────────

function slugify(text: string) {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '.')
        .replace(/(^\.|\.+$)/g, '');
}

function permissionGroups(perms: Permission[]) {
    const groups: Record<string, Permission[]> = {};
    for (const p of perms) {
        (groups[p.group] ??= []).push(p);
    }
    return groups;
}

// ── Roles Tab ───────────────────────────────────────────────────────────────────

function RolesTab({ userCount }: { userCount: number }) {
    const { notify } = useToast();
    const { roles, addRole, updateRole, deleteRole } = useRBAC();
    const [search, setSearch] = useState('');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRole, setEditingRole] = useState<number | undefined>();
    const [formName, setFormName] = useState('');
    const [formDesc, setFormDesc] = useState('');
    const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);

    const filtered = roles.filter((r) => r.name.toLowerCase().includes(search.toLowerCase()));

    const openCreate = () => {
        setEditingRole(undefined);
        setFormName('');
        setFormDesc('');
        setModalOpen(true);
    };

    const openEdit = (role: typeof roles[0]) => {
        setEditingRole(role.id);
        setFormName(role.name);
        setFormDesc(role.description);
        setModalOpen(true);
    };

    const handleSave = async () => {
        if (!formName.trim()) return;
        try {
            if (editingRole) {
                await updateRole(editingRole, { name: formName.trim(), slug: slugify(formName.trim()), description: formDesc.trim() });
                notify('Role updated', 'success');
            } else {
                await addRole({ name: formName.trim(), slug: slugify(formName.trim()), description: formDesc.trim() });
                notify('Role created', 'success');
            }
            setModalOpen(false);
        } catch {
            notify('Failed to save role', 'danger');
        }
    };

    const handleDelete = async (id: number) => {
        const role = roles.find((r) => r.id === id);
        if (role?.is_system) {
            notify('System roles cannot be deleted', 'warning');
            return;
        }
        try {
            await deleteRole(id);
            setDeleteConfirm(null);
            notify('Role deleted', 'info');
        } catch {
            notify('Failed to delete role', 'danger');
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-3">
                    <div className="flex-1">
                    <Input
                        icon={<Search size={14} />}
                        placeholder="Search roles..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <Button size="sm" icon={<Plus size={14} />} onClick={openCreate}>New Role</Button>
            </div>

            <div className="space-y-2">
                {filtered.map((role) => (
                    <div key={role.id} className="flex items-center gap-4 p-3 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] hover:bg-[var(--color-surface-2)] transition-colors">
                        <div className="w-9 h-9 rounded-lg bg-[var(--color-accent-soft)] flex items-center justify-center shrink-0">
                            <ShieldCheck size={18} className="text-[var(--color-accent-hover)]" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                                <span className="font-medium text-sm text-[var(--color-text)]">{role.name}</span>
                                <code className="text-[10px] text-[var(--color-muted)] bg-[var(--color-surface-2)] px-1.5 py-0.5 rounded font-mono">{role.slug}</code>
                                {role.is_system && <Badge tone="info" className="text-[10px]">System</Badge>}
                            </div>
                            <p className="text-xs text-[var(--color-muted)] truncate">{role.description}</p>
                        </div>
                        <div className="flex items-center gap-1 shrink-0">
                            <Badge tone="neutral" className="text-[10px]">
                                {role.permissions.length} perm{role.permissions.length !== 1 ? 's' : ''}
                            </Badge>
                            <Button variant="ghost" size="sm" icon={<Edit2 size={13} />} onClick={() => openEdit(role)} />
                            {!role.is_system && (
                                <Button variant="ghost" size="sm" icon={<Trash2 size={13} />} onClick={() => setDeleteConfirm(role.id)} />
                            )}
                        </div>
                    </div>
                ))}
                {filtered.length === 0 && (
                    <p className="text-sm text-[var(--color-muted)] text-center py-8">No roles found.</p>
                )}
            </div>

            <Modal
                open={modalOpen}
                onClose={() => setModalOpen(false)}
                title={editingRole ? 'Edit Role' : 'New Role'}
                footer={
                    <>
                        <Button variant="ghost" size="sm" onClick={() => setModalOpen(false)}>Cancel</Button>
                        <Button size="sm" icon={<Save size={13} />} onClick={handleSave} disabled={!formName.trim()}>Save</Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <Input label="Role Name" value={formName} onChange={(e) => setFormName(e.target.value)} placeholder="e.g. Content Manager" />
                    <Input label="Description" value={formDesc} onChange={(e) => setFormDesc(e.target.value)} placeholder="Brief description" />
                </div>
            </Modal>

            <Modal
                open={!!deleteConfirm}
                onClose={() => setDeleteConfirm(null)}
                title="Delete Role"
                size="sm"
                footer={
                    <>
                        <Button variant="ghost" size="sm" onClick={() => setDeleteConfirm(null)}>Cancel</Button>
                        <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={() => deleteConfirm && handleDelete(deleteConfirm)}>Delete</Button>
                    </>
                }
            >
                <p className="text-sm text-[var(--color-muted)]">
                    Are you sure you want to delete this role? Users assigned to it will lose its permissions.
                </p>
            </Modal>
        </div>
    );
}

// ── Permissions Tab ─────────────────────────────────────────────────────────────

function PermissionsTab() {
    const { notify } = useToast();
    const { permissions, addPermission, updatePermission, deletePermission } = useRBAC();
    const [search, setSearch] = useState('');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingPerm, setEditingPerm] = useState<number | undefined>();
    const [formName, setFormName] = useState('');
    const [formGroup, setFormGroup] = useState('');
    const [formDesc, setFormDesc] = useState('');
    const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);

    const groups = useMemo(() => permissionGroups(permissions), [permissions]);
    const groupNames = Object.keys(groups).sort();

    const filtered = permissions.filter((p) =>
        p.name.toLowerCase().includes(search.toLowerCase()) ||
        p.slug.toLowerCase().includes(search.toLowerCase()) ||
        p.group.toLowerCase().includes(search.toLowerCase())
    );
    const filteredGroups = useMemo(() => permissionGroups(filtered), [filtered]);

    const openCreate = () => {
        setEditingPerm(undefined);
        setFormName('');
        setFormGroup(groupNames[0] ?? '');
        setFormDesc('');
        setModalOpen(true);
    };

    const openEdit = (perm: Permission) => {
        setEditingPerm(perm.id);
        setFormName(perm.name);
        setFormGroup(perm.group);
        setFormDesc('');
        setModalOpen(true);
    };

    const handleSave = async () => {
        if (!formName.trim()) return;
        try {
            if (editingPerm) {
                await updatePermission(editingPerm, { name: formName.trim(), slug: slugify(formName.trim()), group: formGroup.trim() || 'General' });
                notify('Permission updated', 'success');
            } else {
                await addPermission({ name: formName.trim(), slug: slugify(formName.trim()), group: formGroup.trim() || 'General' });
                notify('Permission created', 'success');
            }
            setModalOpen(false);
        } catch {
            notify('Failed to save permission', 'danger');
        }
    };

    const handleDelete = async (id: number) => {
        try {
            await deletePermission(id);
            setDeleteConfirm(null);
            notify('Permission deleted', 'info');
        } catch {
            notify('Failed to delete permission', 'danger');
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-3">
                <div className="flex-1">
                    <Input
                        icon={<Search size={14} />}
                        placeholder="Search permissions..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <Button size="sm" icon={<Plus size={14} />} onClick={openCreate}>New Permission</Button>
            </div>

            <div className="space-y-4">
                {groupNames.map((groupName) => {
                    const perms = filteredGroups[groupName] ?? [];
                    if (perms.length === 0) return null;
                    return (
                        <div key={groupName}>
                            <div className="flex items-center gap-2 mb-2">
                                <Lock size={13} className="text-[var(--color-muted)]" />
                                <h3 className="text-xs font-semibold text-[var(--color-muted)] uppercase tracking-wider">{groupName}</h3>
                                <Badge tone="neutral" className="text-[10px]">{perms.length}</Badge>
                            </div>
                            <div className="space-y-1">
                                {perms.map((perm) => (
                                    <div key={perm.id} className="flex items-center gap-3 p-2.5 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] hover:bg-[var(--color-surface-2)] transition-colors group">
                                        <GripVertical size={14} className="text-[var(--color-muted)] opacity-40" />
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium text-[var(--color-text)]">{perm.name}</span>
                                                <code className="text-[10px] text-[var(--color-muted)] bg-[var(--color-surface-2)] px-1.5 py-0.5 rounded font-mono">{perm.slug}</code>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <Button variant="ghost" size="sm" icon={<Edit2 size={12} />} onClick={() => openEdit(perm)} />
                                            <Button variant="ghost" size="sm" icon={<Trash2 size={12} />} onClick={() => setDeleteConfirm(perm.id)} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                })}
                {filtered.length === 0 && (
                    <p className="text-sm text-[var(--color-muted)] text-center py-8">No permissions found.</p>
                )}
            </div>

            <Modal
                open={modalOpen}
                onClose={() => setModalOpen(false)}
                title={editingPerm ? 'Edit Permission' : 'New Permission'}
                footer={
                    <>
                        <Button variant="ghost" size="sm" onClick={() => setModalOpen(false)}>Cancel</Button>
                        <Button size="sm" icon={<Save size={13} />} onClick={handleSave} disabled={!formName.trim()}>Save</Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <Input label="Permission Name" value={formName} onChange={(e) => setFormName(e.target.value)} placeholder="e.g. View Reports" />
                    <Input label="Group" value={formGroup} onChange={(e) => setFormGroup(e.target.value)} placeholder="e.g. Reports" />
                </div>
            </Modal>

            <Modal
                open={!!deleteConfirm}
                onClose={() => setDeleteConfirm(null)}
                title="Delete Permission"
                size="sm"
                footer={
                    <>
                        <Button variant="ghost" size="sm" onClick={() => setDeleteConfirm(null)}>Cancel</Button>
                        <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={() => deleteConfirm && handleDelete(deleteConfirm)}>Delete</Button>
                    </>
                }
            >
                <p className="text-sm text-[var(--color-muted)]">
                    Are you sure you want to delete this permission? Roles that use it will lose access.
                </p>
            </Modal>
        </div>
    );
}

// ── Assign Tab ──────────────────────────────────────────────────────────────────

function AssignTab() {
    const { notify } = useToast();
    const { roles, permissions, users, updateRole } = useRBAC();
    const [selectedRoleId, setSelectedRoleId] = useState(roles[0]?.id ?? 0);
    const [search, setSearch] = useState('');
    const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>(() => {
        const g: Record<string, boolean> = {};
        for (const k of Object.keys(permissionGroups(permissions))) g[k] = true;
        return g;
    });

    const selectedRole = roles.find((r) => r.id === selectedRoleId);
    const groups = useMemo(() => permissionGroups(permissions), [permissions]);
    const groupNames = Object.keys(groups).sort();

    const usersWithRole = users.filter((u) => u.roles.includes(selectedRole?.slug ?? '')).length;

    const selectedPermIds = useMemo(() => new Set(selectedRole?.permissions.map((p) => p.id) ?? []), [selectedRole]);

    const filteredGroupNames = groupNames.filter((groupName) => {
        return groups[groupName].some((p) =>
            p.name.toLowerCase().includes(search.toLowerCase()) ||
            p.slug.toLowerCase().includes(search.toLowerCase())
        );
    });

    const togglePerm = async (permId: number) => {
        if (!selectedRole) return;
        const currentIds = selectedRole.permissions.map((p) => p.id);
        const newIds = selectedPermIds.has(permId)
            ? currentIds.filter((id) => id !== permId)
            : [...currentIds, permId];
        try {
            await updateRole(selectedRole.id, { permission_ids: newIds });
        } catch {
            notify('Failed to update permissions', 'danger');
        }
    };

    const toggleGroup = async (groupName: string) => {
        if (!selectedRole) return;
        const groupPerms = groups[groupName].map((p) => p.id);
        const allAssigned = groupPerms.every((id) => selectedPermIds.has(id));
        const currentIds = selectedRole.permissions.map((p) => p.id);
        const newIds = allAssigned
            ? currentIds.filter((id) => !groupPerms.includes(id))
            : [...new Set([...currentIds, ...groupPerms])];
        try {
            await updateRole(selectedRole.id, { permission_ids: newIds });
        } catch {
            notify('Failed to update permissions', 'danger');
        }
    };

    const toggleExpand = (groupName: string) => {
        setExpandedGroups((prev) => ({ ...prev, [groupName]: !prev[groupName] }));
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-col sm:flex-row gap-3">
                    <Select
                        value={String(selectedRoleId)}
                        onChange={(e) => setSelectedRoleId(Number(e.target.value))}
                        options={roles.map((r) => ({ value: String(r.id), label: r.name }))}
                    />
                <div className="flex-1">
                    <Input
                        icon={<Search size={14} />}
                        placeholder="Search permissions..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
            </div>

            {selectedRole && (
                <div className="flex items-center gap-3 p-3 rounded-lg bg-[var(--color-surface-2)] border border-[var(--color-border)]">
                    <ShieldCheck size={18} className="text-[var(--color-accent-hover)] shrink-0" />
                    <div className="flex-1 min-w-0">
                        <span className="font-medium text-sm text-[var(--color-text)]">{selectedRole.name}</span>
                        <span className="text-xs text-[var(--color-muted)] ml-2">— {selectedRole.description}</span>
                    </div>
                    <div className="flex items-center gap-3 shrink-0">
                        <Badge tone="info" className="text-[10px]">
                            <Users size={10} className="mr-1" />{usersWithRole} user{usersWithRole !== 1 ? 's' : ''}
                        </Badge>
                        <Badge tone="primary">
                            {selectedPermIds.size} / {permissions.length} permissions
                        </Badge>
                    </div>
                </div>
            )}

            <div className="space-y-3">
                {filteredGroupNames.map((groupName) => {
                    const perms = groups[groupName];
                    const assignedCount = perms.filter((p) => selectedPermIds.has(p.id)).length;
                    const allAssigned = assignedCount === perms.length;
                    const someAssigned = assignedCount > 0 && !allAssigned;
                    const expanded = expandedGroups[groupName] ?? true;

                    return (
                        <div key={groupName} className="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] overflow-hidden">
                            <div className="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-[var(--color-surface-2)] transition-colors" onClick={() => toggleExpand(groupName)}>
                                <ChevronDown size={14} className={`text-[var(--color-muted)] transition-transform ${expanded ? 'rotate-180' : ''}`} />
                                <div className="flex-1 flex items-center gap-2">
                                    <Lock size={13} className="text-[var(--color-muted)]" />
                                    <span className="text-sm font-medium text-[var(--color-text)]">{groupName}</span>
                                    <span className="text-[10px] text-[var(--color-muted)]">{assignedCount}/{perms.length}</span>
                                </div>
                                <button
                                    type="button"
                                    onClick={(e) => { e.stopPropagation(); toggleGroup(groupName); }}
                                    className={`relative w-9 h-5 rounded-full transition-colors cursor-pointer ${allAssigned ? 'bg-[var(--color-accent)]' : someAssigned ? 'bg-[var(--color-accent)] opacity-50' : 'bg-[var(--color-border)]'}`}
                                >
                                    <span className={`absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform ${allAssigned ? 'left-[18px]' : 'left-0.5'}`} />
                                </button>
                            </div>
                            {expanded && (
                                <div className="border-t border-[var(--color-border)]">
                                    {perms
                                        .filter((p) => p.name.toLowerCase().includes(search.toLowerCase()) || p.slug.toLowerCase().includes(search.toLowerCase()))
                                        .map((perm) => {
                                            const isChecked = selectedPermIds.has(perm.id);
                                            return (
                                                <div
                                                    key={perm.id}
                                                    className="flex items-center gap-3 px-3 py-2 border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-surface-2)] transition-colors"
                                                >
                                                    <Switch checked={isChecked} onChange={() => togglePerm(perm.id)} />
                                                    <div className="flex-1 min-w-0">
                                                        <span className="text-sm text-[var(--color-text)]">{perm.name}</span>
                                                        <code className="ml-2 text-[10px] text-[var(--color-muted)] bg-[var(--color-surface-2)] px-1.5 py-0.5 rounded font-mono">{perm.slug}</code>
                                                    </div>
                                                    {isChecked && <Check size={14} className="text-green-500 shrink-0" />}
                                                </div>
                                            );
                                        })}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// ── Main Page ───────────────────────────────────────────────────────────────────

function RolesPermissionsPage() {
    const { roles, permissions, users, loading } = useRBAC();
    const [activeTab, setActiveTab] = useState('roles');

    const tabs = [
        { key: 'roles', label: 'Roles', icon: <ShieldCheck size={14} /> },
        { key: 'permissions', label: 'Permissions', icon: <Key size={14} /> },
        { key: 'assign', label: 'Assign', icon: <Lock size={14} /> },
    ];

    return (
        <div className="max-w-4xl space-y-6 py-2">
            <div>
                <h1 className="text-xl font-semibold text-[var(--color-text)] flex items-center gap-2">
                    <ShieldCheck size={22} />
                    Roles & Permissions
                </h1>
                <p className="text-sm text-[var(--color-muted)] mt-1">Manage roles, define permissions, and assign access control.</p>
            </div>

            <div className="grid grid-cols-4 gap-4">
                <Card>
                    <div className="text-center">
                        <p className="text-2xl font-bold text-[var(--color-text)]">{loading ? '...' : roles.length}</p>
                        <p className="text-xs text-[var(--color-muted)] mt-1">Roles</p>
                    </div>
                </Card>
                <Card>
                    <div className="text-center">
                        <p className="text-2xl font-bold text-[var(--color-text)]">{loading ? '...' : permissions.length}</p>
                        <p className="text-xs text-[var(--color-muted)] mt-1">Permissions</p>
                    </div>
                </Card>
                <Card>
                    <div className="text-center">
                        <p className="text-2xl font-bold text-green-600">{loading ? '...' : Object.keys(permissionGroups(permissions)).length}</p>
                        <p className="text-xs text-[var(--color-muted)] mt-1">Groups</p>
                    </div>
                </Card>
                <Card>
                    <div className="text-center">
                        <p className="text-2xl font-bold text-[var(--color-accent-hover)]">{loading ? '...' : users.length}</p>
                        <p className="text-xs text-[var(--color-muted)] mt-1">Users</p>
                    </div>
                </Card>
            </div>

            {loading && (
                <Card>
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Spinner size={24} />
                        <span className="text-sm text-[var(--color-muted)]">Loading...</span>
                    </div>
                </Card>
            )}

            {!loading && (
                <Card className="[&_.nx-card-body]:p-0">
                    <div className="px-4 pt-3 border-b border-[var(--color-border)]">
                        <Tabs tabs={tabs} active={activeTab} onChange={setActiveTab} />
                    </div>
                    <div className="p-4">
                        {activeTab === 'roles' && <RolesTab userCount={users.length} />}
                        {activeTab === 'permissions' && <PermissionsTab />}
                        {activeTab === 'assign' && <AssignTab />}
                    </div>
                </Card>
            )}
        </div>
    );
}

export default function Page() {
    return (
        <ToastProvider>
            <RolesPermissionsPage />
        </ToastProvider>
    );
}
