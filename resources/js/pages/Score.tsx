import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { scoresApi, type Score as ScoreType, type Tip, type CategoryScore } from '@/api/scores';
import { socialApi, type SocialAccount } from '@/api/social';
import { useAuth } from '@/context/AuthContext';

const categoryLabels: Record<string, string> = {
    profile_completeness: 'Profile Completeness',
    engagement_rate: 'Engagement Rate',
    post_consistency: 'Post Consistency',
    content_performance: 'Content Performance',
    growth_trend: 'Growth Trend',
    hashtag_seo: 'Hashtag & SEO',
};

const categoryColors: Record<string, string> = {
    profile_completeness: 'bg-blue-500',
    engagement_rate: 'bg-green-500',
    post_consistency: 'bg-yellow-500',
    content_performance: 'bg-purple-500',
    growth_trend: 'bg-pink-500',
    hashtag_seo: 'bg-cyan-500',
};

function gradeColor(grade: string): string {
    if (grade.startsWith('A')) return 'text-green-600 bg-green-50';
    if (grade === 'B') return 'text-blue-600 bg-blue-50';
    if (grade === 'C') return 'text-yellow-600 bg-yellow-50';
    if (grade === 'D') return 'text-orange-600 bg-orange-50';
    return 'text-red-600 bg-red-50';
}

function priorityBadge(priority: string) {
    switch (priority) {
        case 'high':
            return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">High</span>;
        case 'medium':
            return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Medium</span>;
        default:
            return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">Low</span>;
    }
}

export default function Score() {
    const { platform } = useParams<{ platform: string }>();
    const { user } = useAuth();
    const [score, setScore] = useState<ScoreType | null>(null);
    const [categories, setCategories] = useState<CategoryScore[]>([]);
    const [account, setAccount] = useState<SocialAccount | null>(null);
    const [loading, setLoading] = useState(true);
    const [calculating, setCalculating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        loadData();
    }, [platform]);

    const loadData = async () => {
        setLoading(true);
        try {
            const accountsRes = await socialApi.getAccounts();
            const acc = accountsRes.data.find(a => a.platform === platform);
            if (!acc) {
                setError('Platform not connected');
                setLoading(false);
                return;
            }
            setAccount(acc);

            const scoreRes = await scoresApi.getScore(acc.id);
            if (scoreRes.data.score) {
                setScore(scoreRes.data.score);
            }
            if (scoreRes.data.categories) {
                setCategories(scoreRes.data.categories);
            }
        } catch {
            setError('Failed to load score data');
        } finally {
            setLoading(false);
        }
    };

    const calculateScore = async () => {
        if (!account) return;
        setCalculating(true);
        try {
            const res = await scoresApi.calculate(account.id);
            setScore(res.data.score);
        } catch {
            // Rate limited or error
        } finally {
            setCalculating(false);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    if (error || !account) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">{error || 'Platform not found'}</p>
                <Link to="/connect" className="mt-4 inline-block text-indigo-600 hover:text-indigo-500">
                    Connect a platform
                </Link>
            </div>
        );
    }

    return (
        <div>
            <div className="mb-8">
                <Link to="/dashboard" className="text-sm text-indigo-600 hover:text-indigo-500">
                    &larr; Back to dashboard
                </Link>
                <div className="mt-2 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">
                        Score — @{account.username}
                    </h1>
                    <div className="flex space-x-3">
                        <button
                            onClick={calculateScore}
                            disabled={calculating}
                            className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {calculating ? 'Calculating...' : score ? 'Recalculate' : 'Calculate Score'}
                        </button>
                        {score && (
                            <Link
                                to={`/share/${account.id}`}
                                className="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100"
                            >
                                Share Score
                            </Link>
                        )}
                    </div>
                </div>
            </div>

            {!score ? (
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p className="text-gray-500">No score calculated yet. Click "Calculate Score" to get started.</p>
                </div>
            ) : (
                <div className="space-y-8">
                    {/* Overall score card */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-6">
                                <div className="relative">
                                    <svg width="120" height="120" className="-rotate-90">
                                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" strokeWidth="8" />
                                        <circle
                                            cx="60" cy="60" r="52" fill="none"
                                            className={score.overall_score >= 80 ? 'stroke-green-500' : score.overall_score >= 60 ? 'stroke-blue-500' : score.overall_score >= 40 ? 'stroke-yellow-500' : 'stroke-red-500'}
                                            strokeWidth="8" strokeLinecap="round"
                                            strokeDasharray={2 * Math.PI * 52}
                                            strokeDashoffset={2 * Math.PI * 52 - (score.overall_score / 100) * 2 * Math.PI * 52}
                                        />
                                    </svg>
                                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                                        <span className="text-3xl font-bold text-gray-900">{score.overall_score}</span>
                                        <span className="text-xs text-gray-500">/ 100</span>
                                    </div>
                                </div>
                                <div>
                                    <span className={`inline-block px-4 py-2 text-2xl font-bold rounded-lg ${gradeColor(score.grade)}`}>
                                        {score.grade}
                                    </span>
                                    <p className="mt-2 text-sm text-gray-500">
                                        Calculated {new Date(score.calculated_at).toLocaleString()}
                                    </p>
                                </div>
                            </div>
                            <div className="flex space-x-3">
                                <Link
                                    to={`/stats/${platform}`}
                                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100"
                                >
                                    View Statistics
                                </Link>
                                <Link
                                    to="/history"
                                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100"
                                >
                                    Score History
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Category breakdown */}
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Score Breakdown</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {Object.entries(score.category_scores).map(([key, value]) => (
                                <div key={key} className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm font-medium text-gray-700">
                                            {categoryLabels[key] || key}
                                        </span>
                                        <span className="text-lg font-bold text-gray-900">{value}</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                                        <div
                                            className={`h-2.5 rounded-full ${categoryColors[key] || 'bg-indigo-500'}`}
                                            style={{ width: `${value}%` }}
                                        ></div>
                                    </div>
                                    {categories.find(c => c.key === key) && (
                                        <p className="mt-1 text-xs text-gray-500">
                                            Weight: {(categories.find(c => c.key === key)!.weight * 100).toFixed(0)}%
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Tips section */}
                    {score.tips && score.tips.length > 0 && (
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                Tips & Recommendations
                                {!user?.is_premium && (
                                    <span className="ml-2 text-xs font-normal text-gray-500">
                                        (Showing top 3 — <Link to="/pricing" className="text-indigo-600 hover:underline">Upgrade</Link> for all tips)
                                    </span>
                                )}
                            </h2>
                            <div className="space-y-3">
                                {(user?.is_premium ? score.tips : score.tips.slice(0, 3)).map((tip: Tip, index: number) => (
                                    <div key={index} className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-start space-x-3">
                                        <div className="flex-shrink-0 mt-0.5">
                                            {priorityBadge(tip.priority)}
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-500 capitalize">{categoryLabels[tip.category] || tip.category}</p>
                                            <p className="mt-1 text-sm text-gray-900">{tip.tip}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
