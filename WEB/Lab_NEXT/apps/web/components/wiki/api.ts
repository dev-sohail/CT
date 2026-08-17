import { api } from '@/lib/api';

interface Envelope<T> {
    data: T;
    meta?: any;
    errors?: { code?: string; message?: string; details?: any } | null;
}

function unwrap<T>(envelope: Envelope<T>): T {
    if (envelope?.errors) {
        throw new Error(envelope.errors.message ?? 'API error');
    }
    return envelope.data;
}

export const wikiApi = {
    async list<T>(path: string, params?: Record<string, string | number | boolean>): Promise<T[]> {
        const qs = params
            ? '?' +
              Object.entries(params)
                  .filter(([, v]) => v !== undefined && v !== null && v !== '')
                  .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`)
                  .join('&')
            : '';
        return unwrap(await api<Envelope<T[]>>(`/api/v1${path}${qs}`));
    },

    async get<T>(path: string): Promise<T> {
        return unwrap(await api<Envelope<T>>(`/api/v1${path}`));
    },

    async post<T>(path: string, body?: unknown): Promise<T> {
        return unwrap(
            await api<Envelope<T>>(`/api/v1${path}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: body === undefined ? undefined : JSON.stringify(body),
            })
        );
    },

    async put<T>(path: string, body: unknown): Promise<T> {
        return unwrap(
            await api<Envelope<T>>(`/api/v1${path}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            })
        );
    },

    async del<T = unknown>(path: string): Promise<T> {
        return unwrap(
            await api<Envelope<T>>(`/api/v1${path}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
            })
        );
    },

    async delBody<T = unknown>(path: string, body?: unknown): Promise<T> {
        return unwrap(
            await api<Envelope<T>>(`/api/v1${path}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: body === undefined ? undefined : JSON.stringify(body),
            })
        );
    },
};

export function formatDate(value?: string | null): string {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString();
}

export function formatDateTime(value?: string | null): string {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export function relTime(value?: string | null): string {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return '—';
    const diff = Date.now() - d.getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return `${days}d ago`;
    return d.toLocaleDateString();
}

export function typeLabel(type?: string): string {
    if (!type) return 'Page';
    return type.charAt(0).toUpperCase() + type.slice(1);
}

export function statusLabel(status?: string): string {
    switch (status) {
        case 'in_progress': return 'In Progress';
        case 'done': return 'Done';
        case 'archived': return 'Archived';
        default: return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Active';
    }
}

export function difficultyLabel(difficulty?: string): string {
    if (!difficulty) return '';
    return difficulty.charAt(0).toUpperCase() + difficulty.slice(1);
}
