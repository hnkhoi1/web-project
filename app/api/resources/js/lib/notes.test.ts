import { describe, expect, it } from 'vite-plus/test';
import type { Note } from '@/types';
import { filterNotes } from './notes';

const make = (title: string, content: string | null = null): Note => ({
    id: 1,
    title,
    content,
    color: 'yellow',
    pinned: false,
    source_type: null,
    source_ref: null,
    created_at: '',
    updated_at: '',
});

describe('filterNotes', () => {
    const notes = [make('Ghi chú A'), make('Khác', 'Mức phạt đơn giản')];

    it('ignores case and diacritics', () => {
        expect(filterNotes(notes, 'ghi chu')).toEqual([notes[0]]);
        expect(filterNotes(notes, 'GHI CHÚ')).toEqual([notes[0]]);
        expect(filterNotes(notes, 'don')).toEqual([notes[1]]);
    });

    it('returns everything for an empty query', () => {
        expect(filterNotes(notes, '')).toEqual(notes);
        expect(filterNotes(notes, '   ')).toEqual(notes);
    });

    it('matches a decomposed query against a composed note', () => {
        expect(filterNotes(notes, 'ghi chú'.normalize('NFD'))).toEqual([
            notes[0],
        ]);
        expect(filterNotes(notes, 'Ghi chú'.normalize('NFD'))).toEqual([
            notes[0],
        ]);
    });
});
