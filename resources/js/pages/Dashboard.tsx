import { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { Link } from 'react-router-dom';
import { socialApi, type SocialAccount } from '@/api/social';
import { scoresApi } from '@/api/scores';

const platformIcons: Record<string, string> = {
    instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
    tiktok: 'bg-black',
    youtube: 'bg-red-600',
    x: 'bg-black',
    linkedin: 'bg-blue-700',
};

const platformLabels: Record<string, string> = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    x: 'X (Twitter)',
    linkedin: 'LinkedIn',
};

function gradeColor(grade: string): string {
    if (grade.startsWith('A')) return 'text-green-600';
    if (grade === 'B') return 'text-blue-600';
    if (grade === 'C') return 'text-yellow-600';
    if (grade === 'D') return 'text-orange-600';
    return 'text-red-600';
}

function scoreRingColor(score: number): string {
    if (score >= 80) return 'stroke-green-500';
    if (score >= 60) return 'stroke-blue-500';
    if (score >= 40) return 'stroke-yellow-500';
    return 'stroke-red-500';
}

function ScoreRing({ score, size = 64 }: { score: number; size?: number }) {
    const radius = (size - 8) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (score / 100) * circumference;

    return (
        <svg width={size} height={size} className="-rotate-90">
            <circle
                cx={size / 2}
                cy={size / 2}
                r={radius}
                fill="none"
                stroke="#e5e7eb"
                strokeWidth="4"
            />
            <circle
                cx={size / 2}
                cy={size / 2}
                r={radius}
                fill="none"
                className={scoreRingColor(score)}
                strokeWidth="4"
                strokeLinecap="round"
                strokeDasharray={circumference}
                strokeDashoffset={offset}
            />
        </svg>
    );
}

export default function Dashboard() {
    const { user } = useAuth();
    const [accounts, setAccounts] = useState<SocialAccount[]>([]);
    const [loading, setLoading] = useState(true);
    const [calculating, setCalculating] = useState<number | null>(null);

    useEffect(() => {
        loadAccounts();
    }, []);

    const loadAccounts = async () => {
        try {
            const res = await socialApi.getAccounts();
            setAccounts(res.data);
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    const calculateScore = async (accountId: number) => {
        setCalculating(accountId);
        try {
            await scoresApi.calculate(accountId);
            await loadAccounts();
        } catch {
            // Handle rate limit or error
        } finally {
            setCalculating(null);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    return (
        <div>
            <div className="mb-8 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        Welcome, {user?.name}
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Your social media profile scores overview
                    </p>
                </div>
                <Link
                    to="/connect"
                    className="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                >
                    + Connect Platform
                </Link>
            </div>

            {accounts.length === 0 ? (
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <div className="mx-auto h-12 w-12 text-gray-400">
                        <svg fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                        </svg>
                    </div>
                    <h3 className="mt-4 text-lg font-medium text-gray-900">No platforms connected</h3>
                    <p className="mt-2 text-sm text-gray-500">
                        Connect your social media accounts to get your Pinnacl score.
                    </p>
                    <div className="mt-6">
                        <Link
                            to="/connect"
                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            Connect a platform
                        </Link>
                    </div>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {accounts.map((account) => (
                        <div key={account.id} className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            {/* Platform header */}
                            <div className={`${platformIcons[account.platform] || 'bg-gray-600'} px-4 py-3 flex items-center space-x-3`}>
                                <span className="text-white font-semibold text-sm">
                                    {platformLabels[account.platform] || account.platform}
                                </span>
                                <span className="text-white/70 text-sm">@{account.username}</span>
                            </div>

                            <div className="p-5">
                                {account.latest_score ? (
                                    <div className="flex items-center space-x-4">
                                        <div className="relative flex-shrink-0">
                                            <ScoreRing score={account.latest_score.overall_score} />
                                            <div className="absolute inset-0 flex items-center justify-center">
                                                <span className="text-lg font-bold text-gray-900">
                                                    {account.latest_score.overall_score}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <p className={`text-2xl font-bold ${gradeColor(account.latest_score.grade)}`}>
                                                {account.latest_score.grade}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {new Date(account.latest_score.calculated_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-2">
                                        <p className="text-sm text-gray-500">No score yet</p>
                                    </div>
                                )}

                                {/* Action buttons */}
                                <div className="mt-4 flex space-x-2">
                                    <button
                                        onClick={() => calculateScore(account.id)}
                                        disabled={calculating === account.id}
                                        className="flex-1 px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 disabled:opacity-50"
                                    >
                                        {calculating === account.id ? 'Calculating...' : account.latest_score ? 'Recalculate' : 'Calculate Score'}
                                    </button>
                                    {account.latest_score && (
                                        <Link
                                            to={`/score/${account.platform}`}
                                            className="flex-1 px-3 py-2 text-sm font-medium text-center text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100"
                                        >
                                            Details
                                        </Link>
                                    )}
                                    <Link
                                        to={`/stats/${account.platform}`}
                                        className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100"
                                    >
                                        Stats
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Quick tips section if any account has a score */}
            {accounts.some(a => a.latest_score) && (
                <div className="mt-8">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Link to="/history" className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:border-indigo-300 transition-colors">
                            <p className="font-medium text-gray-900">Score History</p>
                            <p className="mt-1 text-sm text-gray-500">Track your progress over time</p>
                        </Link>
                        <Link to={`/score/${accounts[0].platform}`} className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:border-indigo-300 transition-colors">
                            <p className="font-medium text-gray-900">View Breakdown</p>
                            <p className="mt-1 text-sm text-gray-500">See detailed scores & tips</p>
                        </Link>
                        <Link to={`/stats/${accounts[0].platform}`} className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:border-indigo-300 transition-colors">
                            <p className="font-medium text-gray-900">Statistics</p>
                            <p className="mt-1 text-sm text-gray-500">Analytics & growth data</p>
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
