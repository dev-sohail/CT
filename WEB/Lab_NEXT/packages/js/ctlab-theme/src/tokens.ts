/**
 * CTLabs design tokens — single source of truth for theming.
 *
 * Color values are HSL channel triples (e.g. "248 82% 68%") exposed as CSS
 * custom properties so Tailwind's <alpha-value> and plain hsl() usages both
 * work. Kebab-case keys map 1:1 to CSS variable names (`--ctlab-<key>`).
 */

export const colorRoles = [
    'bg',
    'surface',
    'surface-2',
    'border',
    'border-strong',
    'text',
    'text-muted',
    'text-faint',
    'primary',
    'primary-hover',
    'primary-fg',
    'success',
    'warning',
    'danger',
    'danger-fg',
    'info',
] as const;

export type ColorRole = (typeof colorRoles)[number];
export type ThemeMode = 'dark' | 'light';
export type Palette = Record<ColorRole, string>;

export const palettes: Record<ThemeMode, Palette> = {
    dark: {
        bg: '240 12% 4%',
        surface: '240 10% 7%',
        'surface-2': '240 8% 12%',
        border: '240 6% 16%',
        'border-strong': '240 6% 22%',
        text: '240 10% 92%',
        'text-muted': '240 6% 62%',
        'text-faint': '240 5% 45%',
        primary: '248 82% 68%',
        'primary-hover': '248 84% 74%',
        'primary-fg': '248 30% 6%',
        success: '159 65% 51%',
        warning: '38 92% 53%',
        danger: '0 84% 71%',
        'danger-fg': '0 0% 5%',
        info: '199 89% 60%',
    },
    light: {
        bg: '240 25% 99%',
        surface: '240 16% 97%',
        'surface-2': '240 10% 93%',
        border: '240 8% 88%',
        'border-strong': '240 8% 79%',
        text: '240 14% 12%',
        'text-muted': '240 6% 40%',
        'text-faint': '240 6% 52%',
        primary: '248 68% 55%',
        'primary-hover': '248 70% 48%',
        'primary-fg': '0 0% 100%',
        success: '159 60% 32%',
        warning: '38 92% 32%',
        danger: '0 72% 46%',
        'danger-fg': '0 0% 100%',
        info: '199 89% 36%',
    },
};

export const fontFamily = {
    sans: "'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
    mono: "'JetBrains Mono', ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace",
} as const;

export const fontSize = {
    xs: '0.75rem',
    sm: '0.8125rem',
    base: '0.875rem',
    lg: '1rem',
    xl: '1.125rem',
    '2xl': '1.375rem',
    '3xl': '1.75rem',
    '4xl': '2.25rem',
} as const;

export const spacing = {
    0: '0',
    1: '0.25rem',
    2: '0.5rem',
    3: '0.75rem',
    4: '1rem',
    5: '1.25rem',
    6: '1.5rem',
    8: '2rem',
    10: '2.5rem',
    12: '3rem',
    16: '4rem',
    20: '5rem',
    24: '6rem',
} as const;

export const radius = {
    sm: '0.375rem',
    md: '0.5rem',
    lg: '0.75rem',
    xl: '1rem',
    full: '9999px',
} as const;

export const shadows = {
    sm: '0 1px 2px hsl(240 20% 0% / 0.25)',
    md: '0 4px 12px hsl(240 20% 0% / 0.3)',
    lg: '0 12px 32px hsl(240 20% 0% / 0.4)',
} as const;

export const motion = {
    fast: '120ms',
    base: '200ms',
    slow: '300ms',
    easing: {
        standard: 'cubic-bezier(0.2, 0, 0, 1)',
        enter: 'cubic-bezier(0.2, 0.8, 0.2, 1)',
    },
} as const;

export const zIndex = {
    sticky: 1100,
    dropdown: 1200,
    overlay: 1300,
    modal: 1400,
    popover: 1500,
    toast: 1600,
    command: 1700,
} as const;

export const layout = {
    sidebar: '15rem',
    topbar: '3.5rem',
    contentMaxWidth: '80rem',
    gutter: '1.5rem',
} as const;

export const tokens = {
    colorRoles,
    palettes,
    fontFamily,
    fontSize,
    spacing,
    radius,
    shadows,
    motion,
    zIndex,
    layout,
} as const;

export type CtlabTokens = typeof tokens;
export type CtlabColorRoles = typeof colorRoles;
