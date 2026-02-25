import { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import api from '@/api/client';
import { Link } from 'react-router-dom';

interface UserListItem {
    id: number;
    name: string;
    email: string;
    role: string;
    is_premium: boolean;
    social_accounts_count: number;
    profile_scores_count: number;
    created_at: string;
}

interface PaginatedUsers {
    data: UserListItem[];
    current_page: number;
    last_page: number;
    total: number;
}

export default function AdminUsers() {
    const { user } = useAuth();
    const [users, setUsers] = useState<PaginatedUsers | null>(null);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [selectedUser, setSelectedUser] = useState<number | null>(null);
    const [userProfiles, setUserProfiles] = useState<any[]>([]);

    useEffect(() => {
        loadUsers();
    }, [page, search]);

    const loadUsers = async () => {
        try {
            const res = await api.get('/admin/users', { params: { page, search } });
            setUsers(res.data);
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    const loadUserProfiles = async (userId: number) => {
        if (selectedUser === userId) {
            setSelectedUser(null);
            return;
        }
        setSelectedUser(userId);
        try {
            const res = await api.get(`/admin/users/${userId}/profiles`);
            setUserProfiles(res.data);
        } catch {
            setUserProfiles([]);
        }
    };

    if (!user?.role || !['admin', 'superadmin'].includes(user.role)) {
        return (
            <div className="text-center py-12">
                <h2 className="text-xl font-bold text-gray-900">Access Denied</h2>
                <p className="mt-2 text-gray-500">Admin privileges required.</p>
            </div>
        );
    }

    return (
        <div>
            <div className="mb-8 flex items-center justify-between">
                <div>
                    <Link to="/admin" className="text-sm text-indigo-600 hover:text-indigo-500">
                        &larr; Admin Dashboard
                    </Link>
                    <h1 className="mt-2 text-2xl font-bold text-gray-900">User Management</h1>
                </div>
            </div>

            {/* Search */}
            <div className="mb-6">
                <input
                    type="text"
                    placeholder="Search by name or email..."
                    value={search}
                    onChange={e => { setSearch(e.target.value); setPage(1); }}
                    className="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>

            {loading ? (
                <div className="flex justify-center py-12">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
                </div>
            ) : users && (
                <>
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Accounts</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scores</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                    <th className="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {users.data.map((u) => (
                                    <>
                                        <tr key={u.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">{u.name}</p>
                                                    <p className="text-sm text-gray-500">{u.email}</p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                                    u.is_premium ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700'
                                                }`}>
                                                    {u.is_premium ? 'Premium' : 'Free'}
                                                </span>
                                                {u.role !== 'user' && (
                                                    <span className="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                                        {u.role}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{u.social_accounts_count}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{u.profile_scores_count}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                {new Date(u.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    onClick={() => loadUserProfiles(u.id)}
                                                    className="text-sm text-indigo-600 hover:text-indigo-500"
                                                >
                                                    {selectedUser === u.id ? 'Hide' : 'Profiles'}
                                                </button>
                                            </td>
                                        </tr>
                                        {selectedUser === u.id && (
                                            <tr key={`${u.id}-profiles`}>
                                                <td colSpan={6} className="px-6 py-4 bg-gray-50">
                                                    {userProfiles.length > 0 ? (
                                                        <div className="space-y-2">
                                                            {userProfiles.map((p: any) => (
                                                                <div key={p.id} className="flex items-center justify-between p-3 bg-white rounded border border-gray-200">
                                                                    <div>
                                                                        <span className="text-sm font-medium text-gray-900 capitalize">{p.platform}</span>
                                                                        <span className="ml-2 text-sm text-gray-500">@{p.username}</span>
                                                                    </div>
                                                                    {p.latest_score ? (
                                                                        <div className="text-sm">
                                                                            <span className="font-bold text-gray-900">{p.latest_score.overall_score}</span>
                                                                            <span className="ml-1 text-gray-500">({p.latest_score.grade})</span>
                                                                        </div>
                                                                    ) : (
                                                                        <span className="text-sm text-gray-400">No score</span>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <p className="text-sm text-gray-500">No profiles connected.</p>
                                                    )}
                                                </td>
                                            </tr>
                                        )}
                                    </>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {users.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between">
                            <p className="text-sm text-gray-500">
                                Showing page {users.current_page} of {users.last_page} ({users.total} users)
                            </p>
                            <div className="flex space-x-2">
                                <button
                                    onClick={() => setPage(p => Math.max(1, p - 1))}
                                    disabled={page === 1}
                                    className="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50"
                                >
                                    Previous
                                </button>
                                <button
                                    onClick={() => setPage(p => Math.min(users.last_page, p + 1))}
                                    disabled={page === users.last_page}
                                    className="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
