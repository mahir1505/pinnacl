<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Stats\SnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private SnapshotService $snapshotService
    ) {}

    /**
     * Get account-level statistics overview.
     */
    public function show(Request $request, int $accountId): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($accountId);

        $growth = $this->snapshotService->getGrowthSummary($account);
        $profileData = $account->profile_data ?? [];

        return response()->json([
            'account' => [
                'id' => $account->id,
                'platform' => $account->platform,
                'username' => $account->username,
            ],
            'current' => [
                'followers' => $profileData['followers'] ?? $growth['followers']['current'] ?? 0,
                'following' => $profileData['following'] ?? 0,
                'post_count' => $profileData['post_count'] ?? 0,
            ],
            'growth' => $growth,
            'last_synced_at' => $account->last_synced_at?->toIso8601String(),
        ]);
    }

    /**
     * Get snapshots (time series data) for charts.
     */
    public function snapshots(Request $request, int $accountId): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($accountId);

        $period = $request->query('period', 'day');
        if (!in_array($period, ['day', 'week', 'month'])) {
            $period = 'day';
        }

        $snapshots = $this->snapshotService->getSnapshots($account, $period);

        return response()->json([
            'period' => $period,
            'data' => $snapshots,
        ]);
    }

    /**
     * Get individual posts with metrics (premium only).
     */
    public function posts(Request $request, int $accountId): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPremium()) {
            return response()->json([
                'message' => 'Post analytics is a Premium feature.',
            ], 403);
        }

        $account = $user->socialAccounts()->findOrFail($accountId);

        $sortBy = $request->query('sort', 'posted_at');
        $sortDir = $request->query('dir', 'desc');
        $type = $request->query('type'); // photo, video, reel, etc.

        $allowedSorts = ['posted_at', 'likes', 'views', 'comments', 'shares'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'posted_at';
        }

        $query = Post::where('social_account_id', $account->id)
            ->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');

        if ($type) {
            $query->where('post_type', $type);
        }

        $posts = $query->paginate(20);

        return response()->json($posts);
    }

    /**
     * Get a single post's details (premium only).
     */
    public function postDetail(Request $request, int $accountId, int $postId): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPremium()) {
            return response()->json([
                'message' => 'Post analytics is a Premium feature.',
            ], 403);
        }

        $account = $user->socialAccounts()->findOrFail($accountId);

        $post = Post::where('social_account_id', $account->id)
            ->findOrFail($postId);

        return response()->json($post);
    }

    /**
     * Get content type breakdown (premium only).
     */
    public function contentBreakdown(Request $request, int $accountId): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPremium()) {
            return response()->json([
                'message' => 'Content breakdown is a Premium feature.',
            ], 403);
        }

        $account = $user->socialAccounts()->findOrFail($accountId);

        $breakdown = Post::where('social_account_id', $account->id)
            ->selectRaw('post_type, COUNT(*) as count, AVG(likes) as avg_likes, AVG(views) as avg_views, AVG(comments) as avg_comments')
            ->groupBy('post_type')
            ->get()
            ->map(fn($row) => [
                'type' => $row->post_type ?? 'unknown',
                'count' => $row->count,
                'avg_likes' => (int) round($row->avg_likes),
                'avg_views' => (int) round($row->avg_views),
                'avg_comments' => (int) round($row->avg_comments),
            ]);

        return response()->json($breakdown);
    }
}
