'use client';

import { useState } from 'react';
import Link from 'next/link';
import { LogIn } from 'lucide-react';
import { Button, Card, Input } from '@ctlab/ctlab-ui';
import { api, saveToken } from '@/lib/api';

export default function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    async function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        setLoading(true);
        try {
            const r: any = await api('/api/v1/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password }),
            });
            if (r?.errors) setError(r.errors.message ?? 'Login failed');
            else if (r?.meta?.token) {
                saveToken(r.meta.token);
                window.location.replace('/dashboard/');
            } else setError('Unexpected response');
        } catch {
            setError('Network error');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="h-full min-h-screen flex items-center justify-center p-6">
            <Card className="w-full max-w-sm">
                <div className="mb-6">
                    <div className="flex items-center gap-2 mb-2">
                        <span className="w-2.5 h-2.5 rounded-full bg-[var(--color-accent)]" />
                        <span className="font-semibold tracking-tight">CTLabs</span>
                    </div>
                    <h1 className="text-xl font-semibold tracking-tight">Sign in</h1>
                    <p className="text-sm text-[var(--color-muted)]">Welcome back to your personal software ecosystem.</p>
                </div>

                <form onSubmit={onSubmit} className="space-y-4">
                    <Input label="Email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} placeholder="you@example.com" />
                    <Input label="Password" type="password" required value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" />
                    {error && <p className="text-sm text-[var(--color-bad)]">{error}</p>}
                    <Button type="submit" block loading={loading} icon={<LogIn size={16} />}>
                        Sign in
                    </Button>
                </form>

                <p className="mt-6 text-sm text-[var(--color-muted)]">
                    No account?{' '}
                    <Link href="/register" className="text-[var(--color-accent-hover)] hover:underline">
                        Create one
                    </Link>
                </p>
            </Card>
        </div>
    );
}
