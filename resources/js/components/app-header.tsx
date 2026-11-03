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
        <header className="border-b bg-background">
            <div className="mx-auto flex h-14 max-w-5xl items-center gap-2 px-4">
                <div className="md:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Mở menu"
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

                <Link href={notesIndex()} className="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <nav
                    className="ml-4 hidden items-center gap-1 md:flex"
                    aria-label="Điều hướng chính"
                >
                    {navLinks.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground',
                                isCurrentUrl(item.href) &&
                                    'bg-accent text-foreground',
                            )}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>

                <div className="ml-auto flex items-center gap-2">
                    {user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-10 rounded-full p-1"
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
                            <Button variant="ghost" size="sm" asChild>
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
