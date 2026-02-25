import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import { scoresApi, type ScoreHistoryEntry } from '@/api/scores';
import { socialApi, type SocialAccount } from '@/api/social';
import { useAuth } from '@/context/AuthContext';

export default function History() {
    const { user } = useAuth();
    const [accounts, setAccounts] = useState<SocialAccount[]>([]);
    const [selectedAccount, setSelectedAccount] = useState<number | null>(null);
    const [history, setHistory] = useState<ScoreHistoryEntry[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadAccounts();
    }, []);

    useEffect(() => {
        if (selectedAccount) {
            loadHistory();
        }
    }, [selectedAccount]);

    const loadAccounts = async () => {
        try {
            const res = await socialApi.getAccounts();
            setAccounts(res.data);
            if (res.data.length > 0) {
                setSelectedAccount(res.data[0].id);
            }
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    const loadHistory = async () => {
        if (!selectedAccount) return;
        try {
            const res = await scoresApi.getHistory(selectedAccount);
            setHistory(res.data);
        } catch {
            // Handle error
        }
    };

    const chartData = [...history].reverse().map(entry => ({
        date: new Date(entry.calculated_at).toLocaleDateString(),
        score: entry.overall_score,
        ...entry.category_scores,
    }));

    const selectedAccountData = accounts.find(a => a.id === selectedAccount);

    if (loading) {
        return (
            <div className="flex justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    if (accounts.length === 0) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">No platforms connected yet.</p>
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
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Score History</h1>
                {!user?.is_premium && (
                    <p className="mt-1 text-sm text-gray-500">
                        Showing last 30 days. <Link to="/pricing" className="text-indigo-600 hover:underline">Upgrade to Premium</Link> for unlimited history.
                    </p>
                )}
            </div>

            {/* Account selector */}
            <div className="flex space-x-2 mb-6">
                {accounts.map((account) => (
                    <button
                        key={account.id}
                        onClick={() => setSelectedAccount(account.id)}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                            selectedAccount === account.id
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'text-gray-500 hover:bg-gray-100'
                        }`}
                    >
                        @{account.username}
                    </button>
                ))}
            </div>

            {history.length > 0 ? (
                <div className="space-y-8">
                    {/* Overall score chart */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Overall Score Over Time</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={chartData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                <YAxis domain={[0, 100]} tick={{ fontSize: 12 }} />
                                <Tooltip />
                                <Line type="monotone" dataKey="score" stroke="#6366f1" strokeWidth={2} dot={{ r: 4 }} name="Overall Score" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Category scores chart */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Category Scores</h3>
                        <ResponsiveContainer width="100%" height={350}>
                            <LineChart data={chartData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                <YAxis domain={[0, 100]} tick={{ fontSize: 12 }} />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="profile_completeness" stroke="#3b82f6" strokeWidth={2} dot={false} name="Profile" />
                                <Line type="monotone" dataKey="engagement_rate" stroke="#10b981" strokeWidth={2} dot={false} name="Engagement" />
                                <Line type="monotone" dataKey="post_consistency" stroke="#f59e0b" strokeWidth={2} dot={false} name="Consistency" />
                                <Line type="monotone" dataKey="content_performance" stroke="#8b5cf6" strokeWidth={2} dot={false} name="Performance" />
                                <Line type="monotone" dataKey="growth_trend" stroke="#ec4899" strokeWidth={2} dot={false} name="Growth" />
                                <Line type="monotone" dataKey="hashtag_seo" stroke="#06b6d4" strokeWidth={2} dot={false} name="Hashtag/SEO" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Score table */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900">Score Log</h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Change</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {history.map((entry, index) => {
                                        const prev = history[index + 1];
                                        const change = prev ? entry.overall_score - prev.overall_score : 0;
                                        return (
                                            <tr key={entry.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {new Date(entry.calculated_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    {entry.overall_score}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    {entry.grade}
                                                </td>
                                                <td className={`px-6 py-4 whitespace-nowrap text-sm font-medium ${change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-400'}`}>
                                                    {change > 0 ? '+' : ''}{change !== 0 ? change : '—'}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p className="text-gray-500">No score history yet.</p>
                    {selectedAccountData && (
                        <Link
                            to={`/score/${selectedAccountData.platform}`}
                            className="mt-4 inline-block text-indigo-600 hover:text-indigo-500"
                        >
                            Calculate your first score
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}
