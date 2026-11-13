import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import AnswerMessage from '@/components/chat/answer-message';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { index } from '@/routes/history';
import type { ChatAnswer, ChatRating } from '@/types';

type Props = {
    item: {
        id: number;
        cau_hoi: string;
        danh_gia: ChatRating;
        created_at: string | null;
    };
    answer: ChatAnswer;
};

export default function ChatHistoryShow({ item, answer }: Props) {
    return (
        <>
            <Head title="Câu hỏi đã hỏi" />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-2 print:hidden">
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" /> Lịch sử câu hỏi
                    </Link>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => window.print()}
                    >
                        <Printer /> In
                    </Button>
                </div>

                <div className="space-y-4 rounded-lg border bg-card p-5 text-card-foreground">
                    <p className="text-xs text-muted-foreground">
                        {formatDateTime(item.created_at)}
                    </p>
                    <div className="ml-auto max-w-[85%] rounded-lg bg-primary px-3 py-2 text-sm text-primary-foreground">
                        {item.cau_hoi}
                    </div>
                    <div className="max-w-[85%] rounded-lg bg-muted px-3 py-2 text-sm">
                        <AnswerMessage
                            answer={{ ...answer, history_id: item.id }}
                            rating={item.danh_gia}
                        />
                    </div>
                </div>

                <p className="text-xs text-muted-foreground">
                    Câu trả lời được tính lại từ dữ liệu luật hiện tại, nên có
                    thể khác chút nếu nội dung luật đã được cập nhật.
                </p>
            </div>
        </>
    );
}
