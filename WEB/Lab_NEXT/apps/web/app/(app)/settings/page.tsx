'use client';

import { Save, RotateCcw, Upload, X, Image } from 'lucide-react';
import { Button, Card, Input, Select, Switch, ToastProvider, useToast } from '@ctlab/ctlab-ui';
import { useTheme } from '@ctlab/ctlab-theme';
import { useRef, useEffect } from 'react';
import { useSettings } from '@/components/SettingsProvider';

function readAsDataUrl(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result as string);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function ImageUpload({ label, dataUrl, onUpload, onRemove, accept }: {
    label: string;
    dataUrl: string;
    onUpload: (dataUrl: string) => void;
    onRemove: () => void;
    accept: string;
}) {
    const inputRef = useRef<HTMLInputElement>(null);

    const handleChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            alert('File must be under 2 MB');
            return;
        }
        const dataUrl = await readAsDataUrl(file);
        onUpload(dataUrl);
        e.target.value = '';
    };

    return (
        <div>
            <label className="block text-sm font-medium text-[var(--color-text)] mb-1.5">{label}</label>
            <div className="flex items-center gap-3">
                <div
                    className="w-16 h-16 rounded-lg border-2 border-dashed border-[var(--color-border)] bg-[var(--color-surface-2)] flex items-center justify-center overflow-hidden shrink-0 cursor-pointer hover:border-[var(--color-accent)] transition-colors"
                    onClick={() => inputRef.current?.click()}
                >
                    {dataUrl ? (
                        <img src={dataUrl} alt={label} className="w-full h-full object-contain" />
                    ) : (
                        <Image size={20} className="text-[var(--color-muted)]" />
                    )}
                </div>
                <div className="flex flex-col gap-1.5">
                    <input
                        ref={inputRef}
                        type="file"
                        accept={accept}
                        onChange={handleChange}
                        className="hidden"
                    />
                    <Button variant="secondary" size="sm" icon={<Upload size={12} />} onClick={() => inputRef.current?.click()}>
                        Upload
                    </Button>
                    {dataUrl && (
                        <Button variant="ghost" size="sm" icon={<X size={12} />} onClick={onRemove}>
                            Remove
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}

function SettingsForm() {
    const { notify } = useToast();
    const { settings, update, reset } = useSettings();
    const { mode, setMode } = useTheme();

    const handleSave = () => {
        // Sync defaultTheme to actual ThemeProvider
        if (settings.defaultTheme === 'system') {
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            setMode(prefersDark ? 'dark' : 'light');
        } else {
            setMode(settings.defaultTheme);
        }
        notify('Settings saved and applied', 'success');
    };

    const handleReset = () => {
        reset();
        setMode('dark');
        notify('Settings reset to defaults', 'info');
    };

    return (
        <div className="max-w-3xl space-y-8 py-2">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-[var(--color-text)]">General Settings</h1>
                    <p className="text-sm text-[var(--color-muted)] mt-1">Configure your site identity, appearance, and preferences. Changes apply live.</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" icon={<RotateCcw size={14} />} onClick={handleReset}>
                        Reset
                    </Button>
                    <Button size="sm" icon={<Save size={14} />} onClick={handleSave}>
                        Save
                    </Button>
                </div>
            </div>

            <Card title="Site Identity" subtitle="Name, tagline, and branding">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input
                        label="Site Name"
                        value={settings.siteName}
                        onChange={(e) => update('siteName', e.target.value)}
                        placeholder="CTLabs"
                    />
                    <Input
                        label="Tagline"
                        value={settings.tagline}
                        onChange={(e) => update('tagline', e.target.value)}
                        placeholder="Life OS Platform"
                    />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
                    <ImageUpload
                        label="Logo"
                        dataUrl={settings.logoDataUrl}
                        onUpload={(url) => update('logoDataUrl', url)}
                        onRemove={() => update('logoDataUrl', '')}
                        accept="image/png,image/jpeg,image/svg+xml,image/webp"
                    />
                    <ImageUpload
                        label="Favicon"
                        dataUrl={settings.faviconDataUrl}
                        onUpload={(url) => update('faviconDataUrl', url)}
                        onRemove={() => update('faviconDataUrl', '')}
                        accept="image/png,image/x-icon,image/svg+xml,image/webp"
                    />
                </div>
            </Card>

            <Card title="Appearance" subtitle="Colors and theme preferences — changes apply instantly">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text)] mb-1.5">Primary Color</label>
                        <div className="flex items-center gap-2">
                            <input
                                type="color"
                                value={settings.primaryColor}
                                onChange={(e) => update('primaryColor', e.target.value)}
                                className="w-8 h-8 rounded border border-[var(--color-border)] cursor-pointer"
                            />
                            <Input
                                value={settings.primaryColor}
                                onChange={(e) => update('primaryColor', e.target.value)}
                                placeholder="#6366f1"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text)] mb-1.5">Accent Color</label>
                        <div className="flex items-center gap-2">
                            <input
                                type="color"
                                value={settings.accentColor}
                                onChange={(e) => update('accentColor', e.target.value)}
                                className="w-8 h-8 rounded border border-[var(--color-border)] cursor-pointer"
                            />
                            <Input
                                value={settings.accentColor}
                                onChange={(e) => update('accentColor', e.target.value)}
                                placeholder="#818cf8"
                            />
                        </div>
                    </div>
                    <Select
                        label="Default Theme"
                        value={settings.defaultTheme}
                        onChange={(e) => update('defaultTheme', e.target.value as 'light' | 'dark' | 'system')}
                        options={[
                            { value: 'light', label: 'Light' },
                            { value: 'dark', label: 'Dark' },
                            { value: 'system', label: 'System' },
                        ]}
                    />
                    <div className="flex items-center gap-3 pt-6">
                        <span className="text-sm text-[var(--color-muted)]">Current:</span>
                        <span className="text-sm font-medium text-[var(--color-text)]">{mode === 'dark' ? '🌙 Dark' : '☀️ Light'}</span>
                    </div>
                </div>
            </Card>

            <Card title="Preferences" subtitle="Display and behavior options">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-[var(--color-text)]">Compact Mode</p>
                            <p className="text-xs text-[var(--color-muted)]">Reduce spacing and padding throughout the UI</p>
                        </div>
                        <Switch checked={settings.compactMode} onChange={(v) => update('compactMode', v)} />
                    </div>
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-[var(--color-text)]">Show Module Counts</p>
                            <p className="text-xs text-[var(--color-muted)]">Display item counts in sidebar group labels</p>
                        </div>
                        <Switch checked={settings.showModuleCounts} onChange={(v) => update('showModuleCounts', v)} />
                    </div>
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-[var(--color-text)]">Sidebar Collapsed</p>
                            <p className="text-xs text-[var(--color-muted)]">Start with sidebar collapsed on load</p>
                        </div>
                        <Switch checked={settings.sidebarCollapsed} onChange={(v) => update('sidebarCollapsed', v)} />
                    </div>
                </div>
            </Card>

            <Card title="Regional" subtitle="Date, time, and locale settings">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Select
                        label="Date Format"
                        value={settings.dateFormat}
                        onChange={(e) => update('dateFormat', e.target.value)}
                        options={[
                            { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD' },
                            { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
                            { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
                            { value: 'DD.MM.YYYY', label: 'DD.MM.YYYY' },
                        ]}
                    />
                    <Select
                        label="Time Format"
                        value={settings.timeFormat}
                        onChange={(e) => update('timeFormat', e.target.value)}
                        options={[
                            { value: '24h', label: '24-hour' },
                            { value: '12h', label: '12-hour (AM/PM)' },
                        ]}
                    />
                    <Select
                        label="Language"
                        value={settings.language}
                        onChange={(e) => update('language', e.target.value)}
                        options={[
                            { value: 'en', label: 'English' },
                            { value: 'ar', label: 'Arabic' },
                            { value: 'fr', label: 'French' },
                            { value: 'de', label: 'German' },
                            { value: 'es', label: 'Spanish' },
                            { value: 'tr', label: 'Turkish' },
                        ]}
                    />
                    <Select
                        label="Timezone"
                        value={settings.timezone}
                        onChange={(e) => update('timezone', e.target.value)}
                        options={[
                            { value: 'UTC', label: 'UTC' },
                            { value: 'Asia/Riyadh', label: 'Asia/Riyadh (GMT+3)' },
                            { value: 'Europe/Istanbul', label: 'Europe/Istanbul (GMT+3)' },
                            { value: 'Europe/London', label: 'Europe/London (GMT+0/+1)' },
                            { value: 'America/New_York', label: 'America/New_York (GMT-5/-4)' },
                            { value: 'Asia/Dubai', label: 'Asia/Dubai (GMT+4)' },
                        ]}
                    />
                </div>
            </Card>
        </div>
    );
}

export default function SettingsPage() {
    return (
        <ToastProvider>
            <SettingsForm />
        </ToastProvider>
    );
}
