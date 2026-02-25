<?php

namespace App\Services\Scoring;

class HashtagSeoScorer implements CategoryScorer
{
    public function key(): string
    {
        return 'hashtag_seo';
    }

    public function label(): string
    {
        return 'Hashtag & SEO';
    }

    public function weight(): float
    {
        return 0.10;
    }

    public function score(array $profileData, array $metrics, array $posts): int
    {
        if (empty($posts)) {
            // Score only bio keywords if no posts
            return $this->scoreBio($profileData);
        }

        $bioScore = $this->scoreBio($profileData);
        $hashtagScore = $this->scoreHashtags($posts);
        $captionScore = $this->scoreCaptions($posts);

        return (int) round($bioScore * 0.3 + $hashtagScore * 0.4 + $captionScore * 0.3);
    }

    private function scoreBio(array $profileData): int
    {
        $bio = $profileData['bio'] ?? '';
        if (empty($bio)) {
            return 0;
        }

        $score = 0;

        // Has keywords (longer bio = more keywords likely)
        if (strlen($bio) >= 100) $score += 40;
        elseif (strlen($bio) >= 50) $score += 25;
        elseif (strlen($bio) > 0) $score += 10;

        // Contains relevant elements
        if (preg_match('/https?:\/\//', $bio)) $score += 20; // Has a link
        if (preg_match('/#\w+/', $bio)) $score += 15; // Has hashtags
        if (preg_match('/[\x{1F600}-\x{1F9FF}]/u', $bio)) $score += 10; // Has emoji (engaging)
        if (preg_match('/\||\-|•|📍|🔗|👇|📧/', $bio)) $score += 15; // Structured/formatted

        return min(100, $score);
    }

    private function scoreHashtags(array $posts): int
    {
        $totalHashtags = 0;
        $postsWithHashtags = 0;

        foreach ($posts as $post) {
            $caption = $post['caption'] ?? '';
            preg_match_all('/#\w+/', $caption, $matches);
            $count = count($matches[0]);

            if ($count > 0) {
                $postsWithHashtags++;
                $totalHashtags += $count;
            }
        }

        $postCount = count($posts);
        if ($postCount === 0) return 0;

        $usageRate = ($postsWithHashtags / $postCount) * 100;
        $avgPerPost = $totalHashtags / $postCount;

        // Score based on usage rate
        $usageScore = match (true) {
            $usageRate >= 80 => 50,
            $usageRate >= 50 => 35,
            $usageRate >= 20 => 20,
            default => 5,
        };

        // Score based on average count (sweet spot: 5-15 for Instagram, 3-5 for others)
        $countScore = match (true) {
            $avgPerPost >= 5 && $avgPerPost <= 15 => 50,
            $avgPerPost >= 3 && $avgPerPost <= 20 => 35,
            $avgPerPost >= 1 => 20,
            default => 0,
        };

        return $usageScore + $countScore;
    }

    private function scoreCaptions(array $posts): int
    {
        $postCount = count($posts);
        if ($postCount === 0) return 0;

        $totalLength = 0;
        $postsWithCTA = 0;
        $postsWithQuestions = 0;

        foreach ($posts as $post) {
            $caption = $post['caption'] ?? '';
            $totalLength += strlen($caption);

            // Check for call-to-actions
            if (preg_match('/\b(link in bio|check out|follow|comment|share|tag|dm|click|tap|swipe)\b/i', $caption)) {
                $postsWithCTA++;
            }

            // Check for questions (engagement drivers)
            if (str_contains($caption, '?')) {
                $postsWithQuestions++;
            }
        }

        $avgLength = $totalLength / $postCount;
        $ctaRate = ($postsWithCTA / $postCount) * 100;
        $questionRate = ($postsWithQuestions / $postCount) * 100;

        $score = 0;

        // Caption length
        if ($avgLength >= 100) $score += 40;
        elseif ($avgLength >= 50) $score += 25;
        elseif ($avgLength > 0) $score += 10;

        // CTA usage
        if ($ctaRate >= 40) $score += 30;
        elseif ($ctaRate >= 20) $score += 20;
        elseif ($ctaRate > 0) $score += 10;

        // Questions
        if ($questionRate >= 30) $score += 30;
        elseif ($questionRate >= 15) $score += 20;
        elseif ($questionRate > 0) $score += 10;

        return min(100, $score);
    }
}
