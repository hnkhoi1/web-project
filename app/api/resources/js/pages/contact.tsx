import { Form, Head } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/ContactController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Contact() {
    return (
        <>
            <Head title="Liên hệ" />

            <div className="mx-auto max-w-xl space-y-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">
                        Liên hệ / Gửi câu hỏi
                    </h1>
                    <p className="text-muted-foreground">
                        Nếu chatbot chưa trả lời thỏa đáng, hãy để lại câu hỏi
                        để chuyên viên xem lại.
                    </p>
                </div>

                <Form
                    {...store.form()}
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="space-y-5"
                >
                    {({ processing, errors, wasSuccessful }) => (
                        <>
                            {wasSuccessful && (
                                <p
                                    role="status"
                                    className="rounded-lg border bg-card p-3 text-sm"
                                >
                                    Đã gửi câu hỏi. Chúng tôi sẽ xem xét sớm
                                    nhất có thể.
                                </p>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="ho_ten">Họ tên</Label>
                                <Input
                                    id="ho_ten"
                                    name="ho_ten"
                                    autoComplete="name"
                                    maxLength={60}
                                    required
                                />
                                <InputError message={errors.ho_ten} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="sdt">
                                    Số điện thoại (không bắt buộc)
                                </Label>
                                <Input
                                    id="sdt"
                                    name="sdt"
                                    type="tel"
                                    autoComplete="tel"
                                />
                                <InputError message={errors.sdt} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    maxLength={254}
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="noi_dung">
                                    Nội dung câu hỏi
                                </Label>
                                <textarea
                                    id="noi_dung"
                                    name="noi_dung"
                                    rows={6}
                                    maxLength={2000}
                                    required
                                    className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError message={errors.noi_dung} />
                            </div>

                            <Button disabled={processing}>Gửi câu hỏi</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
