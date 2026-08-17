'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { UserPlus } from 'lucide-react';
import { Button, Card, Input } from '@ctlab/ctlab-ui';
import { api, saveToken } from '@/lib/api';

export default function RegisterPage() {
    const router = useRouter();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [message, setMessage] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    async function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        setMessage(null);
        setError(null);
        setLoading(true);
        try {
            const r: any = await api('/api/v1/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password, password_confirmation: password }),
            });
            if (r?.errors) setError(r.errors.message ?? 'Registration failed');
            else if (r?.meta?.token) {
                saveToken(r.meta.token);
                setMessage(`Account created for ${r.data?.email}`);
                window.setTimeout(() => router.push('/dashboard'), 300);
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
                    <h1 className="text-xl font-semibold tracking-tight">Create your account</h1>
                    <p className="text-sm text-[var(--color-muted)]">One identity across your entire ecosystem.</p>
                </div>

                <form onSubmit={onSubmit} className="space-y-4">
                    <Input label="Name" required value={name} onChange={(e) => setName(e.target.value)} placeholder="Ada Lovelace" />
                    <Input label="Email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} placeholder="you@example.com" />
                    <Input label="Password (min 12 chars)" type="password" required minLength={12} value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" />
                    {message && <p className="text-sm text-[var(--color-ok)]">{message}</p>}
                    {error && <p className="text-sm text-[var(--color-bad)]">{error}</p>}
                    <Button type="submit" block loading={loading} icon={<UserPlus size={16} />}>
                        Create account
                    </Button>
                </form>

                <p className="mt-6 text-sm text-[var(--color-muted)]">
                    Already registered?{' '}
                    <Link href="/login" className="text-[var(--color-accent-hover)] hover:underline">
                        Sign in
                    </Link>
                </p>
            </Card>
        </div>
    );
}
