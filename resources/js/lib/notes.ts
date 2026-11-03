import type { Note } from '@/types';

/** Lowercase, strip diacritics (including đ) and compare in one canonical form. */
export function fold(text: string): string {
    return text
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .replace(/đ/gi, 'd')
        .toLowerCase()
        .trim();
}

/** Notes whose title or content contains the query, ignoring case and accents. */
export function filterNotes(notes: Note[], q: string): Note[] {
    const needle = fold(q);

    if (needle === '') {
        return notes;
    }

    return notes.filter((n) =>
        fold(`${n.title}\n${n.content ?? ''}`).includes(needle),
    );
}
