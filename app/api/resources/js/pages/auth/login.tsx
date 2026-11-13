import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { store } from '@/routes/login';

type Props = {
    status?: string;
};

export default function Login({ status }: Props) {
    return (
        <>
            <Head title="Đăng nhập" />

            {status && (
                <div
                    className="mb-6 rounded-lg border border-success bg-success-bg p-3 text-center text-sm text-success"
                    role="status"
                    data-test="login-status"
                >
                    {status}
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="login">
                                    Tên đăng nhập hoặc email
                                </Label>
                                <Input
                                    id="login"
                                    type="text"
                                    name="login"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="username"
                                    placeholder="Tên đăng nhập hoặc email"
                                />
                                <InputError message={errors.login} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password">Mật khẩu</Label>
                                    <TextLink
                                        href={request()}
                                        className="text-sm"
                                        tabIndex={6}
                                    >
                                        Quên mật khẩu?
                                    </TextLink>
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Mật khẩu"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">
                                    Ghi nhớ đăng nhập
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Đăng nhập
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Chưa có tài khoản?{' '}
                            <TextLink href={register()} tabIndex={5}>
                                Đăng ký
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Đăng nhập vào tài khoản',
    description: 'Nhập tên đăng nhập hoặc email và mật khẩu của bạn',
};
