'use client';

import { useEffect, useState, useCallback } from 'react';
import Link from 'next/link';
import {
    Activity,
    ArrowRight,
    BookOpen,
    BookMarked,
    Bookmark,
    Calendar,
    CalendarDays,
    CheckSquare,
    Clock,
    CreditCard,
    Dumbbell,
    FileText,
    GraduationCap,
    Heart,
    HelpCircle,
    LayoutDashboard,
    Link2,
    Moon,
    Repeat,
    RefreshCw,
    Search,
    Shield,
    Sparkles,
    Target,
    Trash2,
    TrendingUp,
    Wallet,
    Zap,
} from 'lucide-react';
import { Card, EmptyState, PageHeader, Badge } from '@ctlab/ctlab-ui';
import { wikiApi, relTime, formatDate } from '@/components/wiki/api';
import { markdownToText } from '@/components/wiki/Markdown';
import type { Workspace, Page, Task, Review } from '@/components/wiki/types';

interface Stat {
    label: string;
    value: number | string;
    icon: React.ReactNode;
    tone: 'accent' | 'ok' | 'warn' | 'muted' | 'info';
    href?: string;
}

const toneColor: Record<Stat['tone'], string> = {
    accent: 'var(--color-accent-hover)',
    ok: 'var(--color-ok)',
    warn: 'var(--color-warn)',
    muted: 'var(--color-muted)',
    info: 'var(--color-info)',
};

function StatCard({ stat }: { stat: Stat }) {
    const inner = (
        <div className="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4 transition-colors hover:border-[var(--color-accent)]/50">
            <div className="flex items-center gap-2 text-xs text-[var(--color-muted)]">
                <span className="shrink-0">{stat.icon}</span>
                {stat.label}
            </div>
            <div className="mt-2 text-2xl font-bold" style={{ color: toneColor[stat.tone] }}>
                {stat.value}
            </div>
        </div>
    );
    return stat.href ? <Link href={stat.href}>{inner}</Link> : inner;
}

function SectionHeader({ title, href }: { title: string; href?: string }) {
    return (
        <div className="flex items-center justify-between mb-3">
            <h3 className="text-sm font-semibold text-[var(--color-muted)] uppercase tracking-wider">{title}</h3>
            {href && <Link href={href} className="text-xs text-[var(--color-accent-hover)] hover:underline">View all</Link>}
        </div>
    );
}

export default function Dashboard() {
    const [workspaces, setWorkspaces] = useState<Workspace[]>([]);
    const [pages, setPages] = useState<Page[]>([]);
    const [openTasks, setOpenTasks] = useState<Task[]>([]);
    const [reviews, setReviews] = useState<Review[]>([]);
    const [homeSummary, setHomeSummary] = useState<any>(null);
    const [habits, setHabits] = useState<any>(null);
    const [workouts, setWorkouts] = useState<any>(null);
    const [meals, setMeals] = useState<any>(null);
    const [sleep, setSleep] = useState<any>(null);
    const [contacts, setContacts] = useState<any[]>([]);
    const [courses, setCourses] = useState<any>(null);
    const [flashcards, setFlashcards] = useState<any>(null);
    const [bookmarks, setBookmarks] = useState<any>(null);
    const [snippets, setSnippets] = useState<any>(null);
    const [expenses, setExpenses] = useState<any>(null);
    const [subscriptions, setSubs] = useState<any>(null);
    const [habitsStats, setHabitsStats] = useState<any>(null);
    const [goals, setGoals] = useState<any>(null);
    const [notifications, setNotifications] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    const load = useCallback(async () => {
        setLoading(true);
        const safe = <T,>(p: Promise<T>) => p.catch(() => null as any);

        const [
            ws, pg, tk, rv,
            home, habitS, workS, mealS, slpS,
            cnt, crs, flb, bkm, snp,
            exp, sub, gl, noti,
        ] = await Promise.all([
            safe(wikiApi.list<Workspace>('/workspaces')),
            safe(wikiApi.list<Page>('/pages')),
            safe(wikiApi.list<Task>('/tasks', { done: false })),
            safe(wikiApi.list<Review>('/reviews')),
            safe(wikiApi.get<any>('/home/summary')),
            safe(wikiApi.get<any>('/habits/stats')),
            safe(wikiApi.get<any>('/workouts/stats')),
            safe(wikiApi.get<any>('/meals/stats')),
            safe(wikiApi.get<any>('/sleep/stats')),
            safe(wikiApi.get<any[]>('/contacts')),
            safe(wikiApi.get<any>('/courses/stats')),
            safe(wikiApi.get<any>('/flashcards/stats')),
            safe(wikiApi.get<any>('/bookmarks/stats')),
            safe(wikiApi.get<any>('/snippets/stats')),
            safe(wikiApi.get<any>('/expenses/summary')),
            safe(wikiApi.get<any>('/subscriptions/stats')),
            safe(wikiApi.list<any>('/goals', { active: true })),
            safe(wikiApi.get<any>('/notifications/unread-count')),
        ]);

        setWorkspaces(ws ?? []);
        setPages(pg ?? []);
        setOpenTasks(tk ?? []);
        setReviews(rv ?? []);
        setHomeSummary(home);
        setHabitsStats(habitS);
        setWorkouts(workS);
        setMeals(mealS);
        setSleep(slpS);
        setContacts(cnt ?? []);
        setCourses(crs);
        setFlashcards(flb);
        setBookmarks(bkm);
        setSnippets(snp);
        setExpenses(exp);
        setSubs(sub);
        setGoals(gl);
        setNotifications(noti);
        setLoading(false);
    }, []);

    useEffect(() => { load(); }, [load]);

    const dueReviews = reviews.filter((r) => r.status !== 'done');
    const recentPages = [...pages].sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()).slice(0, 5);
    const tasksByDue = [...openTasks]
        .filter((t) => t.due_date)
        .sort((a, b) => new Date(a.due_date!).getTime() - new Date(b.due_date!).getTime())
        .slice(0, 5);

    const stats: Stat[] = [
        { label: 'Contacts', value: contacts.length, icon: <Heart size={14} />, tone: 'accent', href: '/modules/77' },
        { label: 'Open Tasks', value: openTasks.length, icon: <CheckSquare size={14} />, tone: openTasks.length > 10 ? 'warn' : 'ok', href: '/modules/19' },
        { label: 'Habits Today', value: habitsStats ? `${habitsStats.completed_today}/${habitsStats.active}` : '—', icon: <Repeat size={14} />, tone: 'ok', href: '/modules/40' },
        { label: 'Workouts', value: workouts?.total ?? '—', icon: <Dumbbell size={14} />, tone: 'info', href: '/modules/46' },
        { label: 'Courses', value: courses?.total ?? '—', icon: <GraduationCap size={14} />, tone: 'accent', href: '/modules/27' },
        { label: 'Subscriptions', value: subscriptions?.active_count ?? '—', icon: <CreditCard size={14} />, tone: 'muted', href: '/modules/38' },
        { label: 'Reviews Due', value: dueReviews.length, icon: <RefreshCw size={14} />, tone: dueReviews.length > 0 ? 'warn' : 'muted', href: '/modules/26' },
        { label: 'Notifications', value: notifications?.count ?? 0, icon: <Zap size={14} />, tone: (notifications?.count ?? 0) > 0 ? 'warn' : 'muted', href: '/modules/3' },
    ];

    return (
        <div className="space-y-8">
            <PageHeader
                title="Dashboard"
                subtitle="CTLabs — Your personal software ecosystem."
            />

            {/* ── Top Stats ── */}
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                {stats.map((s) => (
                    <StatCard key={s.label} stat={s} />
                ))}
            </div>

            {/* ── Today ── */}
            {homeSummary && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card>
                        <SectionHeader title="Today's Schedule" />
                        <div className="space-y-2">
                            {homeSummary.today && (
                                <div className="flex items-center gap-3 text-sm">
                                    <Calendar size={16} className="text-[var(--color-accent)]" />
                                    <span className="text-[var(--color-muted)]">{homeSummary.today.events_today} events today</span>
                                </div>
                            )}
                            {homeSummary.today && (
                                <div className="flex items-center gap-3 text-sm">
                                    <CheckSquare size={16} className="text-[var(--color-ok)]" />
                                    <span className="text-[var(--color-muted)]">{homeSummary.today.planner_items_due} items due</span>
                                </div>
                            )}
                            {homeSummary.upcoming_events?.length > 0 ? (
                                homeSummary.upcoming_events.map((ev: any) => (
                                    <div key={ev.id} className="flex items-center gap-3 p-2 rounded bg-[var(--color-surface-2)] text-sm">
                                        <CalendarDays size={14} className="text-[var(--color-accent)] shrink-0" />
                                        <span className="truncate flex-1">{ev.title}</span>
                                        <span className="text-xs text-[var(--color-muted)] shrink-0">{formatDate(ev.starts_at)}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-xs text-[var(--color-muted)]">No upcoming events.</p>
                            )}
                        </div>
                    </Card>

                    <Card>
                        <SectionHeader title="Birthdays Coming Up" />
                        {homeSummary.birthdays?.length > 0 ? (
                            <div className="space-y-2">
                                {homeSummary.birthdays.map((b: any) => (
                                    <div key={b.id} className="flex items-center gap-3 p-2 rounded bg-[var(--color-surface-2)] text-sm">
                                        <span className="text-lg">🎂</span>
                                        <span className="flex-1">{b.name}</span>
                                        <span className="text-xs text-[var(--color-muted)]">{formatDate(b.birthday)}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-xs text-[var(--color-muted)]">No birthdays in the next 14 days.</p>
                        )}
                    </Card>

                    <Card>
                        <SectionHeader title="Quick Actions" />
                        <div className="grid grid-cols-2 gap-2">
                            {[
                                { href: '/modules/19', label: 'Planner', icon: <CalendarDays size={16} /> },
                                { href: '/modules/26', label: 'Wiki', icon: <BookOpen size={16} /> },
                                { href: '/modules/77', label: 'CRM', icon: <Heart size={16} /> },
                                { href: '/modules/53', label: 'Finance', icon: <Wallet size={16} /> },
                                { href: '/modules/40', label: 'Habits', icon: <Repeat size={16} /> },
                                { href: '/modules/46', label: 'Workouts', icon: <Dumbbell size={16} /> },
                            ].map((l) => (
                                <Link key={l.href} href={l.href} className="flex items-center gap-2 p-2 rounded-lg border border-[var(--color-border)] text-sm text-[var(--color-muted)] hover:border-[var(--color-accent)]/50 hover:text-[var(--color-text)] transition-colors">
                                    {l.icon}
                                    <span>{l.label}</span>
                                </Link>
                            ))}
                        </div>
                    </Card>
                </div>
            )}

            {/* ── Tasks + Recent Pages ── */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <SectionHeader title="Open Tasks" href="/modules/19" />
                    {tasksByDue.length === 0 && openTasks.length === 0 ? (
                        <EmptyState description="No open tasks." />
                    ) : (
                        <div className="space-y-2">
                            {tasksByDue.map((task) => (
                                <div key={task.id} className="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                                    <span className="text-sm leading-6" style={{ color: task.priority === 'high' ? 'var(--color-warn)' : task.priority === 'medium' ? 'var(--color-info)' : 'var(--color-muted)' }}>
                                        {task.priority === 'high' ? '▲' : task.priority === 'medium' ? '●' : '○'}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm text-[var(--color-text)] truncate">{task.title}</p>
                                        <p className="text-xs text-[var(--color-muted)]">Due {relTime(task.due_date!)}</p>
                                    </div>
                                </div>
                            ))}
                            {openTasks.length > tasksByDue.length && (
                                <p className="text-xs text-[var(--color-muted)] px-1">…and {openTasks.length - tasksByDue.length} more</p>
                            )}
                        </div>
                    )}
                </Card>

                <Card>
                    <SectionHeader title="Recent Pages" href="/modules/26" />
                    {recentPages.length === 0 ? (
                        <EmptyState description="No pages yet." />
                    ) : (
                        <div className="space-y-2">
                            {recentPages.map((page) => (
                                <Link
                                    key={page.id}
                                    href={`/modules/26`} 
                                    className="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:border-[var(--color-accent)]/50 transition-colors"
                                >
                                    <span className="text-xl shrink-0">{page.icon || <FileText size={18} className="text-[var(--color-muted)]" />}</span>
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium text-[var(--color-text)] text-sm truncate">{page.title}</p>
                                        <p className="text-xs text-[var(--color-muted)] truncate">{markdownToText(page.content) || 'No content'}</p>
                                    </div>
                                    <span className="ml-auto shrink-0 text-xs text-[var(--color-muted)]">{relTime(page.updated_at)}</span>
                                </Link>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            {/* ── Health & Wellness ── */}
            <div>
                <h3 className="text-sm font-semibold text-[var(--color-muted)] uppercase tracking-wider mb-3">Health & Wellness</h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link href="/modules/46">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Dumbbell size={14} /> Workouts</div>
                                {workouts ? (
                                    <div className="space-y-1">
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{workouts.total}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{workouts.completed} completed · {workouts.total_minutes} min total</div>
                                        {workouts.total_calories > 0 && <div className="text-xs text-[var(--color-ok)]">{workouts.total_calories} cal burned</div>}
                                    </div>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/47">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Zap size={14} /> Nutrition</div>
                                {meals ? (
                                    <div className="space-y-1">
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{meals.total_meals}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{meals.days_logged} days logged</div>
                                        {meals.average_daily_calories > 0 && <div className="text-xs text-[var(--color-ok)]">~{Math.round(meals.average_daily_calories)} cal/day avg</div>}
                                    </div>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/48">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Moon size={14} /> Sleep</div>
                                {sleep ? (
                                    <div className="space-y-1">
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{sleep.total_nights}</div>
                                        <div className="text-xs text-[var(--color-muted)]">nights tracked</div>
                                        {sleep.average_duration_minutes > 0 && <div className="text-xs text-[var(--color-ok)]">~{Math.round(sleep.average_duration_minutes / 60 * 10) / 10}h avg</div>}
                                        {sleep.average_quality > 0 && <div className="text-xs text-[var(--color-info)]">Quality: {sleep.average_quality}/10</div>}
                                    </div>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/40">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Repeat size={14} /> Habits</div>
                                {habitsStats ? (
                                    <div className="space-y-1">
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{habitsStats.completed_today}/{habitsStats.active}</div>
                                        <div className="text-xs text-[var(--color-muted)]">completed today</div>
                                        {habitsStats.total > 0 && <div className="text-xs text-[var(--color-muted)]">{habitsStats.total} total habits</div>}
                                    </div>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                </div>
            </div>

            {/* ── Finance ── */}
            <div>
                <h3 className="text-sm font-semibold text-[var(--color-muted)] uppercase tracking-wider mb-3">Finance</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Link href="/modules/53">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Wallet size={14} /> Monthly Expenses</div>
                                {expenses ? (
                                    <div className="space-y-1">
                                        <div className="text-2xl font-bold text-[var(--color-text)]">${Number(expenses.total).toFixed(2)}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{expenses.count} transactions this month</div>
                                    </div>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/38">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><CreditCard size={14} /> Subscriptions</div>
                                {subscriptions ? (
                                    <div className="space-y-1">
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{subscriptions.active_count}</div>
                                        <div className="text-xs text-[var(--color-muted)]">active subscriptions</div>
                                        {subscriptions.monthly_total > 0 && <div className="text-xs text-[var(--color-warn)]">${Number(subscriptions.monthly_total).toFixed(2)}/mo</div>}
                                    </div>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/53">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><TrendingUp size={14} /> Budget</div>
                                <div className="text-2xl font-bold text-[var(--color-text)]">—</div>
                                <div className="text-xs text-[var(--color-muted)]">Set up budgets</div>
                            </div>
                        </Card>
                    </Link>
                </div>
            </div>

            {/* ── Knowledge & Learning ── */}
            <div>
                <h3 className="text-sm font-semibold text-[var(--color-muted)] uppercase tracking-wider mb-3">Knowledge & Learning</h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link href="/modules/27">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><GraduationCap size={14} /> Courses</div>
                                {courses ? (
                                    <>
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{courses.total}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{courses.completed ?? 0} completed</div>
                                    </>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/30">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><BookMarked size={14} /> Flashcards</div>
                                {flashcards ? (
                                    <>
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{flashcards.total}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{flashcards.due_today ?? 0} due today</div>
                                    </>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/31">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Bookmark size={14} /> Bookmarks</div>
                                {bookmarks ? (
                                    <>
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{bookmarks.total}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{bookmarks.unread ?? 0} unread</div>
                                    </>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                    <Link href="/modules/32">
                        <Card className="hover:border-[var(--color-accent)] transition-colors cursor-pointer">
                            <div className="p-4">
                                <div className="flex items-center gap-2 text-xs text-[var(--color-muted)] mb-2"><Sparkles size={14} /> Snippets</div>
                                {snippets ? (
                                    <>
                                        <div className="text-2xl font-bold text-[var(--color-text)]">{snippets.total}</div>
                                        <div className="text-xs text-[var(--color-muted)]">{snippets.languages ?? 0} languages</div>
                                    </>
                                ) : <p className="text-xs text-[var(--color-muted)]">No data</p>}
                            </div>
                        </Card>
                    </Link>
                </div>
            </div>

            {/* ── Goals & Progress ── */}
            {goals && goals.data && goals.data.length > 0 && (
                <Card>
                    <SectionHeader title="Active Goals" href="/modules/40" />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {goals.data.slice(0, 6).map((g: any) => (
                            <div key={g.id} className="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                                <Target size={16} className="text-[var(--color-accent)] shrink-0" />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm text-[var(--color-text)] truncate">{g.title ?? g.name}</p>
                                    {g.progress !== undefined && (
                                        <div className="mt-1 h-1.5 rounded-full bg-[var(--color-surface-2)] overflow-hidden">
                                            <div className="h-full rounded-full bg-[var(--color-ok)] transition-all" style={{ width: `${Math.min(g.progress, 100)}%` }} />
                                        </div>
                                    )}
                                </div>
                                {g.status && <Badge>{g.status}</Badge>}
                            </div>
                        ))}
                    </div>
                </Card>
            )}

            {/* ── Module Quick Links ── */}
            <Card>
                <SectionHeader title="All Modules" href="/modules" />
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    {[
                        { href: '/modules/40', label: 'Habits', icon: '🔄' },
                        { href: '/modules/41', label: 'Journal', icon: '📓' },
                        { href: '/modules/43', label: 'Scorecard', icon: '📊' },
                        { href: '/modules/46', label: 'Workouts', icon: '💪' },
                        { href: '/modules/47', label: 'Nutrition', icon: '🍎' },
                        { href: '/modules/48', label: 'Sleep', icon: '🌙' },
                        { href: '/modules/53', label: 'Expenses', icon: '💰' },
                        { href: '/modules/54', label: 'Bills', icon: '📋' },
                        { href: '/modules/61', label: 'Home Maint.', icon: '🔧' },
                        { href: '/modules/67', label: 'Trip Planner', icon: '🗺️' },
                        { href: '/modules/72', label: 'Resumes', icon: '📄' },
                        { href: '/modules/83', label: 'Media', icon: '🎬' },
                        { href: '/modules/88', label: 'Writing', icon: '✍️' },
                        { href: '/modules/96', label: 'Deployments', icon: '🚀' },
                        { href: '/modules/102', label: 'Security', icon: '🛡️' },
                        { href: '/modules/104', label: 'Analytics', icon: '📈' },
                    ].map((l) => (
                        <Link key={l.href} href={l.href} className="flex items-center gap-2 p-2.5 rounded-lg border border-[var(--color-border)] text-sm text-[var(--color-muted)] hover:border-[var(--color-accent)]/50 hover:text-[var(--color-text)] transition-colors">
                            <span className="text-base">{l.icon}</span>
                            <span className="truncate">{l.label}</span>
                        </Link>
                    ))}
                </div>
            </Card>
        </div>
    );
}
