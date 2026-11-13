import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

type Props = {
    status?: string;
};

export default function ForgotPassword({ status }: Props) {
    return (
        <>
            <Head title="Quên mật khẩu" />

            {status && (
                <div
                    className="mb-6 rounded-lg border border-success bg-success-bg p-3 text-center text-sm text-success"
                    role="status"
                    data-test="reset-link-status"
                >
                    {status}
                </div>
            )}

            <Form {...email.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                autoComplete="email"
                                placeholder="email@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="send-reset-link-button"
                        >
                            {processing && <Spinner />}
                            Gửi liên kết đặt lại
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            Nhớ ra mật khẩu rồi?{' '}
                            <TextLink href={login()}>
                                Quay lại đăng nhập
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Quên mật khẩu',
    description:
        'Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để đặt mật khẩu mới',
};
