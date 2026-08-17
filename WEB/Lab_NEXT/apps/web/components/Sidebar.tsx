'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
    ChevronDown,
    ChevronsLeft,
    ChevronsRight,
    LayoutDashboard,
    Palette,
} from 'lucide-react';
import { roadmapGroups } from './roadmap-data';

interface NavItem {
    href: string;
    label: string;
    icon: React.ReactNode;
}

const systemLinks: NavItem[] = [
    { href: '/design', label: 'Design', icon: <Palette size={18} /> },
];

function isActive(href: string, pathname: string, exact: boolean): boolean {
    if (exact) return pathname === href || pathname === `${href}/`;
    return pathname === href || pathname.startsWith(`${href}/`);
}

function SidebarGroup({ group, open, onToggle, collapsed, pathname }: { group: (typeof roadmapGroups)[number]; open: boolean; onToggle: () => void; collapsed: boolean; pathname: string }) {
    const hasActiveChild = group.items.some((item) => pathname === `/modules/${item.id}` || pathname === `/modules/${item.id}/`);
    return (
        <div>
            <button
                type="button"
                onClick={onToggle}
                title={collapsed ? group.label : undefined}
                aria-expanded={open}
                className={[
                    'flex items-center gap-3 rounded-md text-sm transition-colors w-full cursor-pointer',
                    collapsed ? 'justify-center px-0 py-2.5' : 'px-3 py-2',
                    hasActiveChild && open
                        ? 'text-[var(--color-accent-hover)]'
                        : 'text-[var(--color-muted)] hover:bg-[var(--color-surface-2)] hover:text-[var(--color-text)]',
                ].join(' ')}
                style={hasActiveChild && open ? { background: 'var(--color-accent-soft)' } : undefined}
            >
                <span className="shrink-0">{group.icon}</span>
                {!collapsed && (
                    <>
                        <span className="flex-1 truncate text-left">{group.label}</span>
                        <span className="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-[var(--color-surface-2)] text-[var(--color-muted)]">
                            {group.items.length}
                        </span>
                    </>
                )}
                {!collapsed && (
                    <ChevronDown
                        size={14}
                        className={`shrink-0 transition-transform duration-[var(--ctlab-duration-fast)] ${open ? 'rotate-180' : ''}`}
                    />
                )}
            </button>
            {open && !collapsed && (
                <div className="mt-1 ml-5 pl-3 border-l border-[var(--color-border)] space-y-0.5">
                    {group.items.map((item) => {
                        const active = pathname === `/modules/${item.id}` || pathname === `/modules/${item.id}/`;
                        return (
                            <Link
                                key={item.id}
                                href={`/modules/${item.id}`}
                                className={[
                                    'flex items-start gap-2.5 rounded-md text-[13px] py-1.5 px-2 transition-colors',
                                    active
                                        ? 'text-[var(--color-accent-hover)]'
                                        : 'text-[var(--color-muted)] hover:bg-[var(--color-surface-2)] hover:text-[var(--color-text)]',
                                ].join(' ')}
                                style={active ? { background: 'var(--color-accent-soft)' } : undefined}
                            >
                                <span className="mt-0.5 shrink-0">{item.icon}</span>
                                <span className="min-w-0 truncate">{item.label}</span>
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function NavLink({ item, pathname, collapsed, exact = false }: { item: NavItem; pathname: string; collapsed: boolean; exact?: boolean }) {
    const active = isActive(item.href, pathname, exact);
    return (
        <Link
            href={item.href}
            title={collapsed ? item.label : undefined}
            className={[
                'flex items-center gap-3 rounded-md text-sm transition-colors',
                collapsed ? 'justify-center px-0 py-2.5' : 'px-3 py-2',
                active
                    ? 'text-[var(--color-accent-hover)]'
                    : 'text-[var(--color-muted)] hover:bg-[var(--color-surface-2)] hover:text-[var(--color-text)]',
            ].join(' ')}
            style={active ? { background: 'var(--color-accent-soft)' } : undefined}
        >
            {item.icon}
            {!collapsed && <span className="truncate">{item.label}</span>}
        </Link>
    );
}

function SectionLabel({ children, collapsed }: { children: React.ReactNode; collapsed: boolean }) {
    if (collapsed) return null;
    return (
        <div className="px-3 pt-4 pb-1 text-[11px] font-semibold text-[var(--color-muted)] uppercase tracking-wider">
            {children}
        </div>
    );
}

export default function Sidebar() {
    const pathname = usePathname();
    const [collapsed, setCollapsed] = useState(false);
    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>({});

    useEffect(() => {
        const mq = window.matchMedia('(max-width: 1024px)');
        const sync = () => setCollapsed(mq.matches);
        sync();
        mq.addEventListener('change', sync);
        return () => mq.removeEventListener('change', sync);
    }, []);

    useEffect(() => {
        for (const group of roadmapGroups) {
            for (const item of group.items) {
                if (pathname === `/modules/${item.id}` || pathname === `/modules/${item.id}/`) {
                    setOpenGroups((prev) => ({ ...prev, [group.id]: true }));
                }
            }
        }
    }, [pathname]);

    const toggleGroup = (id: string) => setOpenGroups((prev) => ({ ...prev, [id]: !prev[id] }));

    return (
        <aside
            className="shrink-0 border-r border-[var(--color-border)] bg-[var(--color-surface)] flex flex-col transition-[width] duration-[var(--ctlab-duration-base)] overflow-hidden"
            style={{ width: collapsed ? '3.5rem' : '15rem' }}
        >
            <div className={`h-14 flex items-center gap-2 border-b border-[var(--color-border)] ${collapsed ? 'justify-center px-0' : 'px-4'}`}>
                <span className="w-2.5 h-2.5 rounded-full bg-[var(--color-accent)] shrink-0" />
                {!collapsed && <span className="font-semibold tracking-tight">CTLabs</span>}
            </div>

            <nav className="flex-1 p-2 overflow-y-auto overflow-x-hidden">
                <div className="space-y-1 mb-1">
                    <NavLink item={{ href: '/dashboard', label: 'Dashboard', icon: <LayoutDashboard size={18} /> }} pathname={pathname} collapsed={collapsed} exact />
                </div>

                <div className="space-y-0.5">
                    {roadmapGroups.map((group) => (
                        <SidebarGroup
                            key={group.id}
                            group={group}
                            open={!!openGroups[group.id]}
                            onToggle={() => toggleGroup(group.id)}
                            collapsed={collapsed}
                            pathname={pathname}
                        />
                    ))}
                </div>

                <SectionLabel collapsed={collapsed}>System</SectionLabel>
                <div className="space-y-1">
                    {systemLinks.map((item) => (
                        <NavLink key={item.href} item={item} pathname={pathname} collapsed={collapsed} />
                    ))}
                </div>
            </nav>

            <div className="p-2 border-t border-[var(--color-border)]">
                <button
                    type="button"
                    onClick={() => setCollapsed((c) => !c)}
                    className="flex items-center justify-center gap-2 rounded-md px-2 py-2 w-full text-xs text-[var(--color-muted)] hover:bg-[var(--color-surface-2)] hover:text-[var(--color-text)] cursor-pointer"
                    aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                >
                    {collapsed ? <ChevronsRight size={16} /> : <ChevronsLeft size={16} />}
                    {!collapsed && <span>Collapse</span>}
                </button>
            </div>
        </aside>
    );
}
