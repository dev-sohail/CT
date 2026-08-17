export const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'http://api.lab.ct.local';

export async function api<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        ...(options.headers as Record<string, string>),
    };

    if (typeof window !== 'undefined') {
        const token = window.localStorage.getItem('ctlab_token');
        if (token) headers.Authorization = `Bearer ${token}`;
    }

    const res = await fetch(`${API_BASE}${path}`, { ...options, headers });

    if (res.status === 401 && typeof window !== 'undefined') {
        window.localStorage.removeItem('ctlab_token');
        if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
            window.location.replace('/login');
        }
    }

    const body = await res.json().catch(() => null);
    return body as T;
}

export function saveToken(token: string): void {
    window.localStorage.setItem('ctlab_token', token);
}

export function clearToken(): void {
    window.localStorage.removeItem('ctlab_token');
}
