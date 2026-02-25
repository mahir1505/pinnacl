<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TikTokConnector extends AbstractConnector
{
    private const API_BASE = 'https://open.tiktokapis.com/v2';

    public function platform(): string
    {
        return 'tiktok';
    }

    public function scopes(): array
    {
        return [
            'user.info.basic',
            'user.info.stats',
            'video.list',
        ];
    }

    public function fetchProfileData(SocialAccount $account): array
    {
        $response = Http::withToken($account->access_token)
            ->post(self::API_BASE . '/user/info/', [
                'fields' => 'open_id,display_name,avatar_url,follower_count,following_count,likes_count,video_count,bio_description,is_verified',
            ]);

        $data = $response->json('data.user') ?? [];

        if (empty($data)) {
            return [];
        }

        return [
            'followers' => $data['follower_count'] ?? 0,
            'following' => $data['following_count'] ?? 0,
            'bio' => $data['bio_description'] ?? '',
            'profile_image' => $data['avatar_url'] ?? '',
            'username' => $data['display_name'] ?? '',
            'name' => $data['display_name'] ?? '',
            'post_count' => $data['video_count'] ?? 0,
            'total_likes' => $data['likes_count'] ?? 0,
            'verified' => $data['is_verified'] ?? false,
        ];
    }

    public function fetchRecentPosts(SocialAccount $account, int $limit = 25): array
    {
        $response = Http::withToken($account->access_token)
            ->post(self::API_BASE . '/video/list/', [
                'max_count' => $limit,
                'fields' => 'id,title,cover_image_url,share_url,like_count,comment_count,share_count,view_count,create_time',
            ]);

        $videos = $response->json('data.videos') ?? [];

        return array_map(function ($video) {
            return [
                'platform_post_id' => $video['id'],
                'post_type' => 'video',
                'caption' => $video['title'] ?? '',
                'thumbnail_url' => $video['cover_image_url'] ?? '',
                'post_url' => $video['share_url'] ?? '',
                'likes' => $video['like_count'] ?? 0,
                'views' => $video['view_count'] ?? 0,
                'comments' => $video['comment_count'] ?? 0,
                'shares' => $video['share_count'] ?? 0,
                'saves' => 0,
                'posted_at' => isset($video['create_time']) ? Carbon::createFromTimestamp($video['create_time']) : null,
            ];
        }, $videos);
    }

    public function fetchMetrics(SocialAccount $account): array
    {
        $profile = $this->fetchProfileData($account);
        $posts = $this->fetchRecentPosts($account);

        $totalLikes = array_sum(array_column($posts, 'likes'));
        $totalViews = array_sum(array_column($posts, 'views'));
        $totalComments = array_sum(array_column($posts, 'comments'));
        $postCount = count($posts);

        $postDates = array_filter(array_map(fn($p) => $p['posted_at'], $posts));

        return [
            'followers' => $profile['followers'] ?? 0,
            'following' => $profile['following'] ?? 0,
            'avg_likes' => $postCount > 0 ? (int) round($totalLikes / $postCount) : 0,
            'avg_views' => $postCount > 0 ? (int) round($totalViews / $postCount) : 0,
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
}
