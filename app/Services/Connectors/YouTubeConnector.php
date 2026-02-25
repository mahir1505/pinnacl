<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;
use Carbon\Carbon;

class YouTubeConnector extends AbstractConnector
{
    private const API_BASE = 'https://www.googleapis.com/youtube/v3';

    public function platform(): string
    {
        return 'youtube';
    }

    public function scopes(): array
    {
        return [
            'https://www.googleapis.com/auth/youtube.readonly',
        ];
    }

    public function fetchProfileData(SocialAccount $account): array
    {
        $data = $this->apiGet($account, self::API_BASE . '/channels', [
            'part' => 'snippet,statistics,brandingSettings',
            'mine' => 'true',
        ]);

        $channel = $data['items'][0] ?? null;
        if (!$channel) {
            return [];
        }

        $snippet = $channel['snippet'] ?? [];
        $stats = $channel['statistics'] ?? [];

        return [
            'followers' => (int) ($stats['subscriberCount'] ?? 0),
            'following' => 0,
            'bio' => $snippet['description'] ?? '',
            'profile_image' => $snippet['thumbnails']['default']['url'] ?? '',
            'username' => $snippet['customUrl'] ?? $snippet['title'] ?? '',
            'name' => $snippet['title'] ?? '',
            'post_count' => (int) ($stats['videoCount'] ?? 0),
            'total_views' => (int) ($stats['viewCount'] ?? 0),
            'verified' => false,
        ];
    }

    public function fetchRecentPosts(SocialAccount $account, int $limit = 25): array
    {
        // First get channel's upload playlist
        $channelData = $this->apiGet($account, self::API_BASE . '/channels', [
            'part' => 'contentDetails',
            'mine' => 'true',
        ]);

        $uploadsPlaylistId = $channelData['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
        if (!$uploadsPlaylistId) {
            return [];
        }

        // Get video IDs from playlist
        $playlistData = $this->apiGet($account, self::API_BASE . '/playlistItems', [
            'part' => 'contentDetails',
            'playlistId' => $uploadsPlaylistId,
            'maxResults' => $limit,
        ]);

        $videoIds = array_map(
            fn($item) => $item['contentDetails']['videoId'] ?? '',
            $playlistData['items'] ?? []
        );
        $videoIds = array_filter($videoIds);

        if (empty($videoIds)) {
            return [];
        }

        // Get video details
        $videosData = $this->apiGet($account, self::API_BASE . '/videos', [
            'part' => 'snippet,statistics',
            'id' => implode(',', $videoIds),
        ]);

        return array_map(function ($video) {
            $snippet = $video['snippet'] ?? [];
            $stats = $video['statistics'] ?? [];

            return [
                'platform_post_id' => $video['id'],
                'post_type' => $this->mapVideoType($snippet),
                'caption' => $snippet['title'] ?? '',
                'thumbnail_url' => $snippet['thumbnails']['medium']['url'] ?? '',
                'post_url' => 'https://youtube.com/watch?v=' . $video['id'],
                'likes' => (int) ($stats['likeCount'] ?? 0),
                'views' => (int) ($stats['viewCount'] ?? 0),
                'comments' => (int) ($stats['commentCount'] ?? 0),
                'shares' => 0,
                'saves' => 0,
                'posted_at' => isset($snippet['publishedAt']) ? Carbon::parse($snippet['publishedAt']) : null,
            ];
        }, $videosData['items'] ?? []);
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
            'following' => 0,
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

    private function mapVideoType(array $snippet): string
    {
        // YouTube Shorts are typically < 60 seconds, vertical format
        // We can't reliably detect this from the API alone
        return 'video';
    }
}
