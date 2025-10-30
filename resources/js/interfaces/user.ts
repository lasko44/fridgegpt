//User interface
export interface User {
    name: string;
    email: string,
    is_subscribed: boolean;
    username: string;
    uuid: string;
    pm_last_four: number | null;
}
