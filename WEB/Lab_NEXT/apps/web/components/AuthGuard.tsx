'use client';

import { useEffect, useState } from 'react';

const PUBLIC_PATHS = ['/login', '/register'];

export default function AuthGuard({ children }: { children: React.ReactNode }) {
    const [ready, setReady] = useState(false);

    useEffect(() => {
        const path = window.location.pathname.replace(/\/+$/, '') || '/';
        const isPublic = PUBLIC_PATHS.includes(path);
        const token = window.localStorage.getItem('ctlab_token');

        if (!isPublic && !token) {
            window.location.replace('/login');
            return;
        }
        setReady(true);
    }, []);

    if (!ready) return null;
    return <>{children}</>;
}
