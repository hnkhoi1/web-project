import ChoiceGroup from '@/components/choice-group';
import { useAppearance } from '@/hooks/use-appearance';
import type { Theme } from '@/types';

const themes: { value: Theme; label: string }[] = [
    { value: 'system', label: 'Theo thiết bị' },
    { value: 'light', label: 'Sáng' },
    { value: 'dark', label: 'Tối' },
];

export default function AppearanceTabs() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <ChoiceGroup
            label="Giao diện"
            choices={themes}
            value={appearance}
            onChange={updateAppearance}
        />
    );
}
