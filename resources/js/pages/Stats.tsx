import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { statsApi, type StatsOverview, type SnapshotData } from '@/api/stats';
import { useAuth } from '@/context/AuthContext';
import StatCard from '@/components/StatCard';

type Period = 'day' | 'week' | 'month';

export default function Stats() {
    const { platform } = useParams<{ platform: string }>();
    const { user } = useAuth();
    const [overview, setOverview] = useState<StatsOverview | null>(null);
    const [snapshots, setSnapshots] = useState<SnapshotData[]>([]);
    const [period, setPeriod] = useState<Period>('day');
    const [loading, setLoading] = useState(true);
    const [accountId, setAccountId] = useState<number | null>(null);

    useEffect(() => {
        loadData();
    }, [platform]);

    useEffect(() => {
        if (accountId) {
            loadSnapshots();
        }
    }, [accountId, period]);

    const loadData = async () => {
        try {
            // First get the account for this platform
            const accountsRes = await import('@/api/social').then(m => m.socialApi.getAccounts());
            const account = accountsRes.data.find(a => a.platform === platform);

            if (!account) {
                setLoading(false);
                return;
            }

            setAccountId(account.id);
            const overviewRes = await statsApi.getOverview(account.id);
            setOverview(overviewRes.data);
        } catch {
            // Handle error silently
        } finally {
            setLoading(false);
        }
    };

    const loadSnapshots = async () => {
        if (!accountId) return;
        try {
            const res = await statsApi.getSnapshots(accountId, period);
            setSnapshots(res.data.data);
        } catch {
            // Handle error silently
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    if (!overview || !accountId) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">No data available for this platform.</p>
                <Link to="/connect" className="mt-4 inline-block text-indigo-600 hover:text-indigo-500">
                    Connect a platform
                </Link>
            </div>
        );
    }

    const { growth } = overview;

    return (
        <div>
            <div className="mb-8 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        Statistics — @{overview.account.username}
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        {overview.last_synced_at
                            ? `Last synced: ${new Date(overview.last_synced_at).toLocaleDateString()}`
                            : 'Not yet synced'}
                    </p>
                </div>
                {user?.is_premium && (
                    <Link
                        to={`/stats/${platform}/posts`}
                        className="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100"
                    >
                        Post Analytics
                    </Link>
                )}
            </div>

            {/* Growth cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <StatCard
                    label="Followers"
                    value={overview.current.followers}
                    change={growth.followers.change}
                    changePercent={growth.followers.percent}
                />
                <StatCard
                    label="Engagement Rate"
                    value={`${growth.engagement_rate.current}%`}
                    change={growth.engagement_rate.change}
                    changePercent={growth.engagement_rate.percent}
                />
                <StatCard
                    label="Avg. Likes"
                    value={growth.avg_likes.current}
                    change={growth.avg_likes.change}
                    changePercent={growth.avg_likes.percent}
                />
                <StatCard
                    label="Avg. Views"
                    value={growth.avg_views.current}
                    change={growth.avg_views.change}
                    changePercent={growth.avg_views.percent}
                />
            </div>

            {/* Period selector */}
            <div className="flex space-x-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
                {(['day', 'week', 'month'] as Period[]).map((p) => (
                    <button
                        key={p}
                        onClick={() => setPeriod(p)}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                            period === p
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        {p === 'day' ? 'Daily' : p === 'week' ? 'Weekly' : 'Monthly'}
                    </button>
                ))}
            </div>

            {/* Charts */}
            {snapshots.length > 0 ? (
                <div className="space-y-8">
                    {/* Followers chart */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Followers</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={[...snapshots].reverse()}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                <YAxis tick={{ fontSize: 12 }} />
                                <Tooltip />
                                <Line type="monotone" dataKey="followers" stroke="#6366f1" strokeWidth={2} dot={false} />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Engagement chart */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Engagement Rate (%)</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={[...snapshots].reverse()}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                <YAxis tick={{ fontSize: 12 }} />
                                <Tooltip />
                                <Line type="monotone" dataKey="engagement_rate" stroke="#10b981" strokeWidth={2} dot={false} />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Likes & Views chart */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Avg. Likes & Views</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={[...snapshots].reverse()}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                <YAxis tick={{ fontSize: 12 }} />
                                <Tooltip />
                                <Line type="monotone" dataKey="avg_likes" stroke="#f59e0b" strokeWidth={2} dot={false} name="Likes" />
                                <Line type="monotone" dataKey="avg_views" stroke="#3b82f6" strokeWidth={2} dot={false} name="Views" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p className="text-gray-500">No statistics data yet. Data will appear after your first sync.</p>
                </div>
            )}
        </div>
    );
}
