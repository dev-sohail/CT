'use client';

import { createContext, useContext, useEffect, useState, useCallback, useMemo, type ReactNode } from 'react';

export interface SiteSettings {
    siteName: string;
    tagline: string;
    logoDataUrl: string;
    faviconDataUrl: string;
    primaryColor: string;
    accentColor: string;
    defaultTheme: 'light' | 'dark' | 'system';
    compactMode: boolean;
    showModuleCounts: boolean;
    sidebarCollapsed: boolean;
    dateFormat: string;
    timeFormat: string;
    language: string;
    timezone: string;
}

const STORAGE_KEY = 'ctlab_site_settings';

const DEFAULTS: SiteSettings = {
    siteName: 'CTLabs',
    tagline: 'Life OS Platform',
    logoDataUrl: '',
    faviconDataUrl: '',
    primaryColor: '#6366f1',
    accentColor: '#818cf8',
    defaultTheme: 'dark',
    compactMode: false,
    showModuleCounts: true,
    sidebarCollapsed: false,
    dateFormat: 'YYYY-MM-DD',
    timeFormat: '24h',
    language: 'en',
    timezone: 'UTC',
};

function loadSettings(): SiteSettings {
    if (typeof window === 'undefined') return DEFAULTS;
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? { ...DEFAULTS, ...JSON.parse(raw) } : DEFAULTS;
    } catch {
        return DEFAULTS;
    }
}

function hexToHsl(hex: string): string {
    let r = 0, g = 0, b = 0;
    if (hex.length === 4) {
        r = parseInt(hex[1] + hex[1], 16);
        g = parseInt(hex[2] + hex[2], 16);
        b = parseInt(hex[3] + hex[3], 16);
    } else if (hex.length === 7) {
        r = parseInt(hex.slice(1, 3), 16);
        g = parseInt(hex.slice(3, 5), 16);
        b = parseInt(hex.slice(5, 7), 16);
    }
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h = 0, s = 0;
    const l = (max + min) / 2;
    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
        else if (max === g) h = ((b - r) / d + 2) / 6;
        else h = ((r - g) / d + 4) / 6;
    }
    return `${Math.round(h * 360)} ${Math.round(s * 100)}% ${Math.round(l * 100)}%`;
}

function computeHoverColor(hex: string): string {
    let r = parseInt(hex.slice(1, 3), 16);
    let g = parseInt(hex.slice(3, 5), 16);
    let b = parseInt(hex.slice(5, 7), 16);
    r = Math.min(255, r + 25);
    g = Math.min(255, g + 25);
    b = Math.min(255, b + 25);
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
}

function applySettings(s: SiteSettings) {
    const root = document.documentElement;

    // Apply custom colors as CSS variable overrides
    if (s.primaryColor && s.primaryColor !== DEFAULTS.primaryColor) {
        root.style.setProperty('--ctlab-primary', hexToHsl(s.primaryColor));
        root.style.setProperty('--ctlab-primary-hover', hexToHsl(computeHoverColor(s.primaryColor)));
        root.style.setProperty('--ctlab-primary-fg', '0 0% 100%');
    } else {
        root.style.removeProperty('--ctlab-primary');
        root.style.removeProperty('--ctlab-primary-hover');
        root.style.removeProperty('--ctlab-primary-fg');
    }

    if (s.accentColor && s.accentColor !== DEFAULTS.accentColor) {
        // accentColor is used for info / secondary highlights
        root.style.setProperty('--ctlab-info', hexToHsl(s.accentColor));
    } else {
        root.style.removeProperty('--ctlab-info');
    }

    // Favicon
    const existingLink = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
    if (s.faviconDataUrl) {
        if (existingLink) {
            existingLink.href = s.faviconDataUrl;
        } else {
            const link = document.createElement('link');
            link.rel = 'icon';
            link.href = s.faviconDataUrl;
            document.head.appendChild(link);
        }
    } else if (existingLink) {
        existingLink.href = '/favicon.ico';
    }

    // Document title
    if (s.siteName) {
        document.title = s.siteName;
    }

    // Meta description
    const metaDesc = document.querySelector<HTMLMetaElement>('meta[name="description"]');
    if (metaDesc && s.tagline) {
        metaDesc.content = `${s.siteName} — ${s.tagline}`;
    }
}

interface SettingsContextValue {
    settings: SiteSettings;
    update: <K extends keyof SiteSettings>(key: K, value: SiteSettings[K]) => void;
    reset: () => void;
}

const SettingsContext = createContext<SettingsContextValue | null>(null);

export function SettingsProvider({ children }: { children: ReactNode }) {
    const [settings, setSettings] = useState<SiteSettings>(DEFAULTS);
    const [ready, setReady] = useState(false);

    // Load from localStorage on mount
    useEffect(() => {
        const loaded = loadSettings();
        setSettings(loaded);
        setReady(true);
    }, []);

    // Apply whenever settings change
    useEffect(() => {
        if (!ready) return;
        applySettings(settings);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
    }, [settings, ready]);

    // Sync across tabs
    useEffect(() => {
        function onStorage(e: StorageEvent) {
            if (e.key !== STORAGE_KEY || !e.newValue) return;
            try {
                const parsed = JSON.parse(e.newValue) as SiteSettings;
                setSettings(parsed);
            } catch { /* ignore */ }
        }
        window.addEventListener('storage', onStorage);
        return () => window.removeEventListener('storage', onStorage);
    }, []);

    const update = useCallback(<K extends keyof SiteSettings>(key: K, value: SiteSettings[K]) => {
        setSettings((prev) => ({ ...prev, [key]: value }));
    }, []);

    const reset = useCallback(() => {
        setSettings(DEFAULTS);
    }, []);

    const value = useMemo(() => ({ settings, update, reset }), [settings, update, reset]);

    return (
        <SettingsContext.Provider value={value}>
            {children}
        </SettingsContext.Provider>
    );
}

export function useSettings(): SettingsContextValue {
    const ctx = useContext(SettingsContext);
    if (!ctx) {
        throw new Error('useSettings must be used within a SettingsProvider');
    }
    return ctx;
}
