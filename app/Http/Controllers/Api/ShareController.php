<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;

class ShareController extends Controller
{
    /**
     * Get public score card data for a social account.
     */
    public function show(int $accountId): JsonResponse
    {
        $account = SocialAccount::findOrFail($accountId);
        $score = $account->scores()->latest()->first();

        if (!$score) {
            return response()->json(['message' => 'No score available.'], 404);
        }

        return response()->json([
            'username' => $account->username,
            'platform' => $account->platform,
            'overall_score' => $score->overall_score,
            'grade' => $score->grade(),
            'category_scores' => $score->category_scores,
            'calculated_at' => $score->created_at->toIso8601String(),
        ]);
    }
}
