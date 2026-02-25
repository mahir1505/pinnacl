import api from './client';

export interface SocialAccount {
    id: number;
    platform: string;
    platform_name: string;
    username: string;
    profile_data: Record<string, unknown> | null;
    latest_score: {
        overall_score: number;
        grade: string;
        calculated_at: string;
    } | null;
    last_synced_at: string | null;
}

export interface Platform {
    id: string;
    name: string;
    connected: boolean;
}

export const socialApi = {
    getPlatforms: () =>
        api.get<Platform[]>('/social/platforms'),

    getAccounts: () =>
        api.get<SocialAccount[]>('/social/accounts'),

    connect: (platform: string) =>
        api.get<{ redirect_url: string }>(`/social/connect/${platform}`),

    link: (data: {
        platform: string;
        oauth_token: string;
        oauth_refresh_token?: string;
        oauth_id: string;
        oauth_name: string;
    }) => api.post('/social/link', data),

    disconnect: (id: number) =>
        api.delete(`/social/accounts/${id}`),

    sync: (id: number) =>
        api.post(`/social/accounts/${id}/sync`),
};
