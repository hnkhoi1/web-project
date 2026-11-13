import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const base =
    'inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-sm font-medium';

/** Previous / next links for a Laravel paginator, plus a "page x of y" summary. */
export function Pagination({ page }: { page: Paginated<unknown> }) {
    if (page.total === 0) {
        return null;
    }

    return (
        <nav
            className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground"
            aria-label="Phân trang"
        >
            <p>
                {page.from}–{page.to} / {page.total}
            </p>
            {page.last_page > 1 && (
                <div className="flex items-center gap-2">
                    {page.prev_page_url ? (
                        <Link
                            href={page.prev_page_url}
                            className={cn(base, 'hover:bg-accent')}
                            preserveScroll
                        >
                            <ChevronLeft className="size-4" /> Trước
                        </Link>
                    ) : (
                        <span className={cn(base, 'opacity-40')}>
                            <ChevronLeft className="size-4" /> Trước
                        </span>
                    )}
                    <span>
                        Trang {page.current_page} / {page.last_page}
                    </span>
                    {page.next_page_url ? (
                        <Link
                            href={page.next_page_url}
                            className={cn(base, 'hover:bg-accent')}
                            preserveScroll
                        >
                            Sau <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <span className={cn(base, 'opacity-40')}>
                            Sau <ChevronRight className="size-4" />
                        </span>
                    )}
                </div>
            )}
        </nav>
    );
}
