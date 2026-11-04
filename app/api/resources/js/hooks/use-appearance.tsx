import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { update } from '@/routes/preferences';
import type { Prefs, Theme } from '@/types';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = Theme;

const prefersDark = (): boolean =>
    window.matchMedia('(prefers-color-scheme: dark)').matches;

/** Apply a theme to <html>. The server renders the same state before first paint. */
export function applyAppearance(theme: Theme): void {
    const root = document.documentElement;
    const isDark = theme === 'dark' || (theme === 'system' && prefersDark());

    root.classList.toggle('dark', isDark);
    root.style.colorScheme = isDark ? 'dark' : 'light';
    root.dataset.theme = theme;
}

/** Follow the operating system while the theme is "system". */
export function initializeTheme(): void {
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            if (document.documentElement.dataset.theme === 'system') {
                applyAppearance('system');
            }
        });
}

/**
 * Keep <html> in step with the shared prefs, e.g. after login or logout, when
 * the document is not reloaded.
 */
export function useSyncPrefs(): void {
    const { prefs } = usePage().props;

    useEffect(() => {
        applyAppearance(prefs.theme);
        document.documentElement.dataset.font = prefs.font;
        document.documentElement.dataset.noteColor = prefs.noteColor;
    }, [prefs.theme, prefs.font, prefs.noteColor]);
}

export function usePrefs() {
    const { prefs, auth } = usePage().props;

    const updatePrefs = (change: Partial<Prefs>): void => {
        const next: Prefs = { ...prefs, ...change };

        if (change.theme) {
            applyAppearance(change.theme);
        }

        if (auth.user) {
            router.put(update().url, next, {
                preserveScroll: true,
                preserveState: true,
            });
        }
    };

    return { prefs, updatePrefs } as const;
}

export function useAppearance() {
    const { prefs, updatePrefs } = usePrefs();
    const resolvedAppearance: ResolvedAppearance =
        prefs.theme === 'dark' || (prefs.theme === 'system' && prefersDark())
            ? 'dark'
            : 'light';

    return {
        appearance: prefs.theme,
        resolvedAppearance,
        updateAppearance: (theme: Theme): void => updatePrefs({ theme }),
    } as const;
}
