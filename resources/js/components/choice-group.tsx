import { cn } from '@/lib/utils';

export type Choice<T extends string> = { value: T; label: string };

export default function ChoiceGroup<T extends string>({
    label,
    choices,
    value,
    onChange,
}: {
    label: string;
    choices: readonly Choice<T>[];
    value: T;
    onChange: (value: T) => void;
}) {
    return (
        <div role="radiogroup" aria-label={label} className="space-y-2">
            <p className="text-sm font-medium">{label}</p>
            <div className="flex flex-wrap gap-2">
                {choices.map((choice) => (
                    <button
                        key={choice.value}
                        type="button"
                        role="radio"
                        aria-checked={value === choice.value}
                        onClick={() => onChange(choice.value)}
                        className={cn(
                            'rounded-full border px-4 py-1.5 text-sm transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            value === choice.value &&
                                'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
                        )}
                    >
                        {choice.label}
                    </button>
                ))}
            </div>
        </div>
    );
}
