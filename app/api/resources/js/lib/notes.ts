import type { Note, NoteColor } from '@/types';

/** Lowercase, strip diacritics (including đ) and compare in one canonical form. */
export function fold(text: string): string {
    return text
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .replace(/đ/gi, 'd')
        .toLowerCase()
        .trim();
}

export type NoteFilters = {
    pinned: boolean;
    color: NoteColor | null;
};

export const NO_FILTERS: NoteFilters = { pinned: false, color: null };

/**
 * Notes whose title or content contains the query (ignoring case and accents),
 * narrowed to pinned and/or one colour when asked.
 */
export function filterNotes(
    notes: Note[],
    q: string,
    filters: NoteFilters = NO_FILTERS,
): Note[] {
    const needle = fold(q);

    return notes.filter(
        (n) =>
            (!filters.pinned || n.pinned) &&
            (filters.color === null || n.color === filters.color) &&
            (needle === '' ||
                fold(`${n.title}\n${n.content ?? ''}`).includes(needle)),
    );
}

const pad = (n: number): string => String(n).padStart(2, '0');

function stamp(iso: string): string {
    const d = new Date(iso);

    return Number.isNaN(d.getTime())
        ? ''
        : `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function source(note: Note): string | null {
    if (note.source_type === 'article' && note.source_ref) {
        return `Điều ${note.source_ref}`;
    }

    return note.source_type === 'chat' ? 'Từ chatbot' : null;
}

/** Plain-text export of notes, readable in any editor (CRLF line endings). */
export function notesToText(
    notes: Note[],
    appName: string,
    now: Date = new Date(),
): string {
    const rule = '='.repeat(40);
    const lines: string[] = [
        `Ghi chú - ${appName}`,
        `Xuất ngày ${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} · ${notes.length} ghi chú`,
        rule,
        '',
    ];

    for (const n of notes) {
        lines.push(`${n.pinned ? '[Ghim] ' : ''}${n.title}`);

        const meta = [stamp(n.updated_at), source(n)].filter(Boolean);

        if (meta.length > 0) {
            lines.push(meta.join(' · '));
        }

        if (n.content) {
            lines.push('', n.content.replace(/\r?\n/g, '\n'));
        }

        lines.push('', '-'.repeat(40), '');
    }

    return lines.join('\n').replace(/\n/g, '\r\n');
}

export function notesFileName(now: Date = new Date()): string {
    return `ghi-chu-${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}.txt`;
}

/** Save text as a UTF-8 file in the browser (with a BOM so older editors read the accents). */
export function downloadText(filename: string, text: string): void {
    const url = URL.createObjectURL(
        new Blob(['﻿', text], { type: 'text/plain;charset=utf-8' }),
    );
    const a = document.createElement('a');

    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
