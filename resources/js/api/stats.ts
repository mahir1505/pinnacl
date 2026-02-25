import api from './client';

export interface GrowthMetric {
    current: number;
    change: number;
    percent: number;
}

export interface StatsOverview {
    account: {
        id: number;
        platform: string;
        username: string;
    };
    current: {
        followers: number;
        following: number;
        post_count: number;
    };
    growth: {
        followers: GrowthMetric;
        engagement_rate: GrowthMetric;
        avg_likes: GrowthMetric;
        avg_views: GrowthMetric;
    };
    last_synced_at: string | null;
}

export interface SnapshotData {
    date: string;
    followers: number;
    following: number;
    engagement_rate: number;
    avg_likes: number;
    avg_views: number;
    avg_comments: number;
    total_posts: number;
    posting_frequency: number;
}

export interface PostData {
    id: number;
    platform_post_id: string;
    post_type: string;
    caption: string;
    thumbnail_url: string;
    post_url: string;
    likes: number;
    views: number;
    comments: number;
    shares: number;
    saves: number;
    posted_at: string;
}

export interface ContentBreakdown {
    type: string;
    count: number;
    avg_likes: number;
    avg_views: number;
    avg_comments: number;
}

export const statsApi = {
    getOverview: (accountId: number) =>
        api.get<StatsOverview>(`/stats/${accountId}`),

    getSnapshots: (accountId: number, period: 'day' | 'week' | 'month' = 'day') =>
        api.get<{ period: string; data: SnapshotData[] }>(`/stats/${accountId}/snapshots`, { params: { period } }),

    getPosts: (accountId: number, params?: { sort?: string; dir?: string; type?: string; page?: number }) =>
        api.get<{ data: PostData[]; current_page: number; last_page: number }>(`/stats/${accountId}/posts`, { params }),

    getPostDetail: (accountId: number, postId: number) =>
        api.get<PostData>(`/stats/${accountId}/posts/${postId}`),

    getContentBreakdown: (accountId: number) =>
        api.get<ContentBreakdown[]>(`/stats/${accountId}/content-breakdown`),
};
