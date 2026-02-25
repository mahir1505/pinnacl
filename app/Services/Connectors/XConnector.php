<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;
use Carbon\Carbon;

class XConnector extends AbstractConnector
{
    private const API_BASE = 'https://api.x.com/2';

    public function platform(): string
    {
        return 'x';
    }

    public function scopes(): array
    {
        return [
            'tweet.read',
            'users.read',
        ];
    }

    public function fetchProfileData(SocialAccount $account): array
    {
        $data = $this->apiGet($account, self::API_BASE . '/users/me', [
            'user.fields' => 'id,name,username,description,profile_image_url,public_metrics,verified,url',
        ]);

        $user = $data['data'] ?? [];
        $metrics = $user['public_metrics'] ?? [];

        if (empty($user)) {
            return [];
        }

        return [
            'followers' => $metrics['followers_count'] ?? 0,
            'following' => $metrics['following_count'] ?? 0,
            'bio' => $user['description'] ?? '',
            'profile_image' => $user['profile_image_url'] ?? '',
            'username' => $user['username'] ?? '',
            'name' => $user['name'] ?? '',
            'post_count' => $metrics['tweet_count'] ?? 0,
            'verified' => $user['verified'] ?? false,
            'website' => $user['url'] ?? '',
        ];
    }

    public function fetchRecentPosts(SocialAccount $account, int $limit = 25): array
    {
        $userId = $account->platform_user_id;

        $data = $this->apiGet($account, self::API_BASE . "/users/{$userId}/tweets", [
            'max_results' => min($limit, 100),
            'tweet.fields' => 'id,text,created_at,public_metrics,attachments',
            'exclude' => 'retweets,replies',
        ]);

        $tweets = $data['data'] ?? [];

        return array_map(function ($tweet) {
            $metrics = $tweet['public_metrics'] ?? [];

            return [
                'platform_post_id' => $tweet['id'],
                'post_type' => 'photo', // Default, could check attachments
                'caption' => $tweet['text'] ?? '',
                'thumbnail_url' => '',
                'post_url' => 'https://x.com/i/status/' . $tweet['id'],
                'likes' => $metrics['like_count'] ?? 0,
                'views' => $metrics['impression_count'] ?? 0,
                'comments' => $metrics['reply_count'] ?? 0,
                'shares' => $metrics['retweet_count'] ?? 0,
                'saves' => $metrics['bookmark_count'] ?? 0,
                'posted_at' => isset($tweet['created_at']) ? Carbon::parse($tweet['created_at']) : null,
            ];
        }, $tweets);
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
