import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import { DemoNotice } from '@/components/demo-notice';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';

export default function Profile() {
    const { auth, errors } = usePage().props;
    const getInitials = useInitials();
    const user = auth.user;

    if (!user) {
        return null;
    }

    return (
        <>
            <Head title="Hồ sơ" />

            <h1 className="sr-only">Hồ sơ</h1>

            <DemoNotice error={errors.demo} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Ảnh đại diện"
                    description="JPG, PNG hoặc WebP, tối đa 2 MB, tối thiểu 64×64 px"
                />

                <div className="flex items-center gap-4">
                    <Avatar className="size-20 overflow-hidden rounded-full">
                        <AvatarImage
                            src={user.avatar ?? undefined}
                            alt={user.name}
                        />
                        <AvatarFallback className="bg-muted text-xl text-foreground">
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>

                    <Form
                        {...ProfileController.updateAvatar.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="flex flex-col gap-2"
                    >
                        {({ processing, errors: formErrors }) => (
                            <>
                                <div className="flex items-center gap-2">
                                    <Input
                                        id="avatar"
                                        type="file"
                                        name="avatar"
                                        accept="image/jpeg,image/png,image/webp"
                                        disabled={user.isDemo}
                                        aria-label="Chọn ảnh đại diện"
                                        required
                                    />
                                    <Button
                                        disabled={processing || user.isDemo}
                                        data-test="upload-avatar-button"
                                    >
                                        Tải lên
                                    </Button>
                                </div>
                                <InputError message={formErrors.avatar} />
                            </>
                        )}
                    </Form>
                </div>

                {user.avatar && (
                    <Form
                        {...ProfileController.destroyAvatar.form()}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={processing || user.isDemo}
                                data-test="remove-avatar-button"
                            >
                                Xóa ảnh đại diện
                            </Button>
                        )}
                    </Form>
                )}
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Thông tin cá nhân"
                    description="Cập nhật tên hiển thị của bạn"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors: formErrors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Tên hiển thị</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={user.name}
                                    name="name"
                                    required
                                    disabled={user.isDemo}
                                    autoComplete="name"
                                    placeholder="Tên hiển thị"
                                />

                                <InputError
                                    className="mt-2"
                                    message={formErrors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>

                                <Input
                                    id="email"
                                    className="mt-1 block w-full"
                                    value={user.email}
                                    readOnly
                                    disabled
                                />
                                <p className="text-xs text-muted-foreground">
                                    Email không thể thay đổi sau khi đăng ký.
                                </p>
                            </div>

                            <Button
                                disabled={processing || user.isDemo}
                                data-test="update-profile-button"
                            >
                                Lưu
                            </Button>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}
