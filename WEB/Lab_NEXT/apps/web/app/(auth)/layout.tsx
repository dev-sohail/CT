import type { Metadata } from 'next';
import '@/app/globals.css';

export const metadata: Metadata = { title: 'Sign in · CTLabs' };

export default function AuthLayout({ children }: { children: React.ReactNode }) {
    return <>{children}</>;
}
