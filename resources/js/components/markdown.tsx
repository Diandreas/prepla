import React from 'react';

/**
 * Lightweight, dependency-free Markdown renderer.
 *
 * Supports the subset the AI tutor actually emits: headings (#, ##, ###),
 * paragraphs, unordered/ordered lists, blockquotes, fenced/indented-as-pipe
 * tables, inline **bold** / *italic* / `code`, and ``` fenced code blocks.
 *
 * Adapted from the battle-tested parser in pages/learning/lesson.tsx so the
 * chat tutor and lessons render markdown identically. Kept neutral so it works
 * inside a chat bubble, a card, or anywhere else.
 */

function parseInline(text: string): string {
    return (text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|[^*])\*(?!\s)([^*]+?)\*(?!\*)/g, '$1<em>$2</em>')
        .replace(/`([^`]+?)`/g, '<code class="rounded bg-black/10 px-1 py-0.5 text-[0.85em] font-mono">$1</code>');
}

interface Block {
    type: 'h1' | 'h2' | 'h3' | 'text' | 'list' | 'olist' | 'quote' | 'table' | 'code' | 'empty';
    lines: string[];
}

function toBlocks(md: string): Block[] {
    const lines = md.replace(/\r\n/g, '\n').replace(/^﻿/, '').split('\n');
    const blocks: Block[] = [];
    let inCode = false;

    for (const line of lines) {
        const trimmed = line.trim();
        const last = blocks[blocks.length - 1];

        // Fenced code block toggle
        if (trimmed.startsWith('```')) {
            if (inCode) { inCode = false; }
            else { inCode = true; blocks.push({ type: 'code', lines: [] }); }
            continue;
        }
        if (inCode) {
            if (last?.type === 'code') last.lines.push(line);
            continue;
        }

        if (!trimmed) {
            if (last?.type !== 'empty') blocks.push({ type: 'empty', lines: [] });
            continue;
        }

        if (trimmed.startsWith('#')) {
            const level = Math.min((trimmed.match(/^#+/) || ['#'])[0].length, 3);
            blocks.push({ type: `h${level}` as Block['type'], lines: [trimmed.replace(/^#+\s*/, '')] });
        } else if (/^\d+\.\s+/.test(trimmed)) {
            const content = trimmed.replace(/^\d+\.\s+/, '');
            if (last?.type === 'olist') last.lines.push(content);
            else blocks.push({ type: 'olist', lines: [content] });
        } else if (trimmed.startsWith('|')) {
            if (last?.type === 'table') last.lines.push(trimmed);
            else blocks.push({ type: 'table', lines: [trimmed] });
        } else if (/^[-*]\s+/.test(trimmed)) {
            const content = trimmed.replace(/^[-*]\s+/, '');
            if (last?.type === 'list') last.lines.push(content);
            else blocks.push({ type: 'list', lines: [content] });
        } else if (trimmed.startsWith('>')) {
            const content = trimmed.replace(/^>\s*/, '');
            if (last?.type === 'quote') last.lines.push(content);
            else blocks.push({ type: 'quote', lines: [content] });
        } else {
            blocks.push({ type: 'text', lines: [trimmed] });
        }
    }
    return blocks;
}

export function Markdown({ content, className = '' }: { content: string; className?: string }) {
    if (!content) return null;
    const blocks = toBlocks(content);

    return (
        <div className={`markdown-body space-y-2 leading-relaxed ${className}`}>
            {blocks.map((block, bi) => {
                switch (block.type) {
                    case 'h1':
                        return <h1 key={bi} className="mt-2 text-base font-black" dangerouslySetInnerHTML={{ __html: parseInline(block.lines[0]) }} />;
                    case 'h2':
                        return <h2 key={bi} className="mt-2 text-sm font-black" dangerouslySetInnerHTML={{ __html: parseInline(block.lines[0]) }} />;
                    case 'h3':
                        return <h3 key={bi} className="mt-1 text-sm font-bold" dangerouslySetInnerHTML={{ __html: parseInline(block.lines[0]) }} />;
                    case 'empty':
                        return null;
                    case 'text':
                        return <p key={bi} dangerouslySetInnerHTML={{ __html: parseInline(block.lines[0]) }} />;
                    case 'quote':
                        return (
                            <blockquote key={bi} className="border-l-2 border-current/30 pl-3 italic opacity-90">
                                {block.lines.map((l, li) => (
                                    <p key={li} dangerouslySetInnerHTML={{ __html: parseInline(l) }} />
                                ))}
                            </blockquote>
                        );
                    case 'code':
                        return (
                            <pre key={bi} className="overflow-x-auto rounded-lg bg-black/10 p-3 text-[0.8em]">
                                <code className="font-mono whitespace-pre">{block.lines.join('\n')}</code>
                            </pre>
                        );
                    case 'list':
                        return (
                            <ul key={bi} className="ml-1 space-y-1">
                                {block.lines.map((l, li) => (
                                    <li key={li} className="flex gap-2">
                                        <span className="mt-[0.45em] h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-60" />
                                        <span dangerouslySetInnerHTML={{ __html: parseInline(l) }} />
                                    </li>
                                ))}
                            </ul>
                        );
                    case 'olist':
                        return (
                            <ol key={bi} className="ml-1 space-y-1">
                                {block.lines.map((l, li) => (
                                    <li key={li} className="flex gap-2">
                                        <span className="shrink-0 font-bold opacity-70">{li + 1}.</span>
                                        <span dangerouslySetInnerHTML={{ __html: parseInline(l) }} />
                                    </li>
                                ))}
                            </ol>
                        );
                    case 'table': {
                        const rows = block.lines.filter(l => !/^\s*\|?[\s:|-]+\|?\s*$/.test(l)); // drop |---|---| separator
                        if (rows.length === 0) return null;
                        const parse = (l: string) => {
                            const parts = l.split('|');
                            if (parts[0].trim() === '') parts.shift();
                            if (parts[parts.length - 1]?.trim() === '') parts.pop();
                            return parts.map(c => c.trim());
                        };
                        const data = rows.map(parse);
                        return (
                            <div key={bi} className="my-2 overflow-x-auto rounded-lg border border-current/15">
                                <table className="w-full border-collapse text-left text-[0.85em]">
                                    <thead className="bg-current/5">
                                        <tr>
                                            {data[0].map((h, hi) => (
                                                <th key={hi} className="border-b border-current/15 px-3 py-1.5 font-bold" dangerouslySetInnerHTML={{ __html: parseInline(h) }} />
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.slice(1).map((row, ri) => (
                                            <tr key={ri} className="border-b border-current/10 last:border-0">
                                                {row.map((cell, ci) => (
                                                    <td key={ci} className="px-3 py-1.5" dangerouslySetInnerHTML={{ __html: parseInline(cell || '') }} />
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        );
                    }
                    default:
                        return null;
                }
            })}
        </div>
    );
}
