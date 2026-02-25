import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import api from '@/api/client';

interface Analytics {
    total_users: number;
    premium_users: number;
    total_accounts: number;
    total_scores: number;
    avg_score: number;
    platform_breakdown: Record<string, number>;
    recent_signups: number;
}

export default function AdminDashboard() {
    const { user } = useAuth();
    const [analytics, setAnalytics] = useState<Analytics | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadAnalytics();
    }, []);

    const loadAnalytics = async () => {
        try {
            const res = await api.get('/admin/analytics');
            setAnalytics(res.data);
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    if (!user?.role || !['admin', 'superadmin'].includes(user.role)) {
        return (
            <div className="text-center py-12">
                <h2 className="text-xl font-bold text-gray-900">Access Denied</h2>
                <p className="mt-2 text-gray-500">You need admin privileges to access this page.</p>
            </div>
        );
    }

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
                    <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                    <p className="mt-1 text-sm text-gray-500">Platform overview and management</p>
                </div>
                <div className="flex space-x-3">
                    <Link to="/admin/users" className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100">
                        Manage Users
                    </Link>
                    <Link to="/admin/reviews" className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Reviews
                    </Link>
                </div>
            </div>

            {analytics && (
                <>
                    {/* Stats grid */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                            <p className="text-sm font-medium text-gray-500">Total Users</p>
                            <p className="mt-1 text-2xl font-bold text-gray-900">{analytics.total_users}</p>
                            <p className="mt-1 text-xs text-green-600">+{analytics.recent_signups} this week</p>
                        </div>
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                            <p className="text-sm font-medium text-gray-500">Premium Users</p>
                            <p className="mt-1 text-2xl font-bold text-gray-900">{analytics.premium_users}</p>
                            <p className="mt-1 text-xs text-gray-500">
                                {analytics.total_users > 0
                                    ? ((analytics.premium_users / analytics.total_users) * 100).toFixed(1)
                                    : 0}% conversion
                            </p>
                        </div>
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                            <p className="text-sm font-medium text-gray-500">Connected Accounts</p>
                            <p className="mt-1 text-2xl font-bold text-gray-900">{analytics.total_accounts}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                            <p className="text-sm font-medium text-gray-500">Avg. Score</p>
                            <p className="mt-1 text-2xl font-bold text-gray-900">{analytics.avg_score}</p>
                            <p className="mt-1 text-xs text-gray-500">{analytics.total_scores} total scores</p>
                        </div>
                    </div>

                    {/* Platform breakdown */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Platform Breakdown</h3>
                        <div className="space-y-3">
                            {Object.entries(analytics.platform_breakdown).map(([platform, count]) => (
                                <div key={platform} className="flex items-center justify-between">
                                    <span className="text-sm font-medium text-gray-700 capitalize">{platform}</span>
                                    <div className="flex items-center space-x-3">
                                        <div className="w-48 bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-indigo-500 h-2 rounded-full"
                                                style={{ width: `${analytics.total_accounts > 0 ? (count / analytics.total_accounts) * 100 : 0}%` }}
                                            ></div>
                                        </div>
                                        <span className="text-sm text-gray-500 w-8 text-right">{count}</span>
                                    </div>
                                </div>
                            ))}
                            {Object.keys(analytics.platform_breakdown).length === 0 && (
                                <p className="text-sm text-gray-500">No accounts connected yet.</p>
                            )}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
