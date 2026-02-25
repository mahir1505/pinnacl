<?php

namespace App\Services\Scoring;

use App\Models\ScoreSnapshot;
use App\Models\SocialAccount;

class GrowthTrendScorer implements CategoryScorer
{
    public function key(): string
    {
        return 'growth_trend';
    }

    public function label(): string
    {
        return 'Growth Trend';
    }

    public function weight(): float
    {
        return 0.10;
    }

    public function score(array $profileData, array $metrics, array $posts): int
    {
        $accountId = $profileData['_account_id'] ?? null;
        if (!$accountId) {
            return 50; // Neutral if no historical data
        }

        // Get snapshots from the last 30 days
        $snapshots = ScoreSnapshot::where('social_account_id', $accountId)
            ->where('snapshot_date', '>=', now()->subDays(30))
            ->orderBy('snapshot_date')
            ->get();

        if ($snapshots->count() < 2) {
            return 50; // Neutral if not enough data
        }

        $oldest = $snapshots->first();
        $newest = $snapshots->last();

        $oldFollowers = $oldest->followers;
        $newFollowers = $newest->followers;

        if ($oldFollowers === 0) {
            return $newFollowers > 0 ? 80 : 50;
        }

        // Calculate growth percentage
        $growthPercent = (($newFollowers - $oldFollowers) / $oldFollowers) * 100;

        // Also check engagement trend
        $oldEngagement = $oldest->engagement_rate;
        $newEngagement = $newest->engagement_rate;
        $engagementDelta = $oldEngagement > 0
            ? (($newEngagement - $oldEngagement) / $oldEngagement) * 100
            : 0;

        // Follower growth score (70% weight)
        $followerScore = match (true) {
            $growthPercent >= 20 => 100,
            $growthPercent >= 10 => 90,
            $growthPercent >= 5 => 80,
            $growthPercent >= 2 => 70,
            $growthPercent >= 0 => 55,
            $growthPercent >= -2 => 40,
            $growthPercent >= -5 => 30,
            default => 15,
        };

        // Engagement trend score (30% weight)
        $engagementScore = match (true) {
            $engagementDelta >= 10 => 100,
            $engagementDelta >= 5 => 80,
            $engagementDelta >= 0 => 60,
            $engagementDelta >= -5 => 40,
            default => 20,
        };

        return (int) round($followerScore * 0.7 + $engagementScore * 0.3);
    }
}
