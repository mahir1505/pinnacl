<?php

namespace App\Services\Scoring;

class TipsEngine
{
    /**
     * Generate tips based on category scores and data.
     *
     * @return array<array{category: string, tip: string, priority: string}>
     */
    public function generate(array $categoryScores, array $profileData, array $metrics, array $posts): array
    {
        $tips = [];

        // Profile Completeness tips
        $profileScore = $categoryScores['profile_completeness'] ?? 0;
        if (empty($profileData['bio'])) {
            $tips[] = ['category' => 'profile_completeness', 'tip' => 'Add a bio to your profile. A compelling bio helps visitors understand who you are and what you offer.', 'priority' => 'high'];
        } elseif (strlen($profileData['bio'] ?? '') < 50) {
            $tips[] = ['category' => 'profile_completeness', 'tip' => 'Your bio is quite short. Add more details about what you do and what followers can expect.', 'priority' => 'medium'];
        }
        if (empty($profileData['website'])) {
            $tips[] = ['category' => 'profile_completeness', 'tip' => 'Add a link to your bio. Use a link-in-bio tool to drive traffic to your content, store, or website.', 'priority' => 'high'];
        }
        if (empty($profileData['profile_image'])) {
            $tips[] = ['category' => 'profile_completeness', 'tip' => 'Add a profile photo. Profiles with photos get significantly more engagement.', 'priority' => 'high'];
        }

        // Engagement Rate tips
        $engagementRate = $metrics['engagement_rate'] ?? 0;
        if ($engagementRate < 1) {
            $tips[] = ['category' => 'engagement_rate', 'tip' => 'Your engagement is very low. Try asking questions in your captions and respond to every comment you receive.', 'priority' => 'high'];
        } elseif ($engagementRate < 2) {
            $tips[] = ['category' => 'engagement_rate', 'tip' => 'Your engagement is below average. Use call-to-actions like "Save this for later" or "Tag someone who needs this".', 'priority' => 'medium'];
        } elseif ($engagementRate < 3.5) {
            $tips[] = ['category' => 'engagement_rate', 'tip' => 'Your engagement is average. To push higher, try carousel posts, polls, or behind-the-scenes content.', 'priority' => 'low'];
        }

        // Post Consistency tips
        $postingFreq = $metrics['posting_frequency'] ?? 0;
        if ($postingFreq < 1) {
            $tips[] = ['category' => 'post_consistency', 'tip' => 'You post less than once a week. Consistency is key — aim for at least 3 posts per week.', 'priority' => 'high'];
        } elseif ($postingFreq < 3) {
            $tips[] = ['category' => 'post_consistency', 'tip' => 'Try increasing your posting frequency to 3-5 times per week. Use a content calendar to plan ahead.', 'priority' => 'medium'];
        } elseif ($postingFreq > 10) {
            $tips[] = ['category' => 'post_consistency', 'tip' => 'You post very frequently. Make sure quantity isn\'t hurting quality — focus on your best-performing content types.', 'priority' => 'low'];
        }

        // Content Performance tips
        $contentScore = $categoryScores['content_performance'] ?? 0;
        if ($contentScore < 40 && !empty($posts)) {
            $tips[] = ['category' => 'content_performance', 'tip' => 'Your content is underperforming. Analyze your top posts and create more content in that style.', 'priority' => 'high'];
        } elseif ($contentScore < 60) {
            $tips[] = ['category' => 'content_performance', 'tip' => 'Try experimenting with different content formats (reels, carousels, stories) to find what resonates with your audience.', 'priority' => 'medium'];
        }

        // Growth Trend tips
        $growthScore = $categoryScores['growth_trend'] ?? 50;
        if ($growthScore < 40) {
            $tips[] = ['category' => 'growth_trend', 'tip' => 'Your growth is declining. Focus on shareable content and collaborations to reach new audiences.', 'priority' => 'high'];
        } elseif ($growthScore < 55) {
            $tips[] = ['category' => 'growth_trend', 'tip' => 'Your growth is stagnant. Try new content themes, trending topics, or cross-platform promotion.', 'priority' => 'medium'];
        }

        // Hashtag & SEO tips
        if (!empty($posts)) {
            $postsWithHashtags = 0;
            $totalHashtags = 0;
            $postsWithQuestions = 0;

            foreach ($posts as $post) {
                $caption = $post['caption'] ?? '';
                preg_match_all('/#\w+/', $caption, $matches);
                if (count($matches[0]) > 0) $postsWithHashtags++;
                $totalHashtags += count($matches[0]);
                if (str_contains($caption, '?')) $postsWithQuestions++;
            }

            $hashtagRate = count($posts) > 0 ? $postsWithHashtags / count($posts) : 0;
            $avgHashtags = count($posts) > 0 ? $totalHashtags / count($posts) : 0;

            if ($hashtagRate < 0.5) {
                $tips[] = ['category' => 'hashtag_seo', 'tip' => 'Use hashtags more consistently. Aim to include 5-15 relevant hashtags on every post for better discoverability.', 'priority' => 'medium'];
            }
            if ($avgHashtags > 25) {
                $tips[] = ['category' => 'hashtag_seo', 'tip' => 'You\'re using too many hashtags. Focus on 10-15 highly relevant ones instead of spamming.', 'priority' => 'low'];
            }
            if ($postsWithQuestions < count($posts) * 0.2) {
                $tips[] = ['category' => 'hashtag_seo', 'tip' => 'Ask questions in your captions to boost comments and engagement. Questions make your posts more interactive.', 'priority' => 'low'];
            }
        }

        // Sort by priority
        usort($tips, function ($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['priority']] ?? 3) <=> ($order[$b['priority']] ?? 3);
        });

        return $tips;
    }
}
