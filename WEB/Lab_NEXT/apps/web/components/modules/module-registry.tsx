import type { FieldDef, StatsDef, ActionDef } from '@/components/modules/RecordCrudPage';
import type { Column } from '@ctlab/ctlab-ui';

export interface ModuleConfig {
    id: string;
    title: string;
    subtitle: string;
    basePath: string;
    fields?: FieldDef[];
    tableColumns?: Column<Record<string, any>>[];
    stats?: StatsDef;
    actions?: ActionDef[];
    nameKey?: string;
    /** For polymorphic modules: the record_type slug appended to basePath */
    recordType?: string;
    /** Stats-only mode: no CRUD table, just display analytics endpoints */
    statsOnly?: boolean;
    /** Stats endpoints to fetch and display in cards */
    statsEndpoints?: { key: string; label: string; path: string }[];
}

const F = {
    text: (key: string, label: string, opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'text', ...opts }),
    num: (key: string, label: string, opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'number', ...opts }),
    date: (key: string, label: string, opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'date', ...opts }),
    dt: (key: string, label: string, opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'datetime-local', ...opts }),
    select: (key: string, label: string, options: { value: string; label: string }[], opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'select', options, ...opts }),
    toggle: (key: string, label: string, opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'toggle', ...opts }),
    area: (key: string, label: string, opts?: Partial<FieldDef>): FieldDef => ({ key, label, type: 'textarea', ...opts }),
};

const STATUS = (extra?: { value: string; label: string }[]) => [
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'paused', label: 'Paused' },
    { value: 'archived', label: 'Archived' },
    ...(extra ?? []),
];

const PRIORITY = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
];

export const moduleRegistry: Record<string, ModuleConfig> = {
    /* ─── Module 26 Wiki ─── */
    '26': {
        id: '26', title: 'Second Brain', subtitle: 'Personal Wiki & Knowledge Base',
        basePath: '/workspaces', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.area('description', 'Description'),
        ],
    },
    /* ─── Phase 2.1 Automation Core ─── */
    '16': {
        id: '16', title: 'Rules', subtitle: 'Rule Engine & Workflow Builder',
        basePath: '/rules', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.area('description', 'Description'),
            F.toggle('is_active', 'Active'),
            F.select('trigger_type', 'Trigger Type', [
                { value: 'manual', label: 'Manual' },
                { value: 'schedule', label: 'Schedule' },
                { value: 'file_change', label: 'File Change' },
                { value: 'event', label: 'Event' },
            ]),
            F.area('trigger_config', 'Trigger Config (JSON)'),
            F.select('conditions_logic', 'Conditions Logic', [
                { value: 'and', label: 'AND' },
                { value: 'or', label: 'OR' },
            ]),
        ],
    },
    '17': {
        id: '17', title: 'Scheduled Tasks', subtitle: 'Cron Manager',
        basePath: '/cron/tasks', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('command', 'Command', { required: true }),
            F.text('cron_expression', 'Cron Expression', { required: true, placeholder: '* * * * *' }),
            F.toggle('is_active', 'Active'),
            F.area('arguments', 'Arguments (JSON)'),
        ],
    },
    '18': {
        id: '18', title: 'File Watcher Rules', subtitle: 'File Automation',
        basePath: '/file-watcher/rules', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('source_disk', 'Source Disk'),
            F.text('source_path', 'Source Path', { required: true }),
            F.text('pattern', 'Pattern (glob)', { required: true, placeholder: '*.pdf' }),
            F.select('action', 'Action', [
                { value: 'copy', label: 'Copy' },
                { value: 'move', label: 'Move' },
                { value: 'tag', label: 'Tag' },
                { value: 'notify', label: 'Notify' },
            ]),
            F.text('destination_path', 'Destination Path'),
            F.toggle('is_active', 'Active'),
        ],
    },

    /* ─── Phase 2.2 Productivity ─── */
    '19': {
        id: '19', title: 'Planner Items', subtitle: 'Unified Planner Engine',
        basePath: '/planner/items', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.select('scope', 'Scope', [
                { value: 'personal', label: 'Personal' },
                { value: 'work', label: 'Work' },
                { value: 'project', label: 'Project' },
            ]),
            F.select('status', 'Status', [
                { value: 'todo', label: 'To Do' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'done', label: 'Done' },
                { value: 'cancelled', label: 'Cancelled' },
            ]),
            F.select('priority', 'Priority', PRIORITY),
            F.dt('due_at', 'Due At'),
            F.text('recurrence_rule', 'Recurrence Rule'),
            F.num('sort_order', 'Sort Order'),
        ],
        tableColumns: [
            { key: 'title', header: 'Title', render: (r) => r.title ?? '—' },
            { key: 'scope', header: 'Scope', render: (r) => r.scope ?? '—' },
            { key: 'status', header: 'Status', render: (r) => <span className="nx-badge">{r.status ?? '—'}</span> },
            { key: 'priority', header: 'Priority', render: (r) => r.priority ?? '—' },
            { key: 'due_at', header: 'Due', render: (r) => r.due_at ? new Date(r.due_at).toLocaleDateString() : '—' },
        ],
    },
    '20': {
        id: '20', title: 'Goals', subtitle: 'Goal Hierarchy & OKR Tracker',
        basePath: '/goals', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.select('level', 'Level', [
                { value: 'vision', label: 'Vision' },
                { value: 'goal', label: 'Goal' },
                { value: 'objective', label: 'Objective' },
            ]),
            F.select('status', 'Status', STATUS()),
            F.date('start_at', 'Start Date'),
            F.date('target_at', 'Target Date'),
            F.num('progress', 'Progress %', { placeholder: '0-100' }),
            F.num('sort_order', 'Sort Order'),
        ],
    },
    '21': {
        id: '21', title: 'Time Entries', subtitle: 'Time Audit & Block Analyzer',
        basePath: '/time/entries', nameKey: 'activity',
        fields: [
            F.text('activity', 'Activity', { required: true }),
            F.text('category', 'Category'),
            F.dt('started_at', 'Started At', { required: true }),
            F.dt('ended_at', 'Ended At'),
            F.area('notes', 'Notes'),
        ],
        tableColumns: [
            { key: 'activity', header: 'Activity', render: (r) => r.activity ?? '—' },
            { key: 'category', header: 'Category', render: (r) => r.category ?? '—' },
            { key: 'started_at', header: 'Started', render: (r) => r.started_at ? new Date(r.started_at).toLocaleString() : '—' },
            { key: 'ended_at', header: 'Ended', render: (r) => r.ended_at ? new Date(r.ended_at).toLocaleString() : '—' },
        ],
    },
    '22': {
        id: '22', title: 'Home Dashboard', subtitle: 'Personal Dashboard / Life OS',
        basePath: '/home/summary', nameKey: 'name', statsOnly: true,
        statsEndpoints: [
            { key: 'summary', label: 'Home Summary', path: '/home/summary' },
        ],
    },
    '23': {
        id: '23', title: 'Shifts', subtitle: 'Work Shift & Schedule Manager',
        basePath: '/shifts', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.dt('starts_at', 'Starts At', { required: true }),
            F.dt('ends_at', 'Ends At', { required: true }),
            F.text('location', 'Location'),
            F.text('client', 'Client'),
            F.num('hourly_rate', 'Hourly Rate'),
            F.select('status', 'Status', [
                { value: 'scheduled', label: 'Scheduled' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ]),
            F.area('notes', 'Notes'),
        ],
    },
    '24': {
        id: '24', title: 'Projects', subtitle: 'Project Roadmap Planner',
        basePath: '/roadmap/projects', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.area('description', 'Description'),
            F.select('status', 'Status', STATUS([
                { value: 'planned', label: 'Planned' },
                { value: 'on_track', label: 'On Track' },
                { value: 'at_risk', label: 'At Risk' },
            ])),
            F.select('priority', 'Priority', PRIORITY),
            F.date('start_at', 'Start Date'),
            F.date('target_end_at', 'Target End Date'),
            F.text('client', 'Client'),
        ],
    },
    '25': {
        id: '25', title: 'Productivity Insights', subtitle: 'Insights Engine',
        basePath: '/insights/overview', nameKey: 'name', statsOnly: true,
        statsEndpoints: [
            { key: 'overview', label: 'Overview', path: '/insights/overview' },
            { key: 'completion', label: 'Completion', path: '/insights/completion' },
            { key: 'trends', label: 'Trends', path: '/insights/trends' },
            { key: 'focus', label: 'Focus', path: '/insights/focus' },
        ],
    },

    /* ─── Phase 2.3 Knowledge & Learning ─── */
    '27': {
        id: '27', title: 'Courses', subtitle: 'Study & Course Tracker',
        basePath: '/courses', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.text('subject', 'Subject'),
            F.text('url', 'URL'),
            F.text('provider', 'Provider'),
            F.select('status', 'Status', [
                { value: 'not_started', label: 'Not Started' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'dropped', label: 'Dropped' },
            ]),
            F.num('hours_target', 'Hours Target'),
            F.num('hours_spent', 'Hours Spent'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/courses/stats',
            render: (d) => (
                <span className="text-sm text-[var(--color-muted)]">
                    {d.total_courses} courses · {d.in_progress} active · {d.total_hours_spent}h logged
                </span>
            ),
        },
    },
    '28': {
        id: '28', title: 'Certifications', subtitle: 'Certification & Skill Roadmap',
        basePath: '/certifications', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.text('issuer', 'Issuer'),
            F.select('status', 'Status', [
                { value: 'planned', label: 'Planned' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'earned', label: 'Earned' },
                { value: 'expired', label: 'Expired' },
            ]),
            F.date('issued_at', 'Issued Date'),
            F.date('expiry_at', 'Expiry Date'),
            F.text('credential_url', 'Credential URL'),
            F.text('skills', 'Skills (comma-separated)'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/certifications/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} certifications</span>,
        },
    },
    '29': {
        id: '29', title: 'Papers', subtitle: 'Research & PDF Annotation',
        basePath: '/papers', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.text('authors', 'Authors'),
            F.num('year', 'Year'),
            F.text('source', 'Source / Journal'),
            F.text('url', 'URL'),
            F.area('abstract', 'Abstract'),
            F.select('status', 'Status', [
                { value: 'to_read', label: 'To Read' },
                { value: 'reading', label: 'Reading' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.num('rating', 'Rating (1-5)'),
        ],
    },
    '30': {
        id: '30', title: 'Flashcards', subtitle: 'SM-2 Flashcard Engine',
        basePath: '/flashcards', nameKey: 'question',
        fields: [
            F.area('question', 'Question', { required: true }),
            F.area('answer', 'Answer', { required: true }),
            F.num('ease_factor', 'Ease Factor', { hint: 'Default 2.5' }),
            F.num('interval_days', 'Interval (days)'),
        ],
        stats: {
            path: '/flashcards/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} cards · {d.due_today ?? 0} due</span>,
        },
    },
    '31': {
        id: '31', title: 'Bookmarks', subtitle: 'Read-Later Archive',
        basePath: '/bookmarks', nameKey: 'title',
        fields: [
            F.text('url', 'URL', { required: true }),
            F.text('title', 'Title'),
            F.area('description', 'Description'),
            F.select('status', 'Status', [
                { value: 'unread', label: 'Unread' },
                { value: 'read', label: 'Read' },
                { value: 'archived', label: 'Archived' },
            ]),
            F.text('tags', 'Tags (comma-separated)'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/bookmarks/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} bookmarks</span>,
        },
    },
    '32': {
        id: '32', title: 'Code Snippets', subtitle: 'Snippet Manager & Package Index',
        basePath: '/snippets', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.text('language', 'Language', { placeholder: 'typescript, python, etc.' }),
            F.area('code', 'Code', { required: true }),
            F.text('tags', 'Tags (comma-separated)'),
            F.toggle('favorite', 'Favorite'),
        ],
        stats: {
            path: '/snippets/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} snippets</span>,
        },
    },
    '33': {
        id: '33', title: 'Learning Analytics', subtitle: 'Learning Analytics Dashboard',
        basePath: '/learning/overview', nameKey: 'name', statsOnly: true,
        statsEndpoints: [
            { key: 'overview', label: 'Overview', path: '/learning/overview' },
            { key: 'streaks', label: 'Streaks', path: '/learning/streaks' },
            { key: 'retention', label: 'Retention', path: '/learning/retention' },
        ],
    },

    /* ─── Phase 2.4 Personal Organization ─── */
    '34': {
        id: '34', title: 'Document Archive', subtitle: 'Version Vault',
        basePath: '/document-archive', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.text('category', 'Category'),
            F.text('tags', 'Tags'),
            F.select('status', 'Status', [
                { value: 'active', label: 'Active' },
                { value: 'archived', label: 'Archived' },
            ]),
            F.date('expires_at', 'Expires At'),
        ],
        stats: {
            path: '/document-archive/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} documents</span>,
        },
    },
    '35': {
        id: '35', title: 'Media Library', subtitle: 'Digital Media Library',
        basePath: '/media', nameKey: 'title',
        fields: [
            F.select('type', 'Type', [
                { value: 'movie', label: 'Movie' },
                { value: 'tv_show', label: 'TV Show' },
                { value: 'documentary', label: 'Documentary' },
                { value: 'podcast', label: 'Podcast' },
                { value: 'audiobook', label: 'Audiobook' },
                { value: 'other', label: 'Other' },
            ]),
            F.text('title', 'Title', { required: true }),
            F.text('creator', 'Creator'),
            F.num('year', 'Year'),
            F.text('genre', 'Genre'),
            F.num('rating', 'Rating'),
            F.select('status', 'Status', [
                { value: 'want_to_watch', label: 'Want to Watch' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/media/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} items</span>,
        },
    },
    '36': {
        id: '36', title: 'Assets', subtitle: 'QR Asset Tagging',
        basePath: '/assets', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.area('description', 'Description'),
            F.text('category', 'Category'),
            F.text('location', 'Location'),
            F.num('value', 'Value'),
            F.select('status', 'Status', [
                { value: 'active', label: 'Active' },
                { value: 'stored', label: 'Stored' },
                { value: 'sold', label: 'Sold' },
                { value: 'discarded', label: 'Discarded' },
            ]),
            F.date('purchased_at', 'Purchased At'),
            F.date('warranty_until', 'Warranty Until'),
        ],
        stats: {
            path: '/assets/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} assets</span>,
        },
    },
    '37': {
        id: '37', title: 'Secrets Vault', subtitle: 'Password & 2FA Vault',
        basePath: '/secret-entries', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('category', 'Category', { placeholder: 'email, social, finance...' }),
            F.text('url', 'URL'),
            F.text('username', 'Username'),
            F.area('password_encrypted', 'Password', { hint: 'Stored encrypted' }),
            F.area('notes_encrypted', 'Notes'),
            F.toggle('totp_enabled', 'TOTP Enabled'),
            F.toggle('favorite', 'Favorite'),
            F.date('expires_at', 'Expires At'),
        ],
        stats: {
            path: '/secret-entries/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} secrets</span>,
        },
    },
    '38': {
        id: '38', title: 'Subscriptions', subtitle: 'Recurring Payment Tracker',
        basePath: '/subscriptions', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('company', 'Company'),
            F.text('category', 'Category'),
            F.num('amount', 'Amount', { required: true }),
            F.select('currency', 'Currency', [
                { value: 'USD', label: 'USD' },
                { value: 'EUR', label: 'EUR' },
                { value: 'GBP', label: 'GBP' },
            ]),
            F.select('billing_cycle', 'Billing Cycle', [
                { value: 'monthly', label: 'Monthly' },
                { value: 'yearly', label: 'Yearly' },
                { value: 'weekly', label: 'Weekly' },
                { value: 'quarterly', label: 'Quarterly' },
            ]),
            F.select('status', 'Status', [
                { value: 'active', label: 'Active' },
                { value: 'cancelled', label: 'Cancelled' },
                { value: 'paused', label: 'Paused' },
            ]),
            F.toggle('auto_renew', 'Auto Renew'),
            F.date('next_billing_at', 'Next Billing'),
        ],
        stats: {
            path: '/subscriptions/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} subscriptions · ${d.monthly_total ?? 0}/mo</span>,
        },
    },
    '39': {
        id: '39', title: 'Receipts', subtitle: 'Receipt & Warranty Archive',
        basePath: '/receipts', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.text('merchant', 'Merchant'),
            F.text('category', 'Category'),
            F.num('amount', 'Amount'),
            F.date('purchased_at', 'Purchased At'),
            F.date('warranty_until', 'Warranty Until'),
            F.text('receipt_number', 'Receipt #'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/receipts/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} receipts</span>,
        },
    },

    /* ─── Phase 2.5 Goals, Habits & Health ─── */
    '40': {
        id: '40', title: 'Habits', subtitle: 'Habit Tracking Engine',
        basePath: '/habits', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.area('description', 'Description'),
            F.select('frequency', 'Frequency', [
                { value: 'daily', label: 'Daily' },
                { value: 'weekly', label: 'Weekly' },
                { value: 'custom', label: 'Custom' },
            ]),
            F.num('target_count', 'Target Count'),
            F.text('color', 'Color', { placeholder: '#ff5722' }),
            F.toggle('active', 'Active'),
            F.date('start_date', 'Start Date'),
            F.date('end_date', 'End Date'),
        ],
        stats: {
            path: '/habits/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} habits · {d.active ?? 0} active</span>,
        },
    },
    '41': {
        id: '41', title: 'Journal', subtitle: 'Structured Review System',
        basePath: '/journal', nameKey: 'title',
        fields: [
            F.date('entry_date', 'Date', { required: true }),
            F.text('title', 'Title'),
            F.area('body', 'Body', { required: true }),
            F.select('mood', 'Mood', [
                { value: 'great', label: 'Great' },
                { value: 'good', label: 'Good' },
                { value: 'neutral', label: 'Neutral' },
                { value: 'bad', label: 'Bad' },
                { value: 'terrible', label: 'Terrible' },
            ]),
            F.num('energy', 'Energy (1-10)'),
            F.area('gratitude', 'Gratitude'),
            F.area('wins', 'Wins'),
            F.area('lessons', 'Lessons'),
            F.toggle('is_private', 'Private'),
        ],
    },
    '42': {
        id: '42', title: 'Decisions', subtitle: 'Decision Journal',
        basePath: '/decisions', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('context', 'Context'),
            F.area('options', 'Options'),
            F.area('decision', 'Decision'),
            F.area('rationale', 'Rationale'),
            F.num('confidence', 'Confidence (1-10)'),
            F.select('status', 'Status', [
                { value: 'pending', label: 'Pending' },
                { value: 'decided', label: 'Decided' },
                { value: 'reviewed', label: 'Reviewed' },
            ]),
            F.date('decided_at', 'Decided At'),
            F.date('review_at', 'Review At'),
            F.area('outcome', 'Outcome'),
        ],
    },
    '43': {
        id: '43', title: 'Scorecard Metrics', subtitle: 'Life KPI Tracker',
        basePath: '/scorecard', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('category', 'Category'),
            F.text('unit', 'Unit', { placeholder: 'hours, %, count, $' }),
            F.num('target', 'Target'),
            F.select('direction', 'Direction', [
                { value: 'higher_is_better', label: 'Higher is Better' },
                { value: 'lower_is_better', label: 'Lower is Better' },
            ]),
            F.select('frequency', 'Frequency', [
                { value: 'daily', label: 'Daily' },
                { value: 'weekly', label: 'Weekly' },
                { value: 'monthly', label: 'Monthly' },
            ]),
            F.toggle('active', 'Active'),
        ],
        stats: {
            path: '/scorecard/summary',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} metrics</span>,
        },
    },
    '44': {
        id: '44', title: 'Vision Board', subtitle: 'Bucket List Manager',
        basePath: '/vision', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.select('type', 'Type', [
                { value: 'vision', label: 'Vision' },
                { value: 'bucket', label: 'Bucket List' },
                { value: 'dream', label: 'Dream' },
            ]),
            F.text('category', 'Category'),
            F.select('status', 'Status', [
                { value: 'active', label: 'Active' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.select('priority', 'Priority', PRIORITY),
            F.date('target_date', 'Target Date'),
            F.text('image_url', 'Image URL'),
        ],
        stats: {
            path: '/vision/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} items · {d.completed ?? 0} done</span>,
        },
    },
    '45': {
        id: '45', title: 'Timeline Events', subtitle: 'Life Timeline & History',
        basePath: '/timeline', nameKey: 'title',
        fields: [
            F.date('event_date', 'Date', { required: true }),
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.select('type', 'Type', [
                { value: 'milestone', label: 'Milestone' },
                { value: 'memory', label: 'Memory' },
                { value: 'achievement', label: 'Achievement' },
                { value: 'life_event', label: 'Life Event' },
            ]),
            F.text('category', 'Category'),
            F.select('significance', 'Significance', [
                { value: 'low', label: 'Low' },
                { value: 'medium', label: 'Medium' },
                { value: 'high', label: 'High' },
                { value: 'landmark', label: 'Landmark' },
            ]),
            F.text('location', 'Location'),
        ],
        stats: {
            path: '/timeline/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} events</span>,
        },
    },
    '46': {
        id: '46', title: 'Workouts', subtitle: 'Training Planner',
        basePath: '/workouts', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.select('type', 'Type', [
                { value: 'strength', label: 'Strength' },
                { value: 'cardio', label: 'Cardio' },
                { value: 'flexibility', label: 'Flexibility' },
                { value: 'hiit', label: 'HIIT' },
                { value: 'sports', label: 'Sports' },
                { value: 'other', label: 'Other' },
            ]),
            F.date('scheduled_on', 'Scheduled On'),
            F.dt('started_at', 'Started At'),
            F.dt('completed_at', 'Completed At'),
            F.num('duration_minutes', 'Duration (min)'),
            F.num('calories_burned', 'Calories Burned'),
            F.select('status', 'Status', [
                { value: 'planned', label: 'Planned' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'skipped', label: 'Skipped' },
            ]),
            F.area('exercises', 'Exercises'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/workouts/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} workouts</span>,
        },
    },
    '47': {
        id: '47', title: 'Meals', subtitle: 'Nutrition & Meal Planner',
        basePath: '/meals', nameKey: 'name',
        fields: [
            F.date('eaten_on', 'Date', { required: true }),
            F.select('meal_type', 'Meal Type', [
                { value: 'breakfast', label: 'Breakfast' },
                { value: 'lunch', label: 'Lunch' },
                { value: 'dinner', label: 'Dinner' },
                { value: 'snack', label: 'Snack' },
            ]),
            F.text('name', 'Name', { required: true }),
            F.num('calories', 'Calories'),
            F.num('protein_grams', 'Protein (g)'),
            F.num('carbs_grams', 'Carbs (g)'),
            F.num('fat_grams', 'Fat (g)'),
            F.area('ingredients', 'Ingredients'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/meals/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} meals · {d.avg_calories ?? 0} avg cal</span>,
        },
    },
    '48': {
        id: '48', title: 'Sleep Logs', subtitle: 'Sleep & Recovery Tracker',
        basePath: '/sleep', nameKey: 'sleep_date',
        fields: [
            F.date('sleep_date', 'Date', { required: true }),
            F.dt('bedtime', 'Bedtime', { required: true }),
            F.dt('wake_time', 'Wake Time', { required: true }),
            F.num('duration_minutes', 'Duration (min)'),
            F.select('quality', 'Quality', [
                { value: 'excellent', label: 'Excellent' },
                { value: 'good', label: 'Good' },
                { value: 'fair', label: 'Fair' },
                { value: 'poor', label: 'Poor' },
            ]),
            F.num('interruptions', 'Interruptions'),
            F.num('deep_minutes', 'Deep Sleep (min)'),
            F.num('rem_minutes', 'REM (min)'),
            F.area('notes', 'Notes'),
        ],
        tableColumns: [
            { key: 'sleep_date', header: 'Date', render: (r) => r.sleep_date ?? '—' },
            { key: 'bedtime', header: 'Bedtime', render: (r) => r.bedtime ? new Date(r.bedtime).toLocaleTimeString() : '—' },
            { key: 'wake_time', header: 'Wake', render: (r) => r.wake_time ? new Date(r.wake_time).toLocaleTimeString() : '—' },
            { key: 'duration_minutes', header: 'Duration', render: (r) => r.duration_minutes ? `${Math.round(r.duration_minutes / 60)}h ${r.duration_minutes % 60}m` : '—' },
            { key: 'quality', header: 'Quality', render: (r) => <span className="nx-badge">{r.quality ?? '—'}</span> },
        ],
    },
    '49': {
        id: '49', title: 'Medications', subtitle: 'Prescription Tracker',
        basePath: '/medications', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('generic_name', 'Generic Name'),
            F.text('dosage', 'Dosage', { placeholder: '10mg' }),
            F.select('form', 'Form', [
                { value: 'tablet', label: 'Tablet' },
                { value: 'capsule', label: 'Capsule' },
                { value: 'liquid', label: 'Liquid' },
                { value: 'injection', label: 'Injection' },
                { value: 'patch', label: 'Patch' },
                { value: 'other', label: 'Other' },
            ]),
            F.select('frequency', 'Frequency', [
                { value: 'daily', label: 'Daily' },
                { value: 'twice_daily', label: 'Twice Daily' },
                { value: 'weekly', label: 'Weekly' },
                { value: 'as_needed', label: 'As Needed' },
            ]),
            F.select('status', 'Status', [
                { value: 'active', label: 'Active' },
                { value: 'completed', label: 'Completed' },
                { value: 'discontinued', label: 'Discontinued' },
            ]),
            F.text('prescriber', 'Prescriber'),
            F.date('started_on', 'Started On'),
            F.area('instructions', 'Instructions'),
        ],
        stats: {
            path: '/medications/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} medications · {d.active ?? 0} active</span>,
        },
    },
    '50': {
        id: '50', title: 'Medical Records', subtitle: 'Visit Log',
        basePath: '/medical', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.date('record_date', 'Date'),
            F.text('provider', 'Provider'),
            F.text('facility', 'Facility'),
            F.text('diagnosis', 'Diagnosis'),
            F.area('symptoms', 'Symptoms'),
            F.area('treatment', 'Treatment'),
            F.area('medications', 'Medications'),
            F.date('follow_up_date', 'Follow-up Date'),
            F.select('status', 'Status', [
                { value: 'active', label: 'Active' },
                { value: 'resolved', label: 'Resolved' },
                { value: 'ongoing', label: 'Ongoing' },
            ]),
        ],
        stats: {
            path: '/medical/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} records</span>,
        },
    },
    '51': {
        id: '51', title: 'Body Observations', subtitle: 'Symptom & Metrics Tracker',
        basePath: '/body-observations', nameKey: 'name',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.text('observation_type', 'Type', { placeholder: 'weight, blood_pressure, mood...' }),
            F.num('value', 'Value', { required: true }),
            F.text('unit', 'Unit', { placeholder: 'kg, bpm, /10' }),
            F.select('severity', 'Severity', [
                { value: 'none', label: 'None' },
                { value: 'mild', label: 'Mild' },
                { value: 'moderate', label: 'Moderate' },
                { value: 'severe', label: 'Severe' },
            ]),
            F.dt('observed_at', 'Observed At'),
            F.area('description', 'Description'),
        ],
    },
    '52': {
        id: '52', title: 'Hydration & Movement', subtitle: 'Reminder Engine',
        basePath: '/wellness', nameKey: 'name',
        fields: [
            F.text('name', 'Name'),
            F.select('type', 'Type', [
                { value: 'hydration', label: 'Hydration' },
                { value: 'movement', label: 'Movement' },
            ]),
        ],
        stats: {
            path: '/wellness/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.water_glasses ?? 0} glasses · {d.movement_minutes ?? 0} min</span>,
        },
    },

    /* ─── Phase 2.6 Finance ─── */
    '53': {
        id: '53', title: 'Expenses', subtitle: 'Expense Tracking & Budgeting',
        basePath: '/expenses', nameKey: 'description',
        fields: [
            F.text('description', 'Description', { required: true }),
            F.num('amount', 'Amount', { required: true }),
            F.text('category', 'Category'),
            F.text('payment_method', 'Payment Method'),
            F.select('recurring', 'Recurring', [
                { value: 'no', label: 'No' },
                { value: 'monthly', label: 'Monthly' },
                { value: 'yearly', label: 'Yearly' },
            ]),
            F.date('spent_on', 'Spent On'),
            F.area('notes', 'Notes'),
        ],
        stats: {
            path: '/expenses/summary',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">${d.total ?? 0} total</span>,
        },
    },
    '54': {
        id: '54', title: 'Bills', subtitle: 'Bill Payment & Reminder',
        basePath: '/finance/bills', nameKey: 'name', recordType: 'bills',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.num('amount', 'Amount', { required: true }),
            F.date('due_date', 'Due Date'),
            F.text('category', 'Category'),
            F.text('institution', 'Institution'),
            F.select('status', 'Status', STATUS([{ value: 'pending', label: 'Pending' }, { value: 'paid', label: 'Paid' }, { value: 'overdue', label: 'Overdue' }])),
        ],
    },
    '55': {
        id: '55', title: 'Savings Goals', subtitle: 'Financial Goal Planner',
        basePath: '/finance/savings', nameKey: 'name', recordType: 'savings',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.num('target_amount', 'Target Amount', { required: true }),
            F.num('current_amount', 'Current Amount'),
            F.text('category', 'Category'),
            F.date('due_date', 'Target Date'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '56': {
        id: '56', title: 'Investments', subtitle: 'Net Worth Tracker',
        basePath: '/finance/investments', nameKey: 'name', recordType: 'investments',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.num('amount', 'Amount', { required: true }),
            F.num('current_amount', 'Current Value'),
            F.text('category', 'Category', { placeholder: 'stocks, bonds, crypto...' }),
            F.text('institution', 'Institution'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '57': {
        id: '57', title: 'Loans', subtitle: 'Debt Payoff Planner',
        basePath: '/finance/loans', nameKey: 'name', recordType: 'loans',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.num('amount', 'Loan Amount', { required: true }),
            F.num('current_amount', 'Remaining Balance'),
            F.text('institution', 'Lender'),
            F.date('due_date', 'Payoff Date'),
            F.select('status', 'Status', STATUS([{ value: 'active', label: 'Active' }])),
        ],
    },
    '58': {
        id: '58', title: 'Insurance', subtitle: 'Policy Manager',
        basePath: '/finance/insurance', nameKey: 'name', recordType: 'insurance',
        fields: [
            F.text('name', 'Policy Name', { required: true }),
            F.num('amount', 'Premium Amount', { required: true }),
            F.text('institution', 'Provider'),
            F.select('status', 'Status', STATUS()),
            F.date('due_date', 'Renewal Date'),
        ],
    },
    '59': {
        id: '59', title: 'Tax Documents', subtitle: 'Tax Archive',
        basePath: '/finance/tax_documents', nameKey: 'name', recordType: 'tax_documents',
        fields: [
            F.text('name', 'Document Name', { required: true }),
            F.num('amount', 'Amount'),
            F.text('category', 'Category'),
            F.select('status', 'Status', STATUS()),
            F.date('due_date', 'Due Date'),
        ],
    },
    '60': {
        id: '60', title: 'Purchases', subtitle: 'Purchase History & Price Tracker',
        basePath: '/finance/purchases', nameKey: 'name', recordType: 'purchases',
        fields: [
            F.text('name', 'Item Name', { required: true }),
            F.num('amount', 'Price', { required: true }),
            F.text('category', 'Category'),
            F.date('due_date', 'Purchase Date'),
            F.select('status', 'Status', STATUS()),
        ],
    },

    /* ─── Phase 2.7 Home & Travel ─── */
    '61': {
        id: '61', title: 'Maintenance Tasks', subtitle: 'Home Maintenance Scheduler',
        basePath: '/maintenance', nameKey: 'title',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Description'),
            F.text('category', 'Category'),
            F.text('location', 'Location'),
            F.date('due_on', 'Due On'),
            F.num('recurrence_days', 'Repeat Every (days)'),
            F.num('cost', 'Cost'),
            F.select('status', 'Status', STATUS([
                { value: 'pending', label: 'Pending' },
                { value: 'completed', label: 'Completed' },
                { value: 'overdue', label: 'Overdue' },
            ])),
            F.select('priority', 'Priority', PRIORITY),
        ],
        stats: {
            path: '/maintenance/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} tasks · {d.pending ?? 0} pending</span>,
        },
    },
    '62': {
        id: '62', title: 'Home Projects', subtitle: 'Improvement Project Planner',
        basePath: '/home-projects', nameKey: 'name',
        fields: [
            F.text('name', 'Project Name', { required: true }),
            F.area('description', 'Description'),
            F.text('room', 'Room'),
            F.select('status', 'Status', STATUS([
                { value: 'planned', label: 'Planned' },
                { value: 'in_progress', label: 'In Progress' },
            ])),
            F.select('priority', 'Priority', PRIORITY),
            F.date('started_on', 'Started On'),
            F.date('target_date', 'Target Date'),
            F.num('budget', 'Budget'),
            F.num('spent', 'Spent'),
        ],
        stats: {
            path: '/home-projects/stats',
            render: (d) => <span className="text-sm text-[var(--color-muted)]">{d.total ?? 0} projects</span>,
        },
    },
    '63': {
        id: '63', title: 'Appliances', subtitle: 'Warranty Inventory',
        basePath: '/home-travel/appliances', nameKey: 'name', recordType: 'appliances',
        fields: [
            F.text('name', 'Name', { required: true }),
            F.area('description', 'Description'),
            F.text('location', 'Location'),
            F.date('due_date', 'Warranty Until'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '64': {
        id: '64', title: 'Utilities', subtitle: 'Usage Tracker',
        basePath: '/home-travel/utilities', nameKey: 'name', recordType: 'utilities',
        fields: [
            F.text('name', 'Utility Name', { required: true }),
            F.num('amount', 'Cost'),
            F.select('status', 'Status', STATUS()),
            F.date('due_date', 'Due Date'),
            F.area('description', 'Notes'),
        ],
    },
    '65': {
        id: '65', title: 'Garden', subtitle: 'Plant Care Manager',
        basePath: '/home-travel/garden', nameKey: 'name', recordType: 'garden',
        fields: [
            F.text('name', 'Plant / Task', { required: true }),
            F.area('description', 'Notes'),
            F.date('due_date', 'Next Care Date'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '66': {
        id: '66', title: 'Household Inventory', subtitle: 'Inventory Manager',
        basePath: '/home-travel/inventory', nameKey: 'name', recordType: 'inventory',
        fields: [
            F.text('name', 'Item', { required: true }),
            F.num('amount', 'Quantity'),
            F.text('location', 'Location'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '67': {
        id: '67', title: 'Trips', subtitle: 'Trip Planner & Itinerary',
        basePath: '/home-travel/trips', nameKey: 'name', recordType: 'trips',
        fields: [
            F.text('name', 'Trip Name', { required: true }),
            F.area('description', 'Itinerary'),
            F.text('location', 'Destination'),
            F.date('due_date', 'Start Date'),
            F.date('date', 'End Date'),
            F.select('status', 'Status', STATUS([{ value: 'upcoming', label: 'Upcoming' }])),
        ],
    },
    '68': {
        id: '68', title: 'Travel Journal', subtitle: 'Journal & Expense Tracker',
        basePath: '/home-travel/travel_journal', nameKey: 'name', recordType: 'travel_journal',
        fields: [
            F.text('name', 'Entry Title', { required: true }),
            F.area('description', 'Content'),
            F.text('location', 'Location'),
            F.num('amount', 'Expense'),
            F.date('date', 'Date'),
        ],
    },
    '69': {
        id: '69', title: 'Travel Documents', subtitle: 'Visa & Document Tracker',
        basePath: '/home-travel/travel_documents', nameKey: 'name', recordType: 'travel_documents',
        fields: [
            F.text('name', 'Document Name', { required: true }),
            F.area('description', 'Details'),
            F.date('due_date', 'Expiry Date'),
            F.select('status', 'Status', STATUS([{ value: 'valid', label: 'Valid' }, { value: 'expired', label: 'Expired' }])),
        ],
    },
    '70': {
        id: '70', title: 'Vehicles', subtitle: 'Maintenance Log',
        basePath: '/home-travel/vehicles', nameKey: 'name', recordType: 'vehicles',
        fields: [
            F.text('name', 'Vehicle', { required: true }),
            F.area('description', 'Maintenance Log'),
            F.date('due_date', 'Next Service'),
            F.num('amount', 'Cost'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '71': {
        id: '71', title: 'Routes', subtitle: 'Personal Route Planner',
        basePath: '/home-travel/routes', nameKey: 'name', recordType: 'routes',
        fields: [
            F.text('name', 'Route Name', { required: true }),
            F.area('description', 'Details'),
            F.num('amount', 'Distance / Cost'),
            F.select('status', 'Status', STATUS()),
        ],
    },

    /* ─── Phase 2.8 Career & Business ─── */
    '72': {
        id: '72', title: 'Resumes', subtitle: 'Resume & Portfolio Versions',
        basePath: '/career/resumes', nameKey: 'title', recordType: 'resumes',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.area('description', 'Content / Notes'),
            F.select('status', 'Status', STATUS([{ value: 'draft', label: 'Draft' }, { value: 'final', label: 'Final' }])),
        ],
    },
    '73': {
        id: '73', title: 'Job Applications', subtitle: 'Interview Tracker',
        basePath: '/career/job_applications', nameKey: 'title', recordType: 'job_applications',
        fields: [
            F.text('title', 'Position', { required: true }),
            F.text('organization', 'Company'),
            F.select('status', 'Status', [
                { value: 'applied', label: 'Applied' },
                { value: 'interviewing', label: 'Interviewing' },
                { value: 'offered', label: 'Offered' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'accepted', label: 'Accepted' },
            ]),
            F.date('record_date', 'Applied On'),
            F.date('next_date', 'Next Step'),
            F.area('details', 'Details'),
        ],
    },
    '74': {
        id: '74', title: 'Achievements', subtitle: 'Salary & Achievement Log',
        basePath: '/career/achievements', nameKey: 'title', recordType: 'achievements',
        fields: [
            F.text('title', 'Achievement', { required: true }),
            F.text('organization', 'Company'),
            F.num('amount', 'Amount / Value'),
            F.date('record_date', 'Date'),
            F.area('description', 'Description'),
        ],
    },
    '75': {
        id: '75', title: 'Meetings', subtitle: 'Notes & Action Items',
        basePath: '/career/meetings', nameKey: 'title', recordType: 'meetings',
        fields: [
            F.text('title', 'Meeting Title', { required: true }),
            F.text('organization', 'Attendees'),
            F.dt('record_date', 'Date & Time'),
            F.area('description', 'Notes'),
            F.area('details', 'Action Items'),
            F.select('status', 'Status', STATUS([{ value: 'scheduled', label: 'Scheduled' }, { value: 'completed', label: 'Completed' }])),
        ],
    },
    '76': {
        id: '76', title: 'Invoices', subtitle: 'Freelance Client & Invoice',
        basePath: '/career/invoices', nameKey: 'title', recordType: 'invoices',
        fields: [
            F.text('title', 'Invoice #', { required: true }),
            F.text('organization', 'Client'),
            F.num('amount', 'Amount', { required: true }),
            F.date('record_date', 'Issued'),
            F.date('next_date', 'Due'),
            F.select('status', 'Status', [
                { value: 'draft', label: 'Draft' },
                { value: 'sent', label: 'Sent' },
                { value: 'paid', label: 'Paid' },
                { value: 'overdue', label: 'Overdue' },
            ]),
        ],
    },
    '77': {
        id: '77', title: 'CRM Contacts', subtitle: 'Relationship Health',
        basePath: '/career/relationships', nameKey: 'title', recordType: 'relationships',
        fields: [
            F.text('title', 'Contact Name', { required: true }),
            F.text('organization', 'Organization'),
            F.area('description', 'Notes'),
            F.date('next_date', 'Next Follow-up'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '78': {
        id: '78', title: 'Follow-ups', subtitle: 'Email Follow-up Organizer',
        basePath: '/career/follow_ups', nameKey: 'title', recordType: 'follow_ups',
        fields: [
            F.text('title', 'Subject', { required: true }),
            F.text('organization', 'Contact'),
            F.date('record_date', 'Sent Date'),
            F.date('next_date', 'Follow-up Date'),
            F.select('status', 'Status', [
                { value: 'pending', label: 'Pending' },
                { value: 'sent', label: 'Sent' },
                { value: 'received', label: 'Received' },
                { value: 'closed', label: 'Closed' },
            ]),
        ],
    },
    '79': {
        id: '79', title: 'Templates', subtitle: 'Template & Snippet Manager',
        basePath: '/career/templates', nameKey: 'title', recordType: 'templates',
        fields: [
            F.text('title', 'Template Name', { required: true }),
            F.area('description', 'Content'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '80': {
        id: '80', title: 'Important Dates', subtitle: 'Birthday & Anniversary Engine',
        basePath: '/career/important_dates', nameKey: 'title', recordType: 'important_dates',
        fields: [
            F.text('title', 'Event Name', { required: true }),
            F.date('record_date', 'Date'),
            F.select('status', 'Status', STATUS()),
            F.area('description', 'Notes'),
        ],
    },
    '81': {
        id: '81', title: 'Gifts', subtitle: 'Gift Planner',
        basePath: '/career/gifts', nameKey: 'title', recordType: 'gifts',
        fields: [
            F.text('title', 'Gift Idea', { required: true }),
            F.text('organization', 'For'),
            F.num('amount', 'Budget'),
            F.date('record_date', 'Occasion Date'),
            F.select('status', 'Status', [
                { value: 'idea', label: 'Idea' },
                { value: 'purchased', label: 'Purchased' },
                { value: 'given', label: 'Given' },
            ]),
        ],
    },
    '82': {
        id: '82', title: 'Family Records', subtitle: 'Emergency Info Vault',
        basePath: '/career/family_records', nameKey: 'title', recordType: 'family_records',
        fields: [
            F.text('title', 'Record Name', { required: true }),
            F.area('description', 'Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },

    /* ─── Phase 2.9 Entertainment & Writing ─── */
    '83': {
        id: '83', title: 'Watch Tracker', subtitle: 'Unified Media Watch',
        basePath: '/entertainment/watch', nameKey: 'title', recordType: 'watch',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.text('creator', 'Director / Creator'),
            F.num('rating', 'Rating (1-5)'),
            F.select('status', 'Status', [
                { value: 'to_watch', label: 'To Watch' },
                { value: 'watching', label: 'Watching' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.area('description', 'Notes'),
            F.text('tags', 'Tags'),
        ],
    },
    '84': {
        id: '84', title: 'Reading Tracker', subtitle: 'Reading Tracker',
        basePath: '/entertainment/reading', nameKey: 'title', recordType: 'reading',
        fields: [
            F.text('title', 'Book / Article', { required: true }),
            F.text('creator', 'Author'),
            F.num('rating', 'Rating (1-5)'),
            F.select('status', 'Status', [
                { value: 'to_read', label: 'To Read' },
                { value: 'reading', label: 'Reading' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.area('description', 'Notes'),
            F.text('tags', 'Tags'),
        ],
    },
    '85': {
        id: '85', title: 'Music & Podcasts', subtitle: 'Library',
        basePath: '/entertainment/music', nameKey: 'title', recordType: 'music',
        fields: [
            F.text('title', 'Title', { required: true }),
            F.text('creator', 'Artist / Host'),
            F.num('rating', 'Rating (1-5)'),
            F.select('status', 'Status', [
                { value: 'to_listen', label: 'To Listen' },
                { value: 'listening', label: 'Listening' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.area('description', 'Notes'),
            F.text('tags', 'Tags'),
        ],
    },
    '86': {
        id: '86', title: 'Chess Log', subtitle: 'Game Log & Repertoire',
        basePath: '/entertainment/chess', nameKey: 'title', recordType: 'chess',
        fields: [
            F.text('title', 'Game / Opening', { required: true }),
            F.select('status', 'Status', [
                { value: 'win', label: 'Win' },
                { value: 'loss', label: 'Loss' },
                { value: 'draw', label: 'Draw' },
            ]),
            F.num('rating', 'Rating'),
            F.area('description', 'Notes'),
            F.text('tags', 'Tags'),
        ],
    },
    '87': {
        id: '87', title: 'Wishlist', subtitle: 'Wishlist Manager',
        basePath: '/entertainment/wishlist', nameKey: 'title', recordType: 'wishlist',
        fields: [
            F.text('title', 'Item', { required: true }),
            F.num('amount', 'Price'),
            F.text('creator', 'Where'),
            F.select('status', 'Status', [
                { value: 'wanted', label: 'Wanted' },
                { value: 'purchased', label: 'Purchased' },
            ]),
            F.area('description', 'Notes'),
            F.text('tags', 'Tags'),
        ],
    },
    '88': {
        id: '88', title: 'Writing Projects', subtitle: 'Writing Project Tracker',
        basePath: '/entertainment/writing', nameKey: 'title', recordType: 'writing',
        fields: [
            F.text('title', 'Project Name', { required: true }),
            F.select('status', 'Status', [
                { value: 'idea', label: 'Idea' },
                { value: 'drafting', label: 'Drafting' },
                { value: 'editing', label: 'Editing' },
                { value: 'completed', label: 'Completed' },
            ]),
            F.area('description', 'Description'),
            F.num('rating', 'Priority'),
            F.text('tags', 'Tags'),
        ],
    },
    '89': {
        id: '89', title: 'Blog & Publishing', subtitle: 'Content Publishing Manager',
        basePath: '/entertainment/publishing', nameKey: 'title', recordType: 'publishing',
        fields: [
            F.text('title', 'Post Title', { required: true }),
            F.select('status', 'Status', [
                { value: 'draft', label: 'Draft' },
                { value: 'published', label: 'Published' },
                { value: 'scheduled', label: 'Scheduled' },
            ]),
            F.area('description', 'Content'),
            F.date('record_date', 'Publish Date'),
            F.text('tags', 'Tags'),
        ],
    },
    '90': {
        id: '90', title: 'Ideas', subtitle: 'Idea Inbox & Pipeline',
        basePath: '/entertainment/ideas', nameKey: 'title', recordType: 'ideas',
        fields: [
            F.text('title', 'Idea', { required: true }),
            F.area('description', 'Details'),
            F.select('status', 'Status', [
                { value: 'inbox', label: 'Inbox' },
                { value: 'evaluating', label: 'Evaluating' },
                { value: 'actionable', label: 'Actionable' },
                { value: 'archived', label: 'Archived' },
            ]),
            F.num('rating', 'Priority (1-5)'),
            F.text('tags', 'Tags'),
        ],
    },

    /* ─── Phase 2.10 Dev Tools & DevOps ─── */
    '91': {
        id: '91', title: 'API Tests', subtitle: 'API Testing Platform',
        basePath: '/devops/api_testing', nameKey: 'name', recordType: 'api_testing',
        fields: [
            F.text('name', 'Test Name', { required: true }),
            F.area('description', 'Endpoint / Description'),
            F.text('url', 'URL'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '92': {
        id: '92', title: 'Scaffolding', subtitle: 'Project Scaffolding Generator',
        basePath: '/devops/scaffolding', nameKey: 'name', recordType: 'scaffolding',
        fields: [
            F.text('name', 'Template Name', { required: true }),
            F.area('description', 'Template Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '93': {
        id: '93', title: 'DB Schemas', subtitle: 'Database Schema Visualizer',
        basePath: '/devops/schemas', nameKey: 'name', recordType: 'schemas',
        fields: [
            F.text('name', 'Schema Name', { required: true }),
            F.area('description', 'Schema Definition'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '94': {
        id: '94', title: 'Packages', subtitle: 'Personal Package Registry',
        basePath: '/devops/packages', nameKey: 'name', recordType: 'packages',
        fields: [
            F.text('name', 'Package Name', { required: true }),
            F.area('description', 'Description'),
            F.select('status', 'Status', STATUS()),
            F.text('environment', 'Registry'),
        ],
    },
    '95': {
        id: '95', title: 'Dev Environments', subtitle: 'Local Dev Dashboard',
        basePath: '/devops/environments', nameKey: 'name', recordType: 'environments',
        fields: [
            F.text('name', 'Environment Name', { required: true }),
            F.text('environment', 'Type', { placeholder: 'local, staging, production' }),
            F.text('url', 'URL'),
            F.area('description', 'Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '96': {
        id: '96', title: 'Deployments', subtitle: 'Deployment Manager',
        basePath: '/devops/deployments', nameKey: 'name', recordType: 'deployments',
        fields: [
            F.text('name', 'Deployment Name', { required: true }),
            F.text('environment', 'Environment'),
            F.text('url', 'URL'),
            F.area('description', 'Notes'),
            F.select('status', 'Status', [
                { value: 'pending', label: 'Pending' },
                { value: 'deployed', label: 'Deployed' },
                { value: 'rolled_back', label: 'Rolled Back' },
            ]),
        ],
    },
    '97': {
        id: '97', title: 'Containers', subtitle: 'Docker Container Dashboard',
        basePath: '/devops/containers', nameKey: 'name', recordType: 'containers',
        fields: [
            F.text('name', 'Container Name', { required: true }),
            F.area('description', 'Details'),
            F.text('environment', 'Image'),
            F.select('status', 'Status', STATUS([
                { value: 'running', label: 'Running' },
                { value: 'stopped', label: 'Stopped' },
            ])),
        ],
    },
    '98': {
        id: '98', title: 'Servers', subtitle: 'Homelab Inventory',
        basePath: '/devops/servers', nameKey: 'name', recordType: 'servers',
        fields: [
            F.text('name', 'Server Name', { required: true }),
            F.text('environment', 'IP / Hostname'),
            F.text('url', 'URL'),
            F.area('description', 'Specs / Notes'),
            F.select('status', 'Status', STATUS([
                { value: 'online', label: 'Online' },
                { value: 'offline', label: 'Offline' },
            ])),
        ],
    },
    '99': {
        id: '99', title: 'Monitoring', subtitle: 'Server Monitoring Dashboard',
        basePath: '/devops/monitoring', nameKey: 'name', recordType: 'monitoring',
        fields: [
            F.text('name', 'Monitor Name', { required: true }),
            F.text('url', 'URL'),
            F.area('description', 'Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '100': {
        id: '100', title: 'Backups', subtitle: 'Backup Manager & Scheduler',
        basePath: '/devops/backup', nameKey: 'name', recordType: 'backup',
        fields: [
            F.text('name', 'Backup Name', { required: true }),
            F.area('description', 'Details'),
            F.date('due_date', 'Next Backup'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '101': {
        id: '101', title: 'Certificates', subtitle: 'Domain & SSL Certificate Tracker',
        basePath: '/devops/certificates', nameKey: 'name', recordType: 'certificates',
        fields: [
            F.text('name', 'Domain / Cert Name', { required: true }),
            F.date('due_date', 'Expiry Date'),
            F.area('description', 'Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },

    /* ─── Phase 2.11 Security & Analytics ─── */
    '102': {
        id: '102', title: 'Security Audits', subtitle: 'Security Audit Dashboard',
        basePath: '/security-analytics/security_audits', nameKey: 'name', recordType: 'security_audits',
        fields: [
            F.text('name', 'Audit Name', { required: true }),
            F.area('description', 'Findings'),
            F.select('status', 'Status', [
                { value: 'pass', label: 'Pass' },
                { value: 'fail', label: 'Fail' },
                { value: 'warning', label: 'Warning' },
            ]),
            F.dt('recorded_at', 'Audit Date'),
        ],
    },
    '103': {
        id: '103', title: 'Devices', subtitle: 'Device Inventory & Login History',
        basePath: '/security-analytics/devices', nameKey: 'name', recordType: 'devices',
        fields: [
            F.text('name', 'Device Name', { required: true }),
            F.area('description', 'Details'),
            F.dt('recorded_at', 'Last Seen'),
            F.select('status', 'Status', [
                { value: 'trusted', label: 'Trusted' },
                { value: 'unknown', label: 'Unknown' },
                { value: 'revoked', label: 'Revoked' },
            ]),
        ],
    },
    '104': {
        id: '104', title: 'Analytics Dashboard', subtitle: 'Unified Analytics & KPI',
        basePath: '/security-analytics/analytics', nameKey: 'name', recordType: 'analytics',
        fields: [
            F.text('name', 'Metric Name', { required: true }),
            F.num('value', 'Value'),
            F.area('description', 'Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '105': {
        id: '105', title: 'Life Statistics', subtitle: 'Life Statistics Engine',
        basePath: '/security-analytics/life_statistics', nameKey: 'name', recordType: 'life_statistics',
        fields: [
            F.text('name', 'Statistic Name', { required: true }),
            F.num('value', 'Value'),
            F.area('description', 'Details'),
            F.select('status', 'Status', STATUS()),
        ],
    },
    '106': {
        id: '106', title: 'Reports', subtitle: 'Custom Report Builder',
        basePath: '/security-analytics/reports', nameKey: 'name', recordType: 'reports',
        fields: [
            F.text('name', 'Report Name', { required: true }),
            F.area('description', 'Report Config / Content'),
            F.select('status', 'Status', STATUS()),
        ],
    },
};
