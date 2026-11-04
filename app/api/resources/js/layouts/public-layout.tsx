import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { useSyncPrefs } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import { login, register } from '@/routes';

/** Pages open to guests: members get the full app shell, guests a slim header with a login link. */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    if (auth.user) {
        return <AppLayout>{children}</AppLayout>;
    }

    return <GuestShell>{children}</GuestShell>;
}

function GuestShell({ children }: { children: ReactNode }) {
    useSyncPrefs();

    const links = [
        { title: 'Giới thiệu', href: '/gioi-thieu' },
        { title: 'Liên hệ', href: '/lien-he' },
    ];

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header className="border-b bg-background">
                <div className="mx-auto flex h-14 max-w-5xl items-center gap-2 px-4">
                    <Link href="/login" className="flex items-center gap-2">
                        <AppLogo />
                    </Link>
                    <nav
                        className="ml-4 flex items-center gap-1"
                        aria-label="Điều hướng chính"
                    >
                        {links.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                    <div className="ml-auto flex items-center gap-2">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={login()}>Đăng nhập</Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={register()}>Đăng ký</Link>
                        </Button>
                    </div>
                </div>
            </header>
            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6">
                {children}
            </main>
            <Toaster />
        </div>
    );
}
