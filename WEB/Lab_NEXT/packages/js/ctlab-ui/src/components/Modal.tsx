'use client';

import { useEffect, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

export type ModalSize = 'sm' | 'md' | 'lg';

export interface ModalProps {
    open: boolean;
    onClose: () => void;
    title?: ReactNode;
    size?: ModalSize;
    children: ReactNode;
    footer?: ReactNode;
}

export function Modal({ open, onClose, title, size = 'md', children, footer }: ModalProps) {
    const panelRef = useRef<HTMLDivElement>(null);
    const previouslyFocused = useRef<Element | null>(null);

    useEffect(() => {
        if (!open) return;
        previouslyFocused.current = document.activeElement;

        function onKey(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                onClose();
                return;
            }
            if (e.key === 'Tab') {
                const panel = panelRef.current;
                if (!panel) return;
                const focusables = panel.querySelectorAll<HTMLElement>(
                    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])',
                );
                if (focusables.length === 0) return;
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }

        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        const timer = window.setTimeout(() => panelRef.current?.querySelector<HTMLElement>('button, a, input')?.focus(), 0);

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
            window.clearTimeout(timer);
            (previouslyFocused.current as HTMLElement | null)?.focus?.();
        };
    }, [open, onClose]);

    if (!open || typeof document === 'undefined') return null;

    return createPortal(
        <div className="nx-modal-backdrop" onMouseDown={onClose}>
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-label={typeof title === 'string' ? title : undefined}
                data-size={size}
                className="nx-modal"
                onMouseDown={(e) => e.stopPropagation()}
            >
                <div className="nx-modal-header">
                    <div className="nx-modal-title">{title}</div>
                    <button type="button" className="nx-modal-close" onClick={onClose} aria-label="Close">
                        ✕
                    </button>
                </div>
                <div className="nx-modal-body">{children}</div>
                {footer && <div className="nx-modal-footer">{footer}</div>}
            </div>
        </div>,
        document.body,
    );
}
