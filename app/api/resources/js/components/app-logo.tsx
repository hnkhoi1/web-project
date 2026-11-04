import { usePage } from '@inertiajs/react';
import { Scale } from 'lucide-react';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <Scale className="size-4" />
            </div>
            <span className="hidden truncate text-sm font-semibold sm:inline">
                {name}
            </span>
        </>
    );
}
