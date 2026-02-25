<?php

namespace App\Services\Scoring;

class ContentPerformanceScorer implements CategoryScorer
{
    public function key(): string
    {
        return 'content_performance';
    }

    public function label(): string
    {
        return 'Content Performance';
    }

    public function weight(): float
    {
        return 0.20;
    }

    public function score(array $profileData, array $metrics, array $posts): int
    {
        $followers = $profileData['followers'] ?? $metrics['followers'] ?? 0;

        if ($followers === 0 || empty($posts)) {
            return 0;
        }

        // Calculate average likes/views relative to followers
        $totalLikes = 0;
        $totalViews = 0;
        $totalComments = 0;

        foreach ($posts as $post) {
            $totalLikes += $post['likes'] ?? 0;
            $totalViews += $post['views'] ?? 0;
            $totalComments += $post['comments'] ?? 0;
        }

        $postCount = count($posts);
        $avgLikes = $totalLikes / $postCount;
        $avgViews = $totalViews / $postCount;

        // Like-to-follower ratio (what % of followers like your posts)
        $likeRatio = ($avgLikes / $followers) * 100;

        // View-to-follower ratio (for video platforms)
        $viewRatio = $avgViews > 0 ? ($avgViews / $followers) * 100 : 0;

        // Score based on like ratio
        $likeScore = match (true) {
            $likeRatio >= 10 => 100,
            $likeRatio >= 5 => 85,
            $likeRatio >= 3 => 70,
            $likeRatio >= 1.5 => 55,
            $likeRatio >= 0.5 => 35,
            $likeRatio > 0 => 20,
            default => 0,
        };

        // If video platform, blend with view ratio
        if ($avgViews > 0) {
            $viewScore = match (true) {
                $viewRatio >= 100 => 100,
                $viewRatio >= 50 => 85,
                $viewRatio >= 25 => 70,
                $viewRatio >= 10 => 55,
                $viewRatio >= 5 => 40,
                $viewRatio > 0 => 25,
                default => 0,
            };
            return (int) round($likeScore * 0.5 + $viewScore * 0.5);
        }

        return $likeScore;
    }
}
