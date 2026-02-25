<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;

interface PlatformConnector
{
    /**
     * Get the platform identifier (instagram, tiktok, youtube, x, linkedin).
     */
    public function platform(): string;

    /**
     * Get the OAuth scopes needed for this platform.
     */
    public function scopes(): array;

    /**
     * Fetch profile data from the platform API.
     * Returns normalized array: [followers, following, bio, profile_image, etc.]
     */
    public function fetchProfileData(SocialAccount $account): array;

    /**
     * Fetch recent posts from the platform API.
     * Returns array of normalized post data.
     */
    public function fetchRecentPosts(SocialAccount $account, int $limit = 25): array;

    /**
     * Fetch engagement metrics for account-level statistics.
     * Returns: [avg_likes, avg_views, avg_comments, engagement_rate, posting_frequency]
     */
    public function fetchMetrics(SocialAccount $account): array;
}
