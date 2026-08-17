'use client';

import { useState } from 'react';
import { Check, Plus, Search, Trash2, Sun, Moon, MoreHorizontal } from 'lucide-react';
import {
    Avatar,
    Badge,
    Button,
    Card,
    Checkbox,
    DropdownMenu,
    EmptyState,
    Input,
    Modal,
    PageHeader,
    ProgressBar,
    Select,
    Skeleton,
    Spinner,
    Switch,
    Table,
    Tabs,
    Tag,
    ToastProvider,
    Tooltip,
    useToast,
} from '@ctlab/ctlab-ui';
import { useTheme } from '@ctlab/ctlab-theme';

interface Row {
    id: number;
    name: string;
    status: 'Active' | 'Paused' | 'Archived';
    updated: string;
}

const rows: Row[] = [
    { id: 1, name: 'Second Brain', status: 'Active', updated: '2m ago' },
    { id: 2, name: 'Planner', status: 'Paused', updated: '1h ago' },
    { id: 3, name: 'Finance', status: 'Archived', updated: '3d ago' },
];

function DemoContent() {
    const { mode, toggle } = useTheme();
    const { notify } = useToast();
    const [modalOpen, setModalOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [darkSwitch, setDarkSwitch] = useState(true);
    const [news, setNews] = useState(false);
    const [tab, setTab] = useState('blocks');

    return (
        <div className="max-w-3xl space-y-10 py-2">
            <PageHeader
                title="Design System"
                subtitle="Live gallery of ctlab-ui components on the ctlab-theme tokens."
                actions={
                    <Button variant="secondary" size="sm" icon={mode === 'dark' ? <Sun size={16} /> : <Moon size={16} />} onClick={toggle}>
                        {mode === 'dark' ? 'Light' : 'Dark'}
                    </Button>
                }
            />

            <Card title="Buttons" subtitle="variants × sizes">
                <div className="flex flex-wrap items-center gap-3">
                    <Button>Primary</Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="danger">Danger</Button>
                    <Button variant="ghost">Ghost</Button>
                    <Button size="sm" icon={<Plus size={14} />}>
                        Sm + icon
                    </Button>
                    <Button size="lg" variant="secondary">
                        Large
                    </Button>
                    <Button
                        loading={loading}
                        onClick={() => {
                            setLoading(true);
                            setTimeout(() => setLoading(false), 1200);
                        }}
                    >
                        Loading
                    </Button>
                    <Button disabled>Disabled</Button>
                </div>
            </Card>

            <Card title="Inputs" subtitle="label, hint, error, icon">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="Email" placeholder="you@example.com" />
                    <Input label="Search" placeholder="Search anything…" icon={<Search size={16} />} />
                    <Input label="Password" hint="At least 8 characters" type="password" defaultValue="secret123" />
                    <Input label="Bad value" error="This field is required." defaultValue="" invalid />
                    <Select label="Workspace" options={[{ value: 'brain', label: 'Second Brain' }, { value: 'work', label: 'Work' }]} />
                    <Select label="Invalid select" invalid error="Pick one." defaultValue="" />
                </div>
            </Card>

            <Card title="Selection controls" subtitle="switch + checkbox">
                <div className="flex flex-wrap items-center gap-6">
                    <Switch checked={darkSwitch} onChange={setDarkSwitch} label="Compact mode" />
                    <Switch checked={false} onChange={() => {}} label="Disabled" disabled />
                    <Checkbox checked={news} onChange={(e) => setNews(e.target.checked)} label="Subscribe to digest" />
                    <Checkbox defaultChecked label="Checked" />
                    <Checkbox disabled label="Disabled" />
                </div>
            </Card>

            <Card title="Tabs & tags" subtitle="navigation + chips">
                <div className="space-y-4">
                    <Tabs
                        active={tab}
                        onChange={setTab}
                        tabs={[
                            { key: 'blocks', label: 'Blocks' },
                            { key: 'markdown', label: 'Markdown' },
                            { key: 'preview', label: 'Preview' },
                        ]}
                    />
                    <p className="text-sm text-[var(--color-muted)]">Active tab: {tab}</p>
                    <div className="flex flex-wrap gap-2">
                        <Tag>plain</Tag>
                        <Tag tone="primary" removable onRemove={() => {}}>primary</Tag>
                        <Tag tone="success">success</Tag>
                        <Tag tone="warning">warning</Tag>
                        <Tag tone="danger" removable onRemove={() => {}}>danger</Tag>
                        <Tag tone="info">info</Tag>
                    </div>
                </div>
            </Card>

            <Card title="Overlay & data" subtitle="tooltip + dropdown + avatar + progress">
                <div className="space-y-5">
                    <div className="flex flex-wrap items-center gap-6">
                        <Tooltip content="Keyboard shortcuts (⌘K)">
                            <Button variant="secondary" size="sm">Hover me</Button>
                        </Tooltip>
                        <DropdownMenu
                            trigger={<Button variant="ghost" size="sm" aria-label="Menu"><MoreHorizontal size={16} /></Button>}
                            items={[
                                { key: 'edit', label: 'Edit', onSelect: () => notify('Edit selected', 'info') },
                                { key: 'dup', label: 'Duplicate' },
                                { key: 'del', label: 'Delete', danger: true, onSelect: () => notify('Deleted (not really)', 'danger') },
                            ]}
                            align="start"
                        />
                        <div className="flex items-center gap-2">
                            <Avatar name="Ada Lovelace" size="sm" />
                            <Avatar name="Grace Hopper" />
                            <Avatar name="Alan Turing" size="lg" />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <ProgressBar value={72} label="Storage" />
                        <ProgressBar indeterminate label="Syncing" />
                    </div>
                </div>
            </Card>

            <Card title="Badges" subtitle="semantic tones">
                <div className="flex flex-wrap gap-2">
                    <Badge>neutral</Badge>
                    <Badge tone="success">success</Badge>
                    <Badge tone="warning">warning</Badge>
                    <Badge tone="danger">danger</Badge>
                    <Badge tone="info">info</Badge>
                    <Badge tone="primary">primary</Badge>
                </div>
            </Card>

            <Card title="Table" subtitle="typed columns, hover rows">
                <Table<Row>
                    columns={[
                        { key: 'name', header: 'Name', render: (r) => <span className="font-medium">{r.name}</span> },
                        { key: 'status', header: 'Status', render: (r) => <Badge tone={r.status === 'Active' ? 'success' : r.status === 'Paused' ? 'warning' : 'neutral'}>{r.status}</Badge> },
                        { key: 'updated', header: 'Updated', render: (r) => <span className="text-[var(--color-muted)]">{r.updated}</span> },
                        {
                            key: 'actions',
                            header: '',
                            render: () => (
                                <Button variant="ghost" size="sm" aria-label="Delete">
                                    <Trash2 size={14} />
                                </Button>
                            ),
                        },
                    ]}
                    rows={rows}
                    rowKey={(r) => r.id}
                />
            </Card>

            <Card title="Feedback" subtitle="toast + modal + spinner">
                <div className="flex flex-wrap gap-3">
                    <Button variant="secondary" onClick={() => notify('Saved successfully', 'success')}>
                        Success toast
                    </Button>
                    <Button variant="secondary" onClick={() => notify('Storage getting full', 'warning', 'Under 1 GB free')}>
                        Warning toast
                    </Button>
                    <Button variant="secondary" onClick={() => setModalOpen(true)}>
                        Open modal
                    </Button>
                    <Spinner />
                </div>
            </Card>

            <Card title="Loading & empty states">
                <div className="space-y-4">
                    <div className="flex flex-col gap-2">
                        <Skeleton height="1rem" width="60%" />
                        <Skeleton height="1rem" />
                        <Skeleton height="1rem" width="80%" />
                    </div>
                    <EmptyState
                        title="No widgets yet"
                        description="Wire up your first widget to see analytics here."
                        icon="📊"
                        action={<Button size="sm" icon={<Plus size={14} />}>Add widget</Button>}
                    />
                </div>
            </Card>

            <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Confirm action" footer={<>
                <Button variant="ghost" onClick={() => setModalOpen(false)}>Cancel</Button>
                <Button icon={<Check size={14} />} onClick={() => setModalOpen(false)}>Confirm</Button>
            </>}>
                <p className="text-sm text-[var(--color-muted)]">This modal demonstrates focus trapping, Esc-to-close, and backdrop dismissal.</p>
            </Modal>
        </div>
    );
}

export default function DesignPage() {
    return (
        <ToastProvider>
            <DemoContent />
        </ToastProvider>
    );
}
