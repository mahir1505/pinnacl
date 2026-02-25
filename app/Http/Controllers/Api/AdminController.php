<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminReview;
use App\Models\User;
use App\Models\SocialAccount;
use App\Models\ProfileScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * List all users with summary data.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::withCount('socialAccounts', 'profileScores');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    /**
     * Get detailed user information.
     */
    public function userDetail(int $id): JsonResponse
    {
        $user = User::with(['socialAccounts' => function ($q) {
            $q->withCount('scores', 'posts');
        }])->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Get user profiles with scores.
     */
    public function userProfiles(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $accounts = $user->socialAccounts()->with(['scores' => function ($q) {
            $q->latest()->limit(1);
        }])->get();

        $profiles = $accounts->map(function (SocialAccount $account) {
            $latestScore = $account->scores->first();
            return [
                'id' => $account->id,
                'platform' => $account->platform,
                'username' => $account->username,
                'profile_data' => $account->profile_data,
                'last_synced_at' => $account->last_synced_at,
                'latest_score' => $latestScore ? [
                    'overall_score' => $latestScore->overall_score,
                    'grade' => $latestScore->grade(),
                    'category_scores' => $latestScore->category_scores,
                    'tips' => $latestScore->tips,
                    'calculated_at' => $latestScore->created_at->toIso8601String(),
                ] : null,
            ];
        });

        return response()->json($profiles);
    }

    /**
     * Create a review for a user's social account.
     */
    public function createReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'social_account_id' => 'required|exists:social_accounts,id',
            'review_text' => 'required|string|min:10',
        ]);

        $review = AdminReview::create([
            'user_id' => $validated['user_id'],
            'social_account_id' => $validated['social_account_id'],
            'admin_id' => $request->user()->id,
            'review_text' => $validated['review_text'],
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Review created successfully.',
            'review' => $review,
        ], 201);
    }

    /**
     * Get pending reviews (premium users without recent review).
     */
    public function pendingReviews(): JsonResponse
    {
        // Find premium users who haven't had a review in the last 30 days
        $premiumUsers = User::where('is_premium', true)
            ->with(['socialAccounts' => function ($q) {
                $q->with(['scores' => function ($sq) {
                    $sq->latest()->limit(1);
                }]);
            }])
            ->get();

        $pending = [];
        foreach ($premiumUsers as $user) {
            foreach ($user->socialAccounts as $account) {
                $lastReview = AdminReview::where('user_id', $user->id)
                    ->where('social_account_id', $account->id)
                    ->latest()
                    ->first();

                $needsReview = !$lastReview || $lastReview->created_at->lt(now()->subDays(30));

                if ($needsReview) {
                    $latestScore = $account->scores->first();
                    $pending[] = [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ],
                        'account' => [
                            'id' => $account->id,
                            'platform' => $account->platform,
                            'username' => $account->username,
                        ],
                        'latest_score' => $latestScore ? [
                            'overall_score' => $latestScore->overall_score,
                            'grade' => $latestScore->grade(),
                        ] : null,
                        'last_review_at' => $lastReview?->created_at?->toIso8601String(),
                    ];
                }
            }
        }

        return response()->json($pending);
    }

    /**
     * Get all reviews.
     */
    public function allReviews(Request $request): JsonResponse
    {
        $reviews = AdminReview::with(['user:id,name,email', 'socialAccount:id,platform,username', 'admin:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reviews);
    }

    /**
     * Platform-wide analytics.
     */
    public function analytics(): JsonResponse
    {
        $totalUsers = User::count();
        $premiumUsers = User::where('is_premium', true)->count();
        $totalAccounts = SocialAccount::count();
        $totalScores = ProfileScore::count();

        $platformBreakdown = SocialAccount::selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform');

        $avgScore = ProfileScore::avg('overall_score');

        $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();

        return response()->json([
            'total_users' => $totalUsers,
            'premium_users' => $premiumUsers,
            'total_accounts' => $totalAccounts,
            'total_scores' => $totalScores,
            'avg_score' => round($avgScore ?? 0, 1),
            'platform_breakdown' => $platformBreakdown,
            'recent_signups' => $recentUsers,
        ]);
    }
}
