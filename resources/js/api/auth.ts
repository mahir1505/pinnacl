import api from './client';

export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    is_premium: boolean;
    premium_expires_at: string | null;
    created_at: string;
}

export interface AuthResponse {
    user: User;
    token: string;
}

export const authApi = {
    register: (data: { name: string; email: string; password: string; password_confirmation: string }) =>
        api.post<AuthResponse>('/auth/register', data),

    login: (data: { email: string; password: string }) =>
        api.post<AuthResponse>('/auth/login', data),

    getUser: () =>
        api.get<User>('/auth/user'),

    logout: () =>
        api.post('/auth/logout'),
};
