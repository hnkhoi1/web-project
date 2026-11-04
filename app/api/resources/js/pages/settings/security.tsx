import { Form, Head, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { DemoNotice } from '@/components/demo-notice';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Props = {
    passwordRules: string;
};

export default function Security(props: Props) {
    const { auth, errors } = usePage().props;
    const isDemo = auth.user?.isDemo ?? false;
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Đổi mật khẩu" />

            <h1 className="sr-only">Đổi mật khẩu</h1>

            <DemoNotice error={errors.demo} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Đổi mật khẩu"
                    description="Dùng mật khẩu dài, khó đoán (ít nhất 8 ký tự, gồm chữ và số) để giữ an toàn cho tài khoản"
                />

                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onError={(errors) => {
                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">
                                    Mật khẩu hiện tại
                                </Label>

                                <PasswordInput
                                    disabled={isDemo}
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    placeholder="Mật khẩu hiện tại"
                                />

                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Mật khẩu mới</Label>

                                <PasswordInput
                                    disabled={isDemo}
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    placeholder="Mật khẩu mới"
                                    passwordrules={props.passwordRules}
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Nhập lại mật khẩu mới
                                </Label>

                                <PasswordInput
                                    disabled={isDemo}
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    placeholder="Nhập lại mật khẩu mới"
                                    passwordrules={props.passwordRules}
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing || isDemo}
                                    data-test="update-password-button"
                                >
                                    Lưu mật khẩu
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
