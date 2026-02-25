<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfileScore;
use App\Services\Scoring\ScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function __construct(
        private ScoreCalculator $calculator
    ) {}

    /**
     * Calculate (or recalculate) score for an account.
     */
    public function calculate(Request $request, int $accountId): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($accountId);

        // Check rate limit: free users 1-2x/week, premium daily
        $user = $request->user();
        $lastScore = $account->scores()->latest()->first();

        if ($lastScore) {
            $minHours = $user->isPremium() ? 12 : 72; // Premium: 2x/day, Free: ~2x/week
            $hoursSinceLastScore = $lastScore->created_at->diffInHours(now());

            if ($hoursSinceLastScore < $minHours) {
                $nextAvailable = $lastScore->created_at->addHours($minHours);
                return response()->json([
                    'message' => 'Score recently calculated. Try again later.',
                    'next_available_at' => $nextAvailable->toIso8601String(),
                    'last_score' => $this->formatScore($lastScore),
                ], 429);
            }
        }

        $score = $this->calculator->calculate($account);

        return response()->json([
            'message' => 'Score calculated successfully.',
            'score' => $this->formatScore($score),
        ], 201);
    }

    /**
     * Get the current (latest) score for an account.
     */
    public function show(Request $request, int $accountId): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($accountId);
        $score = $account->scores()->latest()->first();

        if (!$score) {
            return response()->json([
                'message' => 'No score calculated yet. Calculate your first score.',
                'score' => null,
            ]);
        }

        return response()->json([
            'score' => $this->formatScore($score),
            'categories' => $this->calculator->categories(),
        ]);
    }

    /**
     * Get score history for an account.
     */
    public function history(Request $request, int $accountId): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($accountId);
        $user = $request->user();

        // Free: last 30 days, Premium: unlimited
        $query = $account->scores()->orderBy('created_at', 'desc');

        if (!$user->isPremium()) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $scores = $query->get()->map(fn(ProfileScore $score) => [
            'id' => $score->id,
            'overall_score' => $score->overall_score,
            'grade' => $score->grade(),
            'category_scores' => $score->category_scores,
            'calculated_at' => $score->created_at->toIso8601String(),
        ]);

        return response()->json($scores);
    }

    private function formatScore(ProfileScore $score): array
    {
        return [
            'id' => $score->id,
            'overall_score' => $score->overall_score,
            'grade' => $score->grade(),
            'category_scores' => $score->category_scores,
            'tips' => $score->tips,
            'calculated_at' => $score->created_at->toIso8601String(),
        ];
    }
}
