import type { ReactNode } from 'react';
import { AppHeader } from '@/components/app-header';
import { Toaster } from '@/components/ui/sonner';
import { useSyncPrefs } from '@/hooks/use-appearance';

export default function AppLayout({ children }: { children: ReactNode }) {
    useSyncPrefs();

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <AppHeader />
            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6">
                {children}
            </main>
            <Toaster />
        </div>
    );
}
