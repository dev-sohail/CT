'use client';

import { type ReactNode } from 'react';
import Link from 'next/link';
import { ArrowLeft } from 'lucide-react';
import { EmptyState as NxEmptyState, Modal as NxModal, type ModalSize } from '@ctlab/ctlab-ui';

/**
 * Shared wiki UI primitives. These now delegate to @ctlab/ctlab-ui so every
 * page that uses them inherits the design system automatically.
 */

export const fieldCls = 'nx-input';
export const selectCls = 'nx-input cursor-pointer';
export const primaryBtn = 'nx-btn nx-btn-primary';
export const ghostBtn = 'nx-btn nx-btn-ghost';
export const dangerBtn = 'nx-btn nx-btn-danger';

export function Modal({
    title,
    onClose,
    children,
    size = 'md',
}: {
    title: string;
    onClose: () => void;
    children: ReactNode;
    size?: ModalSize;
}) {
    return (
        <NxModal open onClose={onClose} title={title} size={size}>
            {children}
        </NxModal>
    );
}

export function BackLink({ href, label }: { href: string; label?: string }) {
    return (
        <Link
            href={href}
            className="inline-flex items-center gap-1.5 text-sm text-[var(--color-muted)] hover:text-[var(--color-text)] transition-colors"
        >
            <ArrowLeft size={16} />
            {label ?? 'Back'}
        </Link>
    );
}

export function EmptyState({ message }: { message: string }) {
    return <NxEmptyState description={message} />;
}

export function PageCard({
    icon,
    title,
    sub,
    href,
    meta,
}: {
    icon: string;
    title: string;
    sub?: string;
    href: string;
    meta?: ReactNode;
}) {
    return (
        <Link href={href} data-interactive="true" className="nx-card block">
            <div className="p-4">
                <div className="flex items-center mb-2">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--color-surface-2)] text-lg mr-3 shrink-0">
                        {icon || '📄'}
                    </span>
                    <h4 className="nx-card-title truncate">{title}</h4>
                </div>
                {sub && <p className="nx-card-subtitle line-clamp-2">{sub}</p>}
                {meta && <div className="nx-card-subtitle mt-2">{meta}</div>}
            </div>
        </Link>
    );
}

export function statusColor(status?: string): string {
    switch (status) {
        case 'done': return 'hsl(var(--ctlab-success))';
        case 'in_progress': return 'hsl(var(--ctlab-info))';
        case 'archived': return 'hsl(var(--ctlab-text-muted))';
        case 'planned': return 'hsl(var(--ctlab-warning))';
        default: return 'hsl(var(--ctlab-primary))';
    }
}

export function Pill({ children, color }: { children: ReactNode; color?: string }) {
    return (
        <span
            className="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full border"
            style={{
                backgroundColor: color ? `color-mix(in srgb, ${color} 12%, transparent)` : 'hsl(var(--ctlab-surface-2))',
                color: color ?? 'hsl(var(--ctlab-text-muted))',
                borderColor: color ? `color-mix(in srgb, ${color} 35%, transparent)` : 'hsl(var(--ctlab-border))',
            }}
        >
            {children}
        </span>
    );
}
