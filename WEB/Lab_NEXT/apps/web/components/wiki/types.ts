export interface Block {
    id: number | string;
    _key?: string;
    page_id?: number;
    parent_id?: number | null;
    type: string;
    content: string;
    meta: Record<string, any>;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
}

export interface Tag {
    id: number;
    name: string;
    color?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface Workspace {
    id: number;
    name: string;
    description: string | null;
    color: string | null;
    is_default: boolean;
    notebooks?: Notebook[];
    projects?: Project[];
    created_at: string;
    updated_at: string;
}

export interface Notebook {
    id: number;
    workspace_id: number;
    name: string;
    description: string | null;
    icon: string | null;
    sort_order: number;
    sections?: Section[];
    created_at: string;
    updated_at: string;
}

export interface Section {
    id: number;
    notebook_id: number;
    name: string;
    description: string | null;
    icon: string | null;
    sort_order: number;
    pages?: Page[];
    created_at: string;
    updated_at: string;
}

export interface Page {
    id: number;
    section_id: number;
    project_id: number | null;
    title: string;
    content: string | null;
    icon: string | null;
    type: string;
    status: string;
    difficulty: string | null;
    is_favorite: boolean;
    pinned_at: string | null;
    sort_order: number;
    blocks?: Block[];
    tags?: Tag[];
    versions?: PageVersion[];
    deleted_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface PageVersion {
    id: number;
    page_id: number;
    title: string;
    content: string | null;
    blocks: Block[] | null;
    note: string | null;
    created_at: string;
    updated_at: string;
}

export interface Template {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    blocks: Block[];
    created_at: string;
    updated_at: string;
}

export interface Project {
    id: number;
    workspace_id: number;
    name: string;
    description: string | null;
    status: string;
    color: string | null;
    sort_order: number;
    pages?: Page[];
    deleted_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Question {
    id: number;
    workspace_id: number;
    title: string;
    answer: string | null;
    status: string;
    difficulty: string | null;
    deleted_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Reference {
    id: number;
    workspace_id: number;
    title: string;
    url: string | null;
    type: string;
    author: string | null;
    credibility: number | null;
    notes: string | null;
    deleted_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Task {
    id: number;
    workspace_id: number;
    title: string;
    done: boolean;
    due_date: string | null;
    priority: string;
    taskable_type: string | null;
    taskable_id: number | null;
    notes: string | null;
    deleted_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Review {
    id: number;
    workspace_id: number;
    page_id: number | null;
    type: string;
    status: string;
    scheduled_for: string | null;
    next_review_at: string | null;
    last_reviewed_at: string | null;
    notes: string | null;
    page?: Page | null;
    deleted_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Backlink {
    id: number;
    title: string;
    icon: string | null;
    snippet: string;
}
