<?php

namespace App\Services\Scoring;

class ProfileCompletenessScorer implements CategoryScorer
{
    public function key(): string
    {
        return 'profile_completeness';
    }

    public function label(): string
    {
        return 'Profile Completeness';
    }

    public function weight(): float
    {
        return 0.15;
    }

    public function score(array $profileData, array $metrics, array $posts): int
    {
        $score = 0;
        $maxPoints = 0;

        // Has a bio (20 points)
        $maxPoints += 20;
        $bio = $profileData['bio'] ?? '';
        if (strlen($bio) > 0) {
            $score += strlen($bio) >= 50 ? 20 : 10;
        }

        // Has profile image (15 points)
        $maxPoints += 15;
        if (!empty($profileData['profile_image'])) {
            $score += 15;
        }

        // Has a name set (10 points)
        $maxPoints += 10;
        if (!empty($profileData['name'])) {
            $score += 10;
        }

        // Has website/link (15 points)
        $maxPoints += 15;
        if (!empty($profileData['website'])) {
            $score += 15;
        }

        // Verified status (10 points)
        $maxPoints += 10;
        if (!empty($profileData['verified'])) {
            $score += 10;
        }

        // Has posts (15 points)
        $maxPoints += 15;
        $postCount = $profileData['post_count'] ?? $metrics['total_posts'] ?? 0;
        if ($postCount >= 10) {
            $score += 15;
        } elseif ($postCount >= 1) {
            $score += 8;
        }

        // Follower/following ratio reasonable (15 points)
        $maxPoints += 15;
        $followers = $profileData['followers'] ?? 0;
        $following = $profileData['following'] ?? 0;
        if ($followers > 0 && $following > 0) {
            $ratio = $followers / $following;
            if ($ratio >= 1.0) {
                $score += 15;
            } elseif ($ratio >= 0.5) {
                $score += 10;
            } else {
                $score += 5;
            }
        } elseif ($followers > 0) {
            $score += 15;
        }

        return $maxPoints > 0 ? (int) round(($score / $maxPoints) * 100) : 0;
    }
}
