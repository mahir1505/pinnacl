import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { statsApi, type PostData, type ContentBreakdown } from '@/api/stats';
import { socialApi } from '@/api/social';
import { useAuth } from '@/context/AuthContext';

export default function PostAnalytics() {
    const { platform } = useParams<{ platform: string }>();
    const { user } = useAuth();
    const [posts, setPosts] = useState<PostData[]>([]);
    const [breakdown, setBreakdown] = useState<ContentBreakdown[]>([]);
    const [accountId, setAccountId] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [sortBy, setSortBy] = useState('posted_at');
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    useEffect(() => {
        loadAccount();
    }, [platform]);

    useEffect(() => {
        if (accountId) {
            loadPosts();
            loadBreakdown();
        }
    }, [accountId, sortBy, page]);

    const loadAccount = async () => {
        try {
            const res = await socialApi.getAccounts();
            const account = res.data.find(a => a.platform === platform);
            if (account) {
                setAccountId(account.id);
            }
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    const loadPosts = async () => {
        if (!accountId) return;
        try {
            const res = await statsApi.getPosts(accountId, { sort: sortBy, dir: 'desc', page });
            setPosts(res.data.data);
            setLastPage(res.data.last_page);
        } catch {
            // Handle error
        }
    };

    const loadBreakdown = async () => {
        if (!accountId) return;
        try {
            const res = await statsApi.getContentBreakdown(accountId);
            setBreakdown(res.data);
        } catch {
            // Handle error
        }
    };

    if (!user?.is_premium) {
        return (
            <div className="text-center py-12">
                <h2 className="text-xl font-bold text-gray-900">Premium Feature</h2>
                <p className="mt-2 text-gray-500">Post analytics is available for Premium users.</p>
                <Link to="/pricing" className="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Upgrade to Premium
                </Link>
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
            <div className="mb-8">
                <Link to={`/stats/${platform}`} className="text-sm text-indigo-600 hover:text-indigo-500">
                    &larr; Back to statistics
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Post Analytics</h1>
            </div>

            {/* Content type breakdown */}
            {breakdown.length > 0 && (
                <div className="mb-8">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Content Type Breakdown</h2>
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        {breakdown.map((item) => (
                            <div key={item.type} className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                                <p className="text-sm font-medium text-gray-500 capitalize">{item.type}</p>
                                <p className="text-xl font-bold text-gray-900">{item.count} posts</p>
                                <div className="mt-2 text-xs text-gray-500 space-y-0.5">
                                    <p>Avg. likes: {item.avg_likes.toLocaleString()}</p>
                                    <p>Avg. views: {item.avg_views.toLocaleString()}</p>
                                    <p>Avg. comments: {item.avg_comments.toLocaleString()}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Sort controls */}
            <div className="flex items-center space-x-2 mb-4">
                <span className="text-sm text-gray-500">Sort by:</span>
                {[
                    { key: 'posted_at', label: 'Date' },
                    { key: 'likes', label: 'Likes' },
                    { key: 'views', label: 'Views' },
                    { key: 'comments', label: 'Comments' },
                ].map((option) => (
                    <button
                        key={option.key}
                        onClick={() => { setSortBy(option.key); setPage(1); }}
                        className={`px-3 py-1.5 text-sm rounded-md ${
                            sortBy === option.key
                                ? 'bg-indigo-100 text-indigo-700 font-medium'
                                : 'text-gray-500 hover:bg-gray-100'
                        }`}
                    >
                        {option.label}
                    </button>
                ))}
            </div>

            {/* Posts list */}
            {posts.length > 0 ? (
                <div className="space-y-3">
                    {posts.map((post) => (
                        <div key={post.id} className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-start space-x-4">
                            {post.thumbnail_url && (
                                <img
                                    src={post.thumbnail_url}
                                    alt=""
                                    className="w-16 h-16 rounded-md object-cover flex-shrink-0"
                                />
                            )}
                            <div className="flex-1 min-w-0">
                                <p className="text-sm text-gray-900 truncate">
                                    {post.caption || '(No caption)'}
                                </p>
                                <div className="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                                    <span className="capitalize">{post.post_type}</span>
                                    <span>{new Date(post.posted_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                            <div className="flex space-x-6 text-center flex-shrink-0">
                                <div>
                                    <p className="text-sm font-semibold text-gray-900">{post.likes.toLocaleString()}</p>
                                    <p className="text-xs text-gray-500">Likes</p>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-gray-900">{post.views.toLocaleString()}</p>
                                    <p className="text-xs text-gray-500">Views</p>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-gray-900">{post.comments.toLocaleString()}</p>
                                    <p className="text-xs text-gray-500">Comments</p>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-gray-900">{post.shares.toLocaleString()}</p>
                                    <p className="text-xs text-gray-500">Shares</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p className="text-gray-500">No posts tracked yet. Posts will appear after syncing.</p>
                </div>
            )}

            {/* Pagination */}
            {lastPage > 1 && (
                <div className="mt-6 flex justify-center space-x-2">
                    <button
                        onClick={() => setPage(p => Math.max(1, p - 1))}
                        disabled={page === 1}
                        className="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span className="px-3 py-1.5 text-sm text-gray-500">
                        Page {page} of {lastPage}
                    </span>
                    <button
                        onClick={() => setPage(p => Math.min(lastPage, p + 1))}
                        disabled={page === lastPage}
                        className="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            )}
        </div>
    );
}
