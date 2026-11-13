import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request, update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="Đặt lại mật khẩu" />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token })}
                resetOnSuccess={['password', 'password_confirmation']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    defaultValue={email}
                                    required
                                    readOnly={email !== ''}
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                                {errors.email && (
                                    <TextLink href={request()}>
                                        Yêu cầu liên kết mới
                                    </TextLink>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Mật khẩu mới</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoFocus
                                    autoComplete="new-password"
                                    placeholder="Mật khẩu mới"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Xác nhận mật khẩu
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    placeholder="Nhập lại mật khẩu mới"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                disabled={processing}
                                data-test="reset-password-button"
                            >
                                {processing && <Spinner />}
                                Đặt lại mật khẩu
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Đặt lại mật khẩu',
    description: 'Chọn một mật khẩu mới cho tài khoản của bạn',
};
