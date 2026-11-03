import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import AnswerMessage from '@/components/chat/answer-message';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { postJson } from '@/lib/api';
import type { ChatAnswer } from '@/types';

const SUGGESTIONS = [
    'Tôi bị đánh gây thương tích 20% thì được bồi thường gì?',
    'Bị lừa chuyển khoản mất 80 triệu đồng, tôi đòi lại bằng cách nào?',
    'Lái xe say rượu gây tai nạn làm chết 1 người thì bị phạt bao nhiêu năm tù?',
    'Nhân viên nhà nước lấy 300 triệu tiền công quỹ thì phạm tội gì?',
    'Tàng trữ ma túy trong người bị xử lý ra sao?',
];

const WELCOME =
    'Xin chào! Hãy mô tả tình huống của bạn, mình sẽ chỉ ra điều luật áp dụng, mức xử lý và quyền lợi của bạn.';

type Message =
    | { id: number; kind: 'user'; text: string }
    | { id: number; kind: 'bot'; answer: ChatAnswer }
    | { id: number; kind: 'error'; text: string; retry: string };

export default function ChatIndex() {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [waiting, setWaiting] = useState(false);
    const nextId = useRef(1);
    const logRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const el = logRef.current;

        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }, [messages, waiting]);

    useEffect(() => {
        if (!waiting) {
            inputRef.current?.focus();
        }
    }, [waiting]);

    const send = async (raw: string, retry = false) => {
        const text = raw.trim().slice(0, 500);

        if (!text || waiting) {
            return;
        }

        setInput('');
        setMessages((m) => {
            const base = retry ? m.filter((x) => x.kind !== 'error') : m;

            return retry
                ? base
                : [...base, { id: nextId.current++, kind: 'user', text }];
        });
        setWaiting(true);

        try {
            const answer = await postJson<ChatAnswer>('/tu-van/hoi', {
                message: text,
            });
            setMessages((m) => [
                ...m,
                { id: nextId.current++, kind: 'bot', answer },
            ]);
        } catch (e) {
            setMessages((m) => [
                ...m,
                {
                    id: nextId.current++,
                    kind: 'error',
                    text:
                        e instanceof Error
                            ? e.message
                            : 'Không thể kết nối. Vui lòng thử lại.',
                    retry: text,
                },
            ]);
        } finally {
            setWaiting(false);
        }
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        void send(input);
    };

    return (
        <>
            <Head title="Tư vấn" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 px-4 py-6">
                <h1 className="text-2xl font-semibold">Tư vấn pháp luật</h1>

                <div
                    ref={logRef}
                    className="flex h-[55vh] flex-col gap-3 overflow-y-auto rounded-lg border bg-card p-4 text-card-foreground"
                    aria-live="polite"
                    data-test="chat-log"
                >
                    <div className="max-w-[85%] rounded-lg bg-muted px-3 py-2 text-sm">
                        {WELCOME}
                    </div>
                    {messages.map((m) =>
                        m.kind === 'user' ? (
                            <div
                                key={m.id}
                                className="ml-auto max-w-[85%] rounded-lg bg-primary px-3 py-2 text-sm text-primary-foreground"
                            >
                                {m.text}
                            </div>
                        ) : m.kind === 'bot' ? (
                            <div
                                key={m.id}
                                className="max-w-[85%] rounded-lg bg-muted px-3 py-2 text-sm"
                            >
                                <AnswerMessage answer={m.answer} />
                            </div>
                        ) : (
                            <div
                                key={m.id}
                                role="alert"
                                className="max-w-[85%] space-y-2 rounded-lg border border-destructive/50 px-3 py-2 text-sm text-destructive"
                            >
                                <p>{m.text}</p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={waiting}
                                    onClick={() => void send(m.retry, true)}
                                >
                                    Thử lại
                                </Button>
                            </div>
                        ),
                    )}
                    {waiting && (
                        <div className="text-sm text-muted-foreground italic">
                            Đang phân tích tình huống...
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap gap-2">
                    {SUGGESTIONS.map((s) => (
                        <button
                            key={s}
                            type="button"
                            disabled={waiting}
                            onClick={() => void send(s)}
                            className="rounded-full border px-3 py-1 text-left text-xs hover:bg-muted disabled:opacity-50"
                        >
                            {s}
                        </button>
                    ))}
                </div>

                <form onSubmit={submit} className="flex gap-2">
                    <Input
                        ref={inputRef}
                        value={input}
                        maxLength={500}
                        disabled={waiting}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder="Mô tả tình huống của bạn…"
                        aria-label="Câu hỏi"
                    />
                    <Button type="submit" disabled={waiting || !input.trim()}>
                        Gửi
                    </Button>
                </form>
            </div>
        </>
    );
}
