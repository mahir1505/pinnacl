import api from './client';

export interface CategoryScore {
    key: string;
    label: string;
    weight: number;
}

export interface Tip {
    category: string;
    tip: string;
    priority: 'high' | 'medium' | 'low';
}

export interface Score {
    id: number;
    overall_score: number;
    grade: string;
    category_scores: Record<string, number>;
    tips: Tip[];
    calculated_at: string;
}

export interface ScoreResponse {
    score: Score | null;
    categories?: CategoryScore[];
    message?: string;
}

export interface ScoreHistoryEntry {
    id: number;
    overall_score: number;
    grade: string;
    category_scores: Record<string, number>;
    calculated_at: string;
}

export const scoresApi = {
    calculate: (accountId: number) =>
        api.post<{ message: string; score: Score }>(`/scores/calculate/${accountId}`),

    getScore: (accountId: number) =>
        api.get<ScoreResponse>(`/scores/${accountId}`),

    getHistory: (accountId: number) =>
        api.get<ScoreHistoryEntry[]>(`/scores/${accountId}/history`),
};
