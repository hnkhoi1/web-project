import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { SiteFooter } from '@/components/site-footer';
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
            <header className="relative z-30 border-b-[3px] border-primary bg-ink text-on-ink">
                <div className="mx-auto flex min-h-[68px] max-w-6xl items-center gap-2 px-5">
                    <Link
                        href="/login"
                        className="flex items-center gap-3 text-on-ink hover:no-underline"
                    >
                        <AppLogo />
                    </Link>
                    <nav
                        className="ml-auto flex items-center gap-[22px]"
                        aria-label="Điều hướng chính"
                    >
                        {links.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="border-b-2 border-transparent px-0.5 py-2 text-[0.93rem] font-medium text-[#d8dce4] transition-colors hover:border-primary hover:text-white"
                            >
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                    <div className="ml-6 flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="text-white hover:bg-white/10 hover:text-white"
                            asChild
                        >
                            <Link href={login()}>Đăng nhập</Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={register()}>Đăng ký</Link>
                        </Button>
                    </div>
                </div>
            </header>
            <main className="grid w-full flex-1 grid-cols-[minmax(1.25rem,1fr)_minmax(0,69.5rem)_minmax(1.25rem,1fr)] content-start py-6 *:col-start-2">
                {children}
            </main>
            <SiteFooter />
            <Toaster />
        </div>
    );
}
