import type { ReactNode } from 'react';
import { AppHeader } from '@/components/app-header';
import { SiteFooter } from '@/components/site-footer';
import { Toaster } from '@/components/ui/sonner';
import { useSyncPrefs } from '@/hooks/use-appearance';

export default function AppLayout({ children }: { children: ReactNode }) {
    useSyncPrefs();

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <AppHeader />
            <main className="grid w-full flex-1 grid-cols-[minmax(1.25rem,1fr)_minmax(0,69.5rem)_minmax(1.25rem,1fr)] content-start py-6 *:col-start-2">
                {children}
            </main>
            <SiteFooter />
            <Toaster />
        </div>
    );
}
