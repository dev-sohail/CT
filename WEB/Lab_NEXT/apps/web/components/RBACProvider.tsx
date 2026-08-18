'use client';

import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { api } from '@/lib/api';

// ── Types (matching API shapes) ─────────────────────────────────────────────────

export interface Permission {
    id: number;
    name: string;
    slug: string;
    group: string;
}

export interface Role {
    id: number;
    name: string;
    slug: string;
    description: string;
    is_system: boolean;
    permissions: Permission[];
}

export interface RBACUser {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
    permissions: string[];
    created_at: string;
    updated_at: string;
}

// ── API response types ─────────────────────────────────────────────────────────

interface ApiUser {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
    permissions: string[];
    created_at: string;
    updated_at: string;
}

interface ApiRole {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    permissions: Permission[];
    created_at: string;
    updated_at: string;
}

interface ApiPermission {
    id: number;
    name: string;
    slug: string;
    group: string;
    created_at: string;
    updated_at: string;
}

// ── Context ─────────────────────────────────────────────────────────────────────

interface RBACContextValue {
    users: RBACUser[];
    roles: Role[];
    permissions: Permission[];
    currentUser: RBACUser | undefined;
    currentRole: Role | undefined;
    currentPermissionSlugs: Set<string>;
    loading: boolean;

    hasPermission: (slug: string) => boolean;

    // Users
    addUser: (data: { name: string; email: string; password: string; role_id: number; is_active?: boolean }) => Promise<void>;
    updateUser: (id: number, data: { name?: string; email?: string; password?: string; role_id?: number; is_active?: boolean }) => Promise<void>;
    deleteUser: (id: number) => Promise<void>;

    // Roles
    addRole: (data: { name: string; slug: string; description?: string; permission_ids?: number[] }) => Promise<void>;
    updateRole: (id: number, data: { name?: string; slug?: string; description?: string; permission_ids?: number[] }) => Promise<void>;
    deleteRole: (id: number) => Promise<void>;

    // Permissions
    addPermission: (data: { name: string; slug: string; group: string; description?: string }) => Promise<void>;
    updatePermission: (id: number, data: { name?: string; slug?: string; group?: string; description?: string }) => Promise<void>;
    deletePermission: (id: number) => Promise<void>;

    // Refresh
    refresh: () => Promise<void>;
}

const RBACContext = createContext<RBACContextValue | null>(null);

// ── Provider ────────────────────────────────────────────────────────────────────

export function RBACProvider({ children }: { children: ReactNode }) {
    const [users, setUsers] = useState<RBACUser[]>([]);
    const [roles, setRoles] = useState<Role[]>([]);
    const [permissions, setPermissions] = useState<Permission[]>([]);
    const [currentUser, setCurrentUser] = useState<RBACUser | undefined>();
    const [loading, setLoading] = useState(true);

    const fetchAll = useCallback(async () => {
        const token = typeof window !== 'undefined' ? window.localStorage.getItem('ctlab_token') : null;
        if (!token) {
            setLoading(false);
            return;
        }
        setLoading(true);
        try {
            const [meRes, usersRes, rolesRes, permsRes] = await Promise.all([
                api<{ data: ApiUser }>('/api/v1/me'),
                api<{ data: ApiUser[] }>('/api/v1/users'),
                api<{ data: ApiRole[] }>('/api/v1/roles'),
                api<{ data: ApiPermission[] }>('/api/v1/permissions'),
            ]);

            if (meRes.data) setCurrentUser(meRes.data);
            if (usersRes.data) setUsers(usersRes.data);
            if (rolesRes.data) {
                setRoles(rolesRes.data.map((r) => ({
                    id: r.id,
                    name: r.name,
                    slug: r.slug,
                    description: r.description ?? '',
                    is_system: r.is_system,
                    permissions: r.permissions ?? [],
                })));
            }
            if (permsRes.data) {
                setPermissions(permsRes.data.map((p) => ({
                    id: p.id,
                    name: p.name,
                    slug: p.slug,
                    group: p.group,
                })));
            }
        } catch {
            // API might be unreachable
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { fetchAll(); }, [fetchAll]);

    const currentRole = useMemo(() => {
        if (!currentUser || !currentUser.roles.length) return undefined;
        return roles.find((r) => r.slug === currentUser.roles[0]);
    }, [currentUser, roles]);

    const currentPermissionSlugs = useMemo(() => {
        if (currentUser?.permissions?.length) {
            return new Set(currentUser.permissions);
        }
        if (!currentRole) return new Set<string>();
        return new Set(currentRole.permissions.map((p) => p.slug));
    }, [currentRole, currentUser]);

    const hasPermission = useCallback((slug: string) => currentPermissionSlugs.has(slug), [currentPermissionSlugs]);

    // ── User Mutations ───────────────────────────────────────────────────────

    const addUser = useCallback(async (data: { name: string; email: string; password: string; role_id: number; is_active?: boolean }) => {
        await api('/api/v1/users', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        await fetchAll();
    }, [fetchAll]);

    const updateUser = useCallback(async (id: number, data: { name?: string; email?: string; password?: string; role_id?: number; is_active?: boolean }) => {
        await api(`/api/v1/users/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        await fetchAll();
    }, [fetchAll]);

    const deleteUser = useCallback(async (id: number) => {
        await api(`/api/v1/users/${id}`, { method: 'DELETE' });
        await fetchAll();
    }, [fetchAll]);

    // ── Role Mutations ───────────────────────────────────────────────────────

    const addRole = useCallback(async (data: { name: string; slug: string; description?: string; permission_ids?: number[] }) => {
        await api('/api/v1/roles', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        await fetchAll();
    }, [fetchAll]);

    const updateRole = useCallback(async (id: number, data: { name?: string; slug?: string; description?: string; permission_ids?: number[] }) => {
        await api(`/api/v1/roles/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        await fetchAll();
    }, [fetchAll]);

    const deleteRole = useCallback(async (id: number) => {
        await api(`/api/v1/roles/${id}`, { method: 'DELETE' });
        await fetchAll();
    }, [fetchAll]);

    // ── Permission Mutations ─────────────────────────────────────────────────

    const addPermission = useCallback(async (data: { name: string; slug: string; group: string; description?: string }) => {
        await api('/api/v1/permissions', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        await fetchAll();
    }, [fetchAll]);

    const updatePermission = useCallback(async (id: number, data: { name?: string; slug?: string; group?: string; description?: string }) => {
        await api(`/api/v1/permissions/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        await fetchAll();
    }, [fetchAll]);

    const deletePermission = useCallback(async (id: number) => {
        await api(`/api/v1/permissions/${id}`, { method: 'DELETE' });
        await fetchAll();
    }, [fetchAll]);

    const value = useMemo<RBACContextValue>(() => ({
        users, roles, permissions, currentUser, currentRole, currentPermissionSlugs, loading,
        hasPermission,
        addUser, updateUser, deleteUser,
        addRole, updateRole, deleteRole,
        addPermission, updatePermission, deletePermission,
        refresh: fetchAll,
    }), [users, roles, permissions, currentUser, currentRole, currentPermissionSlugs, loading,
        hasPermission,
        addUser, updateUser, deleteUser,
        addRole, updateRole, deleteRole,
        addPermission, updatePermission, deletePermission, fetchAll]);

    return <RBACContext.Provider value={value}>{children}</RBACContext.Provider>;
}

export function useRBAC(): RBACContextValue {
    const ctx = useContext(RBACContext);
    if (!ctx) throw new Error('useRBAC must be used within an RBACProvider');
    return ctx;
}
