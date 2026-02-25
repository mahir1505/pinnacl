<?php

namespace App\Services\Scoring;

use Carbon\Carbon;

class PostConsistencyScorer implements CategoryScorer
{
    public function key(): string
    {
        return 'post_consistency';
    }

    public function label(): string
    {
        return 'Post Consistency';
    }

    public function weight(): float
    {
        return 0.20;
    }

    public function score(array $profileData, array $metrics, array $posts): int
    {
        $postingFrequency = $metrics['posting_frequency'] ?? 0;

        // If we have posts, calculate consistency from actual dates
        if (!empty($posts)) {
            return $this->scoreFromPosts($posts);
        }

        // Fallback: score from posting frequency metric
        return $this->scoreFromFrequency($postingFrequency);
    }

    private function scoreFromPosts(array $posts): int
    {
        $dates = [];
        foreach ($posts as $post) {
            if (!empty($post['posted_at'])) {
                $date = $post['posted_at'] instanceof Carbon
                    ? $post['posted_at']
                    : Carbon::parse($post['posted_at']);
                $dates[] = $date;
            }
        }

        if (count($dates) < 2) {
            return count($dates) > 0 ? 20 : 0;
        }

        usort($dates, fn($a, $b) => $a->timestamp - $b->timestamp);

        // Calculate gaps between posts (in days)
        $gaps = [];
        for ($i = 1; $i < count($dates); $i++) {
            $gaps[] = $dates[$i - 1]->diffInDays($dates[$i]);
        }

        $avgGap = array_sum($gaps) / count($gaps);
        $postsPerWeek = $avgGap > 0 ? 7 / $avgGap : 0;

        // Score the frequency
        $frequencyScore = $this->scoreFromFrequency($postsPerWeek);

        // Score the regularity (low std deviation = more consistent)
        $mean = $avgGap;
        $variance = 0;
        foreach ($gaps as $gap) {
            $variance += pow($gap - $mean, 2);
        }
        $stdDev = sqrt($variance / count($gaps));
        $coefficient = $mean > 0 ? $stdDev / $mean : 1;

        // Lower coefficient of variation = more regular
        $regularityScore = match (true) {
            $coefficient <= 0.3 => 100,
            $coefficient <= 0.5 => 80,
            $coefficient <= 0.8 => 60,
            $coefficient <= 1.2 => 40,
            default => 20,
        };

        // Blend frequency and regularity (60/40)
        return (int) round($frequencyScore * 0.6 + $regularityScore * 0.4);
    }

    private function scoreFromFrequency(float $postsPerWeek): int
    {
        // Ideal: 3-7 posts per week
        return match (true) {
            $postsPerWeek >= 7 => 90,  // Daily+
            $postsPerWeek >= 5 => 100, // Sweet spot
            $postsPerWeek >= 3 => 85,  // Good
            $postsPerWeek >= 2 => 65,  // Okay
            $postsPerWeek >= 1 => 45,  // Below average
            $postsPerWeek > 0 => 25,   // Rare
            default => 0,
        };
    }
}
