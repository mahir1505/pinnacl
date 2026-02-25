<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;
use Carbon\Carbon;

class LinkedInConnector extends AbstractConnector
{
    private const API_BASE = 'https://api.linkedin.com/v2';

    public function platform(): string
    {
        return 'linkedin';
    }

    public function scopes(): array
    {
        return [
            'openid',
            'profile',
            'w_member_social',
        ];
    }

    public function fetchProfileData(SocialAccount $account): array
    {
        $data = $this->apiGet($account, self::API_BASE . '/userinfo');

        if (empty($data)) {
            return [];
        }

        return [
            'followers' => 0, // LinkedIn requires separate API call for follower count
            'following' => 0,
            'bio' => $data['headline'] ?? '',
            'profile_image' => $data['picture'] ?? '',
            'username' => $data['name'] ?? '',
            'name' => trim(($data['given_name'] ?? '') . ' ' . ($data['family_name'] ?? '')),
            'post_count' => 0,
            'verified' => $data['email_verified'] ?? false,
        ];
    }

    public function fetchRecentPosts(SocialAccount $account, int $limit = 25): array
    {
        // LinkedIn's post API is limited; returning empty for now
        // Full implementation requires LinkedIn Marketing API approval
        return [];
    }

    public function fetchMetrics(SocialAccount $account): array
    {
        $profile = $this->fetchProfileData($account);

        return [
            'followers' => $profile['followers'] ?? 0,
            'following' => $profile['following'] ?? 0,
            'avg_likes' => 0,
            'avg_views' => 0,
            'avg_comments' => 0,
            'total_posts' => $profile['post_count'] ?? 0,
            'engagement_rate' => 0,
            'posting_frequency' => 0,
        ];
    }
}
