<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\ScoreSnapshot;
use App\Models\SocialAccount;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSocialAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private SocialAccount $account
    ) {}

    public function handle(): void
    {
        $connector = ConnectorFactory::make($this->account->platform);

        // 1. Sync profile data
        $profileData = $connector->fetchProfileData($this->account);
        if (!empty($profileData)) {
            $this->account->update([
                'profile_data' => $profileData,
                'last_synced_at' => now(),
            ]);
        }

        // 2. Fetch metrics and create daily snapshot
        $metrics = $connector->fetchMetrics($this->account);
        if (!empty($metrics)) {
            ScoreSnapshot::updateOrCreate(
                [
                    'social_account_id' => $this->account->id,
                    'snapshot_date' => now()->toDateString(),
                ],
                [
                    'followers' => $metrics['followers'] ?? 0,
                    'following' => $metrics['following'] ?? 0,
                    'engagement_rate' => $metrics['engagement_rate'] ?? 0,
                    'avg_likes' => $metrics['avg_likes'] ?? 0,
                    'avg_views' => $metrics['avg_views'] ?? 0,
                    'avg_comments' => $metrics['avg_comments'] ?? 0,
                    'total_posts' => $metrics['total_posts'] ?? 0,
                    'posting_frequency' => $metrics['posting_frequency'] ?? 0,
                ]
            );
        }

        // 3. Fetch posts (only for premium users)
        if ($this->account->user->isPremium()) {
            $posts = $connector->fetchRecentPosts($this->account);
            foreach ($posts as $postData) {
                Post::updateOrCreate(
                    [
                        'social_account_id' => $this->account->id,
                        'platform_post_id' => $postData['platform_post_id'],
                    ],
                    [
                        'post_type' => $postData['post_type'] ?? null,
                        'caption' => $postData['caption'] ?? null,
                        'thumbnail_url' => $postData['thumbnail_url'] ?? null,
                        'post_url' => $postData['post_url'] ?? null,
                        'likes' => $postData['likes'] ?? 0,
                        'views' => $postData['views'] ?? 0,
                        'comments' => $postData['comments'] ?? 0,
                        'shares' => $postData['shares'] ?? 0,
                        'saves' => $postData['saves'] ?? 0,
                        'posted_at' => $postData['posted_at'] ?? null,
                        'fetched_at' => now(),
                    ]
                );
            }
        }

        Log::info("Synced social account", [
            'account_id' => $this->account->id,
            'platform' => $this->account->platform,
        ]);
    }
}
