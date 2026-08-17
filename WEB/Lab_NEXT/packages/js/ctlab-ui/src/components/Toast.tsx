'use client';

import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { createPortal } from 'react-dom';

export type ToastTone = 'success' | 'info' | 'warning' | 'danger';
export interface Toast {
    id: string;
    title: string;
    description?: string;
    tone: ToastTone;
}

interface ToastContextValue {
    notify: (title: string, tone?: ToastTone, description?: string) => void;
    dismiss: (id: string) => void;
}

const ToastContext = createContext<ToastContextValue | null>(null);

const toneColor: Record<ToastTone, string> = {
    success: 'hsl(var(--ctlab-success))',
    info: 'hsl(var(--ctlab-info))',
    warning: 'hsl(var(--ctlab-warning))',
    danger: 'hsl(var(--ctlab-danger))',
};

const toneIcon: Record<ToastTone, string> = {
    success: '✓',
    info: 'ℹ',
    warning: '!',
    danger: '✕',
};

export function ToastProvider({ children }: { children: ReactNode }) {
    const [toasts, setToasts] = useState<Toast[]>([]);
    const counter = useRef(0);

    const dismiss = useCallback((id: string) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    const notify = useCallback(
        (title: string, tone: ToastTone = 'success', description?: string) => {
            const id = `toast-${++counter.current}`;
            setToasts((prev) => [...prev.slice(-4), { id, title, tone, description }]);
            window.setTimeout(() => dismiss(id), 4500);
        },
        [dismiss],
    );

    const value = useMemo(() => ({ notify, dismiss }), [notify, dismiss]);

    return (
        <ToastContext.Provider value={value}>
            {children}
            {typeof document !== 'undefined' &&
                createPortal(
                    <div
                        role="region"
                        aria-live="polite"
                        style={{
                            position: 'fixed',
                            top: '1rem',
                            right: '1rem',
                            zIndex: 'var(--ctlab-z-toast, 1600)',
                            display: 'flex',
                            flexDirection: 'column',
                            gap: '0.5rem',
                            width: '20rem',
                            maxWidth: 'calc(100vw - 2rem)',
                        }}
                    >
                        {toasts.map((toast) => (
                            <div
                                key={toast.id}
                                role="alert"
                                style={{
                                    display: 'flex',
                                    gap: '0.75rem',
                                    padding: '0.875rem 1rem',
                                    background: 'hsl(var(--ctlab-surface))',
                                    border: '1px solid hsl(var(--ctlab-border))',
                                    borderLeft: `3px solid ${toneColor[toast.tone]}`,
                                    borderRadius: 'var(--ctlab-radius-md)',
                                    boxShadow: 'var(--ctlab-shadow-lg)',
                                    animation: 'nx-modal-in var(--ctlab-duration-base) ease',
                                }}
                            >
                                <span style={{ color: toneColor[toast.tone], fontWeight: 700 }}>{toneIcon[toast.tone]}</span>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <div style={{ fontSize: '0.875rem', fontWeight: 600, color: 'hsl(var(--ctlab-text))' }}>{toast.title}</div>
                                    {toast.description && (
                                        <div style={{ fontSize: '0.8125rem', color: 'hsl(var(--ctlab-text-muted))' }}>{toast.description}</div>
                                    )}
                                </div>
                                <button
                                    type="button"
                                    aria-label="Dismiss notification"
                                    onClick={() => dismiss(toast.id)}
                                    style={{
                                        background: 'none',
                                        border: 'none',
                                        cursor: 'pointer',
                                        color: 'hsl(var(--ctlab-text-faint))',
                                        fontSize: '0.875rem',
                                        lineHeight: 1,
                                    }}
                                >
                                    ✕
                                </button>
                            </div>
                        ))}
                    </div>,
                    document.body,
                )}
        </ToastContext.Provider>
    );
}

export function useToast(): ToastContextValue {
    const ctx = useContext(ToastContext);
    if (!ctx) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return ctx;
}
