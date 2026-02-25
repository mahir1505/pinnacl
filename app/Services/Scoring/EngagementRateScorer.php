<?php

namespace App\Services\Scoring;

class EngagementRateScorer implements CategoryScorer
{
    public function key(): string
    {
        return 'engagement_rate';
    }

    public function label(): string
    {
        return 'Engagement Rate';
    }

    public function weight(): float
    {
        return 0.25;
    }

    public function score(array $profileData, array $metrics, array $posts): int
    {
        $engagementRate = $metrics['engagement_rate'] ?? 0;
        $followers = $profileData['followers'] ?? $metrics['followers'] ?? 0;

        // If no data, return 0
        if ($engagementRate <= 0 && empty($posts)) {
            return 0;
        }

        // Calculate from posts if metrics not available
        if ($engagementRate <= 0 && !empty($posts) && $followers > 0) {
            $totalEngagement = 0;
            foreach ($posts as $post) {
                $totalEngagement += ($post['likes'] ?? 0) + ($post['comments'] ?? 0) + ($post['shares'] ?? 0);
            }
            $engagementRate = ($totalEngagement / count($posts) / $followers) * 100;
        }

        // Score based on engagement rate benchmarks
        // These vary by platform, but general benchmarks:
        // < 1%: Poor, 1-3%: Average, 3-6%: Good, 6-10%: Very Good, 10%+: Excellent
        if ($followers < 1000) {
            // Micro accounts typically have higher engagement
            return $this->scoreForRange($engagementRate, 2.0, 5.0, 10.0, 15.0);
        } elseif ($followers < 10000) {
            return $this->scoreForRange($engagementRate, 1.5, 3.5, 7.0, 12.0);
        } elseif ($followers < 100000) {
            return $this->scoreForRange($engagementRate, 1.0, 3.0, 5.0, 8.0);
        } else {
            // Large accounts have lower engagement rates naturally
            return $this->scoreForRange($engagementRate, 0.5, 1.5, 3.0, 5.0);
        }
    }

    private function scoreForRange(float $rate, float $poor, float $avg, float $good, float $excellent): int
    {
        if ($rate >= $excellent) return 100;
        if ($rate >= $good) return 75 + (int) (($rate - $good) / ($excellent - $good) * 25);
        if ($rate >= $avg) return 50 + (int) (($rate - $avg) / ($good - $avg) * 25);
        if ($rate >= $poor) return 25 + (int) (($rate - $poor) / ($avg - $poor) * 25);
        if ($rate > 0) return (int) (($rate / $poor) * 25);
        return 0;
    }
}
