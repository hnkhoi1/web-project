export type User = {
    id: number;
    username: string | null;
    name: string;
    email: string;
    avatar: string | null;
    is_demo: boolean;
    created_at: string;
    updated_at: string;
};

export type Auth = {
    user: User;
};
