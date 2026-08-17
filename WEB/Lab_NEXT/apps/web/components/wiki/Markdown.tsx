'use client';

import Link from 'next/link';
import type { ReactNode } from 'react';

interface MarkdownProps {
    content: string;
    pageIndex?: Record<string, number>;
}

function inline(text: string, keyPrefix: string, pageIndex: Record<string, number>): ReactNode[] {
    const nodes: ReactNode[] = [];
    const regex = /(\[\[.+?\]\]|`[^`]+`|\*\*[^*]+\*\*|__[^_]+__|\*[^*]+\*|_[^_]+_|\[([^\]]+)\]\(([^)\s]+)\))/g;
    let last = 0;
    let m: RegExpExecArray | null;
    let k = 0;
    while ((m = regex.exec(text)) !== null) {
        if (m.index > last) nodes.push(text.slice(last, m.index));
        const token = m[0];
        let node: ReactNode;
        if (token.startsWith('[[')) {
            const target = token.slice(2, -2).trim();
            const id = pageIndex[target.toLowerCase()] ?? null;
            node = id ? (
                <Link key={k} href={`/wiki/pages?id=${id}`} className="text-[var(--color-info)] underline decoration-dotted hover:decoration-solid">{target}</Link>
            ) : (
                <span key={k} className="text-[var(--color-muted)] underline decoration-dotted">{target}</span>
            );
        } else if (token.startsWith('`')) {
            node = <code key={k} className="px-1 py-0.5 rounded bg-[#1f2430] text-[#c3e88d] text-[0.9em]">{token.slice(1, -1)}</code>;
        } else if (token.startsWith('**') || token.startsWith('__')) {
            node = <strong key={k} className="font-semibold">{inline(token.slice(2, -2), `${keyPrefix}-${k}`, pageIndex)}</strong>;
        } else if (token.startsWith('*') || token.startsWith('_')) {
            node = <em key={k}>{inline(token.slice(1, -1), `${keyPrefix}-${k}`, pageIndex)}</em>;
        } else {
            node = (
                <a key={k} href={m[3]} target="_blank" rel="noopener noreferrer" className="text-[var(--color-info)] hover:underline">
                    {m[2]}
                </a>
            );
        }
        nodes.push(node);
        k++;
        last = m.index + token.length;
    }
    if (last < text.length) nodes.push(text.slice(last));
    return nodes;
}

function renderBlock(source: string, pageIndex: Record<string, number>): { key: string; node: ReactNode } {
    const line = source.trim();
    const key = line;

    if (line.startsWith('### ')) return { key, node: <h3 className="font-semibold leading-tight text-lg mt-4 mb-1">{inline(line.slice(4), key, pageIndex)}</h3> };
    if (line.startsWith('## ')) return { key, node: <h2 className="font-semibold leading-tight text-xl mt-5 mb-1">{inline(line.slice(3), key, pageIndex)}</h2> };
    if (line.startsWith('# ')) return { key, node: <h1 className="font-semibold leading-tight text-2xl mt-5 mb-1">{inline(line.slice(2), key, pageIndex)}</h1> };
    if (line.startsWith('> ')) return { key, node: <blockquote className="border-l-2 border-[var(--color-muted)] pl-3 italic text-[var(--color-muted)] py-1 my-2">{inline(line.slice(2), key, pageIndex)}</blockquote> };
    if (line === '---' || line === '***') return { key, node: <hr className="border-0 border-t border-[var(--color-border)] my-4" /> };
    if (line.startsWith('- [ ] ') || line.startsWith('- [x] ')) return { key, node: <div className="flex items-start gap-2 py-0.5"><span className="text-[var(--color-muted)]">{line.startsWith('- [x] ') ? '☑' : '☐'}</span><span className={line.startsWith('- [x] ') ? 'line-through text-[var(--color-muted)]' : ''}>{inline(line.slice(6), key, pageIndex)}</span></div> };
    if (line.startsWith('- ') || line.startsWith('* ')) return { key, node: <div className="flex items-start gap-2 py-0.5"><span className="text-[var(--color-muted)]">•</span><span>{inline(line.slice(2), key, pageIndex)}</span></div> };
    if (line.startsWith('1. ') || line.startsWith('1) ')) return { key, node: <div className="flex items-start gap-2 py-0.5"><span className="text-[var(--color-muted)]">{Number(key.replace(/\D/g, '') || 1)}.</span><span>{inline(line.slice(3), key, pageIndex)}</span></div> };
    return { key, node: <p className="py-0.5 leading-7">{inline(line, key, pageIndex)}</p> };
}

export default function Markdown({ content, pageIndex = {} }: MarkdownProps) {
    const blocks = (content || '').split('\n');
    const codePairs = new Set<number>();

    let fenceStart: number | null = null;
    for (let i = 0; i < blocks.length; i++) {
        if (blocks[i].startsWith('```')) {
            if (fenceStart === null) fenceStart = i;
            else {
                codePairs.add(fenceStart);
                codePairs.add(i);
                fenceStart = null;
            }
        }
    }

    const rendered: { key: string; node: ReactNode }[] = [];
    let i = 0;
    let fenceOpen = false;
    let lang = '';
    const codeLines: string[] = [];
    while (i < blocks.length) {
        const line = blocks[i];
        if (line.startsWith('```')) {
            if (!fenceOpen) {
                fenceOpen = true;
                lang = line.slice(3).trim();
                codeLines.length = 0;
            } else {
                const code = codeLines.join('\n');
                rendered.push({
                    key: `code-${i}`,
                    node: (
                        <pre className="m-0 my-2 p-4 rounded-lg bg-[#1f2430] text-[#e6e6e6] font-mono text-sm leading-6 overflow-x-auto">
                            <code>{code}</code>
                        </pre>
                    ),
                });
                fenceOpen = false;
                lang = '';
            }
            i++;
            continue;
        }
        if (fenceOpen) {
            codeLines.push(line);
            i++;
            continue;
        }
        if (codePairs.has(i)) { i++; continue; }
        if (line.trim() === '') { i++; continue; }
        const b = renderBlock(line, pageIndex);
        if (b.node) rendered.push({ ...b, key: `${b.key}-${i}-${rendered.length}` });
        i++;
    }
    if (fenceOpen && codeLines.length) {
        rendered.push({
            key: `code-${i}`,
            node: (
                <pre className="m-0 my-2 p-4 rounded-lg bg-[#1f2430] text-[#e6e6e6] font-mono text-sm leading-6 overflow-x-auto">
                    <code>{codeLines.join('\n')}</code>
                </pre>
            ),
        });
    }

    if (rendered.length === 0) {
        return <p className="text-[var(--color-muted)]/60 py-2">No markdown content.</p>;
    }

    return <div className="markdown prose-sm max-w-none">{rendered.map((r) => r.node)}</div>;
}

export function markdownToText(content?: string | null): string {
    if (!content) return '';
    return content
        .replace(/\[\[(.+?)\]\]/g, '$1')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/\[([^\]]+)\]\([^)\s]+\)/g, '$1')
        .replace(/(\*\*|__|\*|_)(.+?)\1/g, '$2')
        .replace(/^#{1,6}\s+/gm, '')
        .replace(/^>+\s?/gm, '')
        .replace(/^[-*]\s+/gm, '')
        .replace(/^1\.\s+/gm, '')
        .replace(/```/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}
