import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
import { index as notesIndex } from '@/routes/notes';

type NavLink = { title: string; href: string };

// Pages that arrive in later tasks are plain hrefs, not Wayfinder imports.
const navLinks: NavLink[] = [
    { title: 'Tra cứu', href: '/tra-cuu' },
    { title: 'Tư vấn', href: '/tu-van' },
    { title: 'Ghi chú', href: notesIndex().url },
    { title: 'Giới thiệu', href: '/gioi-thieu' },
    { title: 'Liên hệ', href: '/lien-he' },
];

export function AppHeader() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const { isCurrentUrl } = useCurrentUrl();
    const user = auth.user;

    return (
        <header className="relative z-30 border-b-[3px] border-primary bg-ink text-on-ink">
            <div className="mx-auto flex min-h-[68px] max-w-6xl items-center gap-2 px-5">
                <div className="md:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Mở menu"
                                className="border border-[#3c4a63] text-white hover:bg-white/10 hover:text-white"
                            >
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-64">
                            <SheetHeader>
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <nav className="flex flex-col gap-1 px-4">
                                {navLinks.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={cn(
                                            'rounded-md px-3 py-2 text-sm font-medium hover:bg-accent',
                                            isCurrentUrl(item.href) &&
                                                'bg-accent',
                                        )}
                                    >
                                        {item.title}
                                    </Link>
                                ))}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link
                    href={notesIndex()}
                    className="flex items-center gap-3 text-on-ink hover:no-underline"
                >
                    <AppLogo />
                </Link>

                <nav
                    className="ml-auto hidden items-center gap-[22px] md:flex"
                    aria-label="Điều hướng chính"
                >
                    {navLinks.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'border-b-2 border-transparent px-0.5 py-2 text-[0.93rem] font-medium text-[#d8dce4] transition-colors hover:border-primary hover:text-white',
                                isCurrentUrl(item.href) &&
                                    'border-primary text-white',
                            )}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>

                <div className="ml-auto flex items-center gap-2 md:ml-6">
                    {user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-11 rounded-full border border-[#3c4a63] p-1 text-white hover:border-primary hover:bg-transparent hover:text-white"
                                    aria-label="Menu người dùng"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={user.avatar ?? undefined}
                                            alt={user.name}
                                        />
                                        <AvatarFallback className="bg-muted text-foreground">
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        <>
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
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
