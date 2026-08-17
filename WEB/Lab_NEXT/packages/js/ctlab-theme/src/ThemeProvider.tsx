'use client';

import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';

export type ThemeMode = 'dark' | 'light';

const STORAGE_KEY = 'ctlab-theme';

function getInitialMode(): ThemeMode {
    if (typeof window === 'undefined') return 'dark';
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') return stored;
        if (window.matchMedia?.('(prefers-color-scheme: light)').matches) return 'light';
    } catch {
        /* storage unavailable — fall through to default */
    }
    return 'dark';
}

interface ThemeContextValue {
    mode: ThemeMode;
    resolved: ThemeMode;
    setMode: (mode: ThemeMode) => void;
    toggle: () => void;
}

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeProvider({
    children,
    defaultMode = 'dark',
}: {
    children: ReactNode;
    defaultMode?: ThemeMode;
}) {
    const [mode, setModeState] = useState<ThemeMode>(defaultMode);

    useEffect(() => {
        setModeState(getInitialMode());
    }, []);

    useEffect(() => {
        document.documentElement.setAttribute('data-ctlab-theme', mode);
        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch {
            /* ignore storage errors */
        }
    }, [mode]);

    const setMode = useCallback((next: ThemeMode) => setModeState(next), []);
    const toggle = useCallback(() => setModeState((m) => (m === 'dark' ? 'light' : 'dark')), []);

    const value = useMemo(
        () => ({ mode, resolved: mode, setMode, toggle }),
        [mode, setMode, toggle],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
    const ctx = useContext(ThemeContext);
    if (!ctx) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return ctx;
}

/**
 * Inline script to set the theme attribute before first paint, avoiding a
 * flash of the wrong theme on load. Rendered in <head> by the host app.
 */
export const noFlashScript = `(function(){try{var t=localStorage.getItem('${STORAGE_KEY}');if(t!=='dark'&&t!=='light'){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'}document.documentElement.setAttribute('data-ctlab-theme',t);}catch(e){}})();`;
