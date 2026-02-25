import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import api from '@/api/client';

interface ShareData {
    username: string;
    platform: string;
    overall_score: number;
    grade: string;
    category_scores: Record<string, number>;
    calculated_at: string;
}

const categoryLabels: Record<string, string> = {
    profile_completeness: 'Profile',
    engagement_rate: 'Engagement',
    post_consistency: 'Consistency',
    content_performance: 'Performance',
    growth_trend: 'Growth',
    hashtag_seo: 'SEO',
};

function gradeGradient(grade: string): string {
    if (grade.startsWith('A')) return 'from-green-500 to-emerald-600';
    if (grade === 'B') return 'from-blue-500 to-indigo-600';
    if (grade === 'C') return 'from-yellow-500 to-amber-600';
    if (grade === 'D') return 'from-orange-500 to-red-500';
    return 'from-red-500 to-red-700';
}

const platformLabels: Record<string, string> = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    x: 'X (Twitter)',
    linkedin: 'LinkedIn',
};

export default function Share() {
    const { id } = useParams<{ id: string }>();
    const [data, setData] = useState<ShareData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    useEffect(() => {
        loadShareData();
    }, [id]);

    const loadShareData = async () => {
        try {
            const res = await api.get(`/share/${id}`);
            setData(res.data);
        } catch {
            setError(true);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    if (error || !data) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="text-center">
                    <h1 className="text-2xl font-bold text-gray-900">Score Not Found</h1>
                    <p className="mt-2 text-gray-500">This score card is not available.</p>
                    <a href="/" className="mt-4 inline-block text-indigo-600 hover:underline">Go to Pinnacl</a>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
            <div className="w-full max-w-md">
                {/* Score card */}
                <div className={`bg-gradient-to-br ${gradeGradient(data.grade)} rounded-2xl shadow-xl p-8 text-white`}>
                    <div className="text-center">
                        <p className="text-sm font-medium text-white/80 uppercase tracking-wider">
                            Pinnacl Score
                        </p>
                        <div className="mt-4">
                            <div className="relative inline-block">
                                <svg width="160" height="160" className="-rotate-90">
                                    <circle cx="80" cy="80" r="68" fill="none" stroke="rgba(255,255,255,0.2)" strokeWidth="8" />
                                    <circle
                                        cx="80" cy="80" r="68" fill="none"
                                        stroke="white" strokeWidth="8" strokeLinecap="round"
                                        strokeDasharray={2 * Math.PI * 68}
                                        strokeDashoffset={2 * Math.PI * 68 - (data.overall_score / 100) * 2 * Math.PI * 68}
                                    />
                                </svg>
                                <div className="absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-5xl font-bold">{data.overall_score}</span>
                                    <span className="text-sm text-white/80">/ 100</span>
                                </div>
                            </div>
                        </div>
                        <p className="mt-3 text-4xl font-bold">{data.grade}</p>
                    </div>

                    <div className="mt-6 text-center">
                        <p className="text-lg font-semibold">@{data.username}</p>
                        <p className="text-sm text-white/70">{platformLabels[data.platform] || data.platform}</p>
                    </div>

                    {/* Category bars */}
                    <div className="mt-6 space-y-2">
                        {Object.entries(data.category_scores).map(([key, value]) => (
                            <div key={key} className="flex items-center space-x-3">
                                <span className="text-xs text-white/80 w-24 text-right">
                                    {categoryLabels[key] || key}
                                </span>
                                <div className="flex-1 bg-white/20 rounded-full h-2">
                                    <div
                                        className="bg-white rounded-full h-2"
                                        style={{ width: `${value}%` }}
                                    ></div>
                                </div>
                                <span className="text-xs font-bold w-8">{value}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-6 pt-4 border-t border-white/20 text-center">
                        <p className="text-xs text-white/60">
                            {new Date(data.calculated_at).toLocaleDateString()}
                        </p>
                    </div>
                </div>

                {/* CTA */}
                <div className="mt-6 text-center">
                    <p className="text-sm text-gray-500 mb-3">Check your own social media score</p>
                    <a
                        href="/register"
                        className="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                    >
                        Get Your Pinnacl Score — Free
                    </a>
                    <p className="mt-4 text-xs text-gray-400">
                        Powered by <span className="font-semibold text-indigo-600">Pinnacl</span>
                    </p>
                </div>
            </div>
        </div>
    );
}
