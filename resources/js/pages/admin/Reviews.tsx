import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import api from '@/api/client';

interface PendingReview {
    user: { id: number; name: string; email: string };
    account: { id: number; platform: string; username: string };
    latest_score: { overall_score: number; grade: string } | null;
    last_review_at: string | null;
}

interface CompletedReview {
    id: number;
    review_text: string;
    status: string;
    created_at: string;
    user: { id: number; name: string; email: string };
    social_account: { id: number; platform: string; username: string };
    admin: { id: number; name: string };
}

export default function AdminReviews() {
    const { user } = useAuth();
    const [tab, setTab] = useState<'pending' | 'all'>('pending');
    const [pending, setPending] = useState<PendingReview[]>([]);
    const [reviews, setReviews] = useState<{ data: CompletedReview[]; current_page: number; last_page: number }>({ data: [], current_page: 1, last_page: 1 });
    const [loading, setLoading] = useState(true);
    const [reviewText, setReviewText] = useState('');
    const [reviewingFor, setReviewingFor] = useState<{ userId: number; accountId: number } | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (tab === 'pending') {
            loadPending();
        } else {
            loadReviews();
        }
    }, [tab]);

    const loadPending = async () => {
        setLoading(true);
        try {
            const res = await api.get('/admin/reviews/pending');
            setPending(res.data);
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    const loadReviews = async (page = 1) => {
        setLoading(true);
        try {
            const res = await api.get('/admin/reviews', { params: { page } });
            setReviews(res.data);
        } catch {
            // Handle error
        } finally {
            setLoading(false);
        }
    };

    const submitReview = async () => {
        if (!reviewingFor || !reviewText.trim()) return;
        setSubmitting(true);
        try {
            await api.post('/admin/reviews', {
                user_id: reviewingFor.userId,
                social_account_id: reviewingFor.accountId,
                review_text: reviewText,
            });
            setReviewText('');
            setReviewingFor(null);
            loadPending();
        } catch {
            // Handle error
        } finally {
            setSubmitting(false);
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
            <div className="mb-8">
                <Link to="/admin" className="text-sm text-indigo-600 hover:text-indigo-500">
                    &larr; Admin Dashboard
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Reviews</h1>
                <p className="mt-1 text-sm text-gray-500">
                    Personal reviews for premium users. You can also use the MCP server for AI-assisted reviews.
                </p>
            </div>

            {/* Tabs */}
            <div className="flex space-x-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
                {(['pending', 'all'] as const).map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                            tab === t ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        {t === 'pending' ? `Pending (${pending.length})` : 'All Reviews'}
                    </button>
                ))}
            </div>

            {loading ? (
                <div className="flex justify-center py-12">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
                </div>
            ) : tab === 'pending' ? (
                <div className="space-y-4">
                    {pending.length === 0 ? (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                            <p className="text-gray-500">No pending reviews. All premium users are up to date.</p>
                        </div>
                    ) : (
                        pending.map((item) => (
                            <div key={`${item.user.id}-${item.account.id}`} className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{item.user.name}</p>
                                        <p className="text-sm text-gray-500">{item.user.email}</p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            <span className="capitalize">{item.account.platform}</span> — @{item.account.username}
                                            {item.latest_score && (
                                                <span className="ml-2 font-medium">Score: {item.latest_score.overall_score} ({item.latest_score.grade})</span>
                                            )}
                                        </p>
                                        {item.last_review_at && (
                                            <p className="text-xs text-gray-400">
                                                Last review: {new Date(item.last_review_at).toLocaleDateString()}
                                            </p>
                                        )}
                                    </div>
                                    <button
                                        onClick={() => setReviewingFor({ userId: item.user.id, accountId: item.account.id })}
                                        className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                                    >
                                        Write Review
                                    </button>
                                </div>

                                {reviewingFor?.userId === item.user.id && reviewingFor?.accountId === item.account.id && (
                                    <div className="mt-4 border-t border-gray-200 pt-4">
                                        <textarea
                                            value={reviewText}
                                            onChange={e => setReviewText(e.target.value)}
                                            placeholder="Write your review..."
                                            rows={4}
                                            className="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        ></textarea>
                                        <div className="mt-2 flex space-x-2">
                                            <button
                                                onClick={submitReview}
                                                disabled={submitting || !reviewText.trim()}
                                                className="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50"
                                            >
                                                {submitting ? 'Submitting...' : 'Submit Review'}
                                            </button>
                                            <button
                                                onClick={() => { setReviewingFor(null); setReviewText(''); }}
                                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))
                    )}
                </div>
            ) : (
                <div className="space-y-4">
                    {reviews.data.length === 0 ? (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                            <p className="text-gray-500">No reviews yet.</p>
                        </div>
                    ) : (
                        reviews.data.map((review) => (
                            <div key={review.id} className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                                <div className="flex items-center justify-between mb-2">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{review.user.name}</p>
                                        <p className="text-xs text-gray-500">
                                            <span className="capitalize">{review.social_account.platform}</span> — @{review.social_account.username}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs text-gray-500">by {review.admin.name}</p>
                                        <p className="text-xs text-gray-400">{new Date(review.created_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                                <p className="text-sm text-gray-700 whitespace-pre-wrap">{review.review_text}</p>
                            </div>
                        ))
                    )}

                    {reviews.last_page > 1 && (
                        <div className="flex justify-center space-x-2">
                            <button
                                onClick={() => loadReviews(reviews.current_page - 1)}
                                disabled={reviews.current_page === 1}
                                className="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <span className="px-3 py-1.5 text-sm text-gray-500">
                                Page {reviews.current_page} of {reviews.last_page}
                            </span>
                            <button
                                onClick={() => loadReviews(reviews.current_page + 1)}
                                disabled={reviews.current_page === reviews.last_page}
                                className="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
