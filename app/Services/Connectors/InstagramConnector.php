<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;
use Carbon\Carbon;

class InstagramConnector extends AbstractConnector
{
    private const API_BASE = 'https://graph.instagram.com/v21.0';

    public function platform(): string
    {
        return 'instagram';
    }

    public function scopes(): array
    {
        return [
            'instagram_business_basic',
            'instagram_business_manage_insights',
        ];
    }

    public function fetchProfileData(SocialAccount $account): array
    {
        $data = $this->apiGet($account, self::API_BASE . '/me', [
            'fields' => 'id,username,name,biography,profile_picture_url,followers_count,follows_count,media_count,website',
        ]);

        if (empty($data)) {
            return [];
        }

        return [
            'followers' => $data['followers_count'] ?? 0,
            'following' => $data['follows_count'] ?? 0,
            'bio' => $data['biography'] ?? '',
            'profile_image' => $data['profile_picture_url'] ?? '',
            'username' => $data['username'] ?? '',
            'name' => $data['name'] ?? '',
            'post_count' => $data['media_count'] ?? 0,
            'website' => $data['website'] ?? '',
            'verified' => false, // Not available via basic API
        ];
    }

    public function fetchRecentPosts(SocialAccount $account, int $limit = 25): array
    {
        $data = $this->apiGet($account, self::API_BASE . '/me/media', [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
            'limit' => $limit,
        ]);

        if (empty($data['data'])) {
            return [];
        }

        return array_map(function ($post) {
            return [
                'platform_post_id' => $post['id'],
                'post_type' => $this->mapMediaType($post['media_type'] ?? ''),
                'caption' => $post['caption'] ?? '',
                'thumbnail_url' => $post['thumbnail_url'] ?? $post['media_url'] ?? '',
                'post_url' => $post['permalink'] ?? '',
                'likes' => $post['like_count'] ?? 0,
                'views' => 0, // Not available for all types
                'comments' => $post['comments_count'] ?? 0,
                'shares' => 0,
                'saves' => 0,
                'posted_at' => isset($post['timestamp']) ? Carbon::parse($post['timestamp']) : null,
            ];
        }, $data['data']);
    }

    public function fetchMetrics(SocialAccount $account): array
    {
        $profile = $this->fetchProfileData($account);
        $posts = $this->fetchRecentPosts($account);

        $totalLikes = array_sum(array_column($posts, 'likes'));
        $totalComments = array_sum(array_column($posts, 'comments'));
        $postCount = count($posts);

        $postDates = array_filter(array_map(fn($p) => $p['posted_at'], $posts));

        return [
            'followers' => $profile['followers'] ?? 0,
            'following' => $profile['following'] ?? 0,
            'avg_likes' => $postCount > 0 ? (int) round($totalLikes / $postCount) : 0,
            'avg_views' => 0,
            'avg_comments' => $postCount > 0 ? (int) round($totalComments / $postCount) : 0,
            'total_posts' => $profile['post_count'] ?? 0,
            'engagement_rate' => $this->calculateEngagementRate(
                $totalLikes + $totalComments,
                $profile['followers'] ?? 0,
                $postCount
            ),
            'posting_frequency' => $this->calculatePostingFrequency($postDates),
        ];
    }

    private function mapMediaType(string $type): string
    {
        return match (strtoupper($type)) {
            'IMAGE' => 'photo',
            'VIDEO' => 'video',
            'CAROUSEL_ALBUM' => 'photo',
            'REEL' => 'reel',
            default => 'photo',
        };
    }
}
