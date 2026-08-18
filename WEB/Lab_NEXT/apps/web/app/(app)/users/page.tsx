'use client';

import { useState } from 'react';
import {
    Users,
    Plus,
    Search,
    Edit2,
    Trash2,
    Shield,
    Mail,
    Calendar,
    X,
    Save,
    UserCheck,
    UserX,
} from 'lucide-react';
import { Button, Card, Input, Select, Modal, Switch, Spinner, ToastProvider, useToast, DropdownMenu } from '@ctlab/ctlab-ui';
import { useRBAC, type RBACUser } from '@/components/RBACProvider';

function RoleBadge({ roleName }: { roleName: string }) {
    return (
        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--color-accent-soft)] text-[var(--color-accent-hover)]">
            <Shield size={10} />
            {roleName}
        </span>
    );
}

function StatusBadge({ active }: { active: boolean }) {
    return (
        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${active
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
            }`}>
            {active ? <UserCheck size={10} /> : <UserX size={10} />}
            {active ? 'Active' : 'Inactive'}
        </span>
    );
}

function UserForm({ user, roles, onSave, onCancel }: {
    user?: RBACUser;
    roles: { id: number; name: string; slug: string }[];
    onSave: (data: { name: string; email: string; password?: string; role_id: number; is_active: boolean }) => void;
    onCancel: () => void;
}) {
    const [name, setName] = useState(user?.name ?? '');
    const [email, setEmail] = useState(user?.email ?? '');
    const [password, setPassword] = useState('');
    const [roleId, setRoleId] = useState<number>(user?.roles.length ? roles.find(r => r.slug === user.roles[0])?.id ?? roles[0]?.id ?? 0 : roles[0]?.id ?? 0);
    const [isActive, setIsActive] = useState(user?.is_active ?? true);

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                    label="Full Name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="John Doe"
                />
                <Input
                    label="Email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="user@ctlabs.io"
                />
                <Input
                    label={user ? 'New Password (leave blank to keep)' : 'Password'}
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder={user ? '••••••••' : 'Minimum 12 characters'}
                />
                <Select
                    label="Role"
                    value={String(roleId)}
                    onChange={(e) => setRoleId(Number(e.target.value))}
                    options={roles.map((r) => ({ value: String(r.id), label: r.name }))}
                />
                <div className="flex items-center gap-3 pt-6">
                    <Switch
                        checked={isActive}
                        onChange={(v) => setIsActive(v)}
                    />
                    <span className="text-sm text-[var(--color-text)]">Active</span>
                </div>
            </div>
            <div className="flex justify-end gap-2 pt-2">
                <Button variant="ghost" size="sm" icon={<X size={14} />} onClick={onCancel}>
                    Cancel
                </Button>
                <Button
                    size="sm"
                    icon={<Save size={14} />}
                    onClick={() => onSave({
                        name,
                        email,
                        ...(password ? { password } : {}),
                        role_id: roleId,
                        is_active: isActive,
                    })}
                    disabled={!name.trim() || !email.trim() || !roleId || (!user && !password)}
                >
                    {user ? 'Update' : 'Create'}
                </Button>
            </div>
        </div>
    );
}

function UserManagement() {
    const { notify } = useToast();
    const { users, roles, currentUser, addUser, updateUser, deleteUser, loading } = useRBAC();
    const [search, setSearch] = useState('');
    const [filterRole, setFilterRole] = useState('all');
    const [showForm, setShowForm] = useState(false);
    const [editingUser, setEditingUser] = useState<RBACUser | undefined>();
    const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);

    const getRoleName = (roleSlugs: string[]) => {
        if (!roleSlugs.length) return 'No Role';
        return roles.find((r) => r.slug === roleSlugs[0])?.name ?? roleSlugs[0];
    };

    const filtered = users.filter((u) => {
        const matchSearch = u.name.toLowerCase().includes(search.toLowerCase()) || u.email.toLowerCase().includes(search.toLowerCase());
        const matchRole = filterRole === 'all' || u.roles.includes(filterRole);
        return matchSearch && matchRole;
    });

    const handleCreate = async (data: { name: string; email: string; password?: string; role_id: number; is_active: boolean }) => {
        try {
            await addUser(data as Parameters<typeof addUser>[0]);
            setShowForm(false);
            notify('User created successfully', 'success');
        } catch {
            notify('Failed to create user', 'danger');
        }
    };

    const handleUpdate = async (data: { name: string; email: string; password?: string; role_id: number; is_active: boolean }) => {
        if (!editingUser) return;
        try {
            await updateUser(editingUser.id, data);
            setEditingUser(undefined);
            notify('User updated successfully', 'success');
        } catch {
            notify('Failed to update user', 'danger');
        }
    };

    const handleDelete = async (id: number) => {
        try {
            await deleteUser(id);
            setDeleteConfirm(null);
            notify('User deleted', 'info');
        } catch {
            notify('Failed to delete user', 'danger');
        }
    };

    const stats = {
        total: users.length,
        active: users.filter((u) => u.is_active).length,
    };

    return (
        <div className="max-w-4xl space-y-6 py-2">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-[var(--color-text)] flex items-center gap-2">
                        <Users size={22} />
                        User Management
                    </h1>
                    <p className="text-sm text-[var(--color-muted)] mt-1">Manage user accounts, roles, and permissions.</p>
                </div>
                <Button
                    size="sm"
                    icon={<Plus size={14} />}
                    onClick={() => { setShowForm(true); setEditingUser(undefined); }}
                >
                    Add User
                </Button>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <Card>
                    <div className="text-center">
                        <p className="text-2xl font-bold text-[var(--color-text)]">{loading ? '...' : stats.total}</p>
                        <p className="text-xs text-[var(--color-muted)] mt-1">Total Users</p>
                    </div>
                </Card>
                <Card>
                    <div className="text-center">
                        <p className="text-2xl font-bold text-green-600">{loading ? '...' : stats.active}</p>
                        <p className="text-xs text-[var(--color-muted)] mt-1">Active</p>
                    </div>
                </Card>
            </div>

            {loading && (
                <Card>
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Spinner size={24} />
                        <span className="text-sm text-[var(--color-muted)]">Loading users...</span>
                    </div>
                </Card>
            )}

            {!loading && (showForm || editingUser) && (
                <Card title={editingUser ? 'Edit User' : 'New User'}>
                    <UserForm
                        user={editingUser}
                        roles={roles}
                        onSave={editingUser ? handleUpdate : handleCreate}
                        onCancel={() => { setShowForm(false); setEditingUser(undefined); }}
                    />
                </Card>
            )}

            <Card title="Users" subtitle={`${filtered.length} of ${users.length} users`}>
                <div className="flex flex-col sm:flex-row gap-3 mb-4">
                    <div className="flex-1">
                        <Input
                            icon={<Search size={14} />}
                            placeholder="Search users..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <Select
                        value={filterRole}
                        onChange={(e) => setFilterRole(e.target.value)}
                        options={[
                            { value: 'all', label: 'All Roles' },
                            ...roles.map((r) => ({ value: r.slug, label: r.name })),
                        ]}
                    />
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-[var(--color-border)]">
                                <th className="text-left py-2 px-3 text-xs font-semibold text-[var(--color-muted)] uppercase tracking-wider">User</th>
                                <th className="text-left py-2 px-3 text-xs font-semibold text-[var(--color-muted)] uppercase tracking-wider">Role</th>
                                <th className="text-left py-2 px-3 text-xs font-semibold text-[var(--color-muted)] uppercase tracking-wider">Status</th>
                                <th className="text-left py-2 px-3 text-xs font-semibold text-[var(--color-muted)] uppercase tracking-wider hidden md:table-cell">Created</th>
                                <th className="py-2 px-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map((user) => (
                                <tr key={user.id} className={`border-b border-[var(--color-border)] last:border-0 transition-colors ${currentUser?.id === user.id ? 'bg-[var(--color-accent-soft)]' : 'hover:bg-[var(--color-surface-2)]'}`}>
                                    <td className="py-3 px-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-8 h-8 rounded-full bg-[var(--color-accent-soft)] flex items-center justify-center text-xs font-bold text-[var(--color-accent-hover)]">
                                                {user.name.split(' ').map(n => n[0]).join('')}
                                            </div>
                                            <div>
                                                <p className="font-medium text-[var(--color-text)] flex items-center gap-2">
                                                    {user.name}
                                                    {currentUser?.id === user.id && (
                                                        <span className="text-[9px] font-bold px-1.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">YOU</span>
                                                    )}
                                                </p>
                                                <p className="text-xs text-[var(--color-muted)] flex items-center gap-1">
                                                    <Mail size={10} />
                                                    {user.email}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="py-3 px-3"><RoleBadge roleName={getRoleName(user.roles)} /></td>
                                    <td className="py-3 px-3"><StatusBadge active={user.is_active} /></td>
                                    <td className="py-3 px-3 hidden md:table-cell">
                                        <span className="text-xs text-[var(--color-muted)] flex items-center gap-1">
                                            <Calendar size={10} />
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </span>
                                    </td>
                                    <td className="py-3 px-3 relative">
                                        <DropdownMenu
                                            trigger={
                                                <button className="p-1 rounded hover:bg-[var(--color-surface-2)] text-[var(--color-muted)] cursor-pointer">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="3" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13" r="1.5"/></svg>
                                                </button>
                                            }
                                            items={[
                                                { key: 'edit', label: 'Edit', icon: <Edit2 size={13} />, onSelect: () => setEditingUser(user) },
                                                { key: 'delete', label: 'Delete', icon: <Trash2 size={13} />, danger: true, onSelect: () => setDeleteConfirm(user.id) },
                                            ]}
                                            align="end"
                                        />
                                    </td>
                                </tr>
                            ))}
                            {filtered.length === 0 && !loading && (
                                <tr>
                                    <td colSpan={5} className="py-8 text-center text-sm text-[var(--color-muted)]">
                                        No users found matching your criteria.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Modal
                open={!!deleteConfirm}
                onClose={() => setDeleteConfirm(null)}
                title="Delete User"
                size="sm"
                footer={
                    <>
                        <Button variant="ghost" size="sm" onClick={() => setDeleteConfirm(null)}>Cancel</Button>
                        <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={() => deleteConfirm && handleDelete(deleteConfirm)}>Delete</Button>
                    </>
                }
            >
                <p className="text-sm text-[var(--color-muted)]">
                    Are you sure you want to delete this user?
                </p>
            </Modal>
        </div>
    );
}

export default function UsersPage() {
    return (
        <ToastProvider>
            <UserManagement />
        </ToastProvider>
    );
}
