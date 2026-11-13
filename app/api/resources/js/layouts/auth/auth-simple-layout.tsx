import { Link } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import type { CSSProperties } from 'react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
    animated = false,
}: AuthLayoutProps) {
    // Staggered fade-up for the login page; `--d` is each element's delay.
    const enter = (delay: string) =>
        animated
            ? {
                  className: 'anim-fade-up',
                  style: { '--d': delay } as CSSProperties,
              }
            : {};

    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div
                                className={`mb-1 flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground ${animated ? 'anim-logo' : ''}`}
                            >
                                <Scale
                                    className={`size-5 ${animated ? 'anim-sway' : ''}`}
                                />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div
                            className="space-y-2 text-center"
                            {...enter('0.12s')}
                        >
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    <div {...enter('0.24s')}>{children}</div>
                </div>
            </div>
        </div>
    );
}
