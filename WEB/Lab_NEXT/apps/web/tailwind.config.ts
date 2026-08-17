import type { Config } from 'tailwindcss';

const ctlab = {
    bg: 'hsl(var(--ctlab-bg) / <alpha-value>)',
    surface: 'hsl(var(--ctlab-surface) / <alpha-value>)',
    'surface-2': 'hsl(var(--ctlab-surface-2) / <alpha-value>)',
    border: 'hsl(var(--ctlab-border) / <alpha-value>)',
    'border-strong': 'hsl(var(--ctlab-border-strong) / <alpha-value>)',
    text: 'hsl(var(--ctlab-text) / <alpha-value>)',
    'text-muted': 'hsl(var(--ctlab-text-muted) / <alpha-value>)',
    'text-faint': 'hsl(var(--ctlab-text-faint) / <alpha-value>)',
    primary: 'hsl(var(--ctlab-primary) / <alpha-value>)',
    'primary-hover': 'hsl(var(--ctlab-primary-hover) / <alpha-value>)',
    'primary-fg': 'hsl(var(--ctlab-primary-fg) / <alpha-value>)',
    success: 'hsl(var(--ctlab-success) / <alpha-value>)',
    warning: 'hsl(var(--ctlab-warning) / <alpha-value>)',
    danger: 'hsl(var(--ctlab-danger) / <alpha-value>)',
    info: 'hsl(var(--ctlab-info) / <alpha-value>)',
};

const config: Config = {
    content: ['./app/**/*.{ts,tsx}', './components/**/*.{ts,tsx}'],
    theme: {
        extend: {
            colors: {
                ctlab,
                ink: {
                    950: '#0b0b0e',
                    900: '#121216',
                    850: '#17171b',
                    800: '#1c1c22',
                    700: '#26262e',
                    600: '#33333d',
                },
                accent: {
                    DEFAULT: '#7c6cf0',
                    hover: '#8f82f5',
                },
                'accent-soft': 'rgba(124,108,240,0.14)',
                ok: '#34d399',
                warn: '#fbbf24',
                bad: '#f87171',
                info: '#38bdf8',
            },
            fontFamily: {
                sans: ['var(--ctlab-font-sans)', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['var(--ctlab-font-mono)', 'ui-monospace', 'monospace'],
            },
            spacing: { 1: '4px', 2: '8px', 3: '12px', 4: '16px', 5: '20px', 6: '24px', 8: '32px', 10: '40px', 12: '48px', 16: '64px', 20: '80px', 24: '96px' },
            borderRadius: { sm: '6px', md: '8px', lg: '12px', xl: '16px', full: '9999px' },
            boxShadow: {
                sm: 'var(--ctlab-shadow-sm)',
                md: 'var(--ctlab-shadow-md)',
                lg: 'var(--ctlab-shadow-lg)',
            },
            transitionDuration: {
                fast: '120ms',
                base: '200ms',
                slow: '300ms',
            },
        },
    },
    plugins: [],
};

export default config;
