<?php

namespace App\Services\Scoring;

use App\Models\ProfileScore;
use App\Models\SocialAccount;
use App\Services\Connectors\ConnectorFactory;

class ScoreCalculator
{
    /** @var CategoryScorer[] */
    private array $scorers;

    private TipsEngine $tipsEngine;

    public function __construct()
    {
        $this->scorers = [
            new ProfileCompletenessScorer(),
            new EngagementRateScorer(),
            new PostConsistencyScorer(),
            new ContentPerformanceScorer(),
            new GrowthTrendScorer(),
            new HashtagSeoScorer(),
        ];

        $this->tipsEngine = new TipsEngine();
    }

    /**
     * Calculate and store the score for a social account.
     */
    public function calculate(SocialAccount $account): ProfileScore
    {
        $connector = ConnectorFactory::make($account->platform);

        // Gather data
        $profileData = $account->profile_data ?? $connector->fetchProfileData($account);
        $profileData['_account_id'] = $account->id; // Pass for GrowthTrendScorer

        $metrics = $connector->fetchMetrics($account);
        $posts = $connector->fetchRecentPosts($account);

        // Calculate each category score
        $categoryScores = [];
        $weightedTotal = 0;
        $totalWeight = 0;

        foreach ($this->scorers as $scorer) {
            $score = $scorer->score($profileData, $metrics, $posts);
            $score = max(0, min(100, $score)); // Clamp 0-100

            $categoryScores[$scorer->key()] = $score;
            $weightedTotal += $score * $scorer->weight();
            $totalWeight += $scorer->weight();
        }

        // Overall score (weighted average)
        $overallScore = $totalWeight > 0 ? (int) round($weightedTotal / $totalWeight) : 0;

        // Generate tips
        $tips = $this->tipsEngine->generate($categoryScores, $profileData, $metrics, $posts);

        // Store the score
        return ProfileScore::create([
            'user_id' => $account->user_id,
            'social_account_id' => $account->id,
            'overall_score' => $overallScore,
            'category_scores' => $categoryScores,
            'tips' => $tips,
        ]);
    }

    /**
     * Calculate score from already-fetched data (no API calls).
     */
    public function calculateFromData(SocialAccount $account, array $profileData, array $metrics, array $posts): ProfileScore
    {
        $profileData['_account_id'] = $account->id;

        $categoryScores = [];
        $weightedTotal = 0;
        $totalWeight = 0;

        foreach ($this->scorers as $scorer) {
            $score = $scorer->score($profileData, $metrics, $posts);
            $score = max(0, min(100, $score));

            $categoryScores[$scorer->key()] = $score;
            $weightedTotal += $score * $scorer->weight();
            $totalWeight += $scorer->weight();
        }

        $overallScore = $totalWeight > 0 ? (int) round($weightedTotal / $totalWeight) : 0;
        $tips = $this->tipsEngine->generate($categoryScores, $profileData, $metrics, $posts);

        return ProfileScore::create([
            'user_id' => $account->user_id,
            'social_account_id' => $account->id,
            'overall_score' => $overallScore,
            'category_scores' => $categoryScores,
            'tips' => $tips,
        ]);
    }

    /**
     * Get the scoring categories with their weights.
     */
    public function categories(): array
    {
        return array_map(fn(CategoryScorer $s) => [
            'key' => $s->key(),
            'label' => $s->label(),
            'weight' => $s->weight(),
        ], $this->scorers);
    }
}
