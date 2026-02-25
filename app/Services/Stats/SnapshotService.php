<?php

namespace App\Services\Stats;

use App\Models\ScoreSnapshot;
use App\Models\SocialAccount;
use Illuminate\Support\Collection;

class SnapshotService
{
    /**
     * Get snapshots for an account with period filtering.
     *
     * @param string $period day|week|month
     */
    public function getSnapshots(SocialAccount $account, string $period = 'day', ?int $limit = null): Collection
    {
        $query = ScoreSnapshot::where('social_account_id', $account->id)
            ->orderBy('snapshot_date', 'desc');

        // Apply date range based on period
        $query->where('snapshot_date', '>=', match ($period) {
            'day' => now()->subDays(30),
            'week' => now()->subWeeks(12),
            'month' => now()->subMonths(12),
            default => now()->subDays(30),
        });

        if ($limit) {
            $query->limit($limit);
        }

        $snapshots = $query->get();

        // For week/month, aggregate the data
        return match ($period) {
            'week' => $this->aggregateByWeek($snapshots),
            'month' => $this->aggregateByMonth($snapshots),
            default => $snapshots->map(fn($s) => $this->formatSnapshot($s)),
        };
    }

    /**
     * Get growth summary comparing current to previous period.
     */
    public function getGrowthSummary(SocialAccount $account): array
    {
        $now = ScoreSnapshot::where('social_account_id', $account->id)
            ->where('snapshot_date', '>=', now()->subDays(7))
            ->orderBy('snapshot_date', 'desc')
            ->first();

        $previous = ScoreSnapshot::where('social_account_id', $account->id)
            ->where('snapshot_date', '>=', now()->subDays(14))
            ->where('snapshot_date', '<', now()->subDays(7))
            ->orderBy('snapshot_date', 'desc')
            ->first();

        if (!$now) {
            return [
                'followers' => ['current' => 0, 'change' => 0, 'percent' => 0],
                'engagement_rate' => ['current' => 0, 'change' => 0, 'percent' => 0],
                'avg_likes' => ['current' => 0, 'change' => 0, 'percent' => 0],
                'avg_views' => ['current' => 0, 'change' => 0, 'percent' => 0],
            ];
        }

        return [
            'followers' => $this->calcChange($now->followers, $previous?->followers ?? 0),
            'engagement_rate' => $this->calcChange($now->engagement_rate, $previous?->engagement_rate ?? 0),
            'avg_likes' => $this->calcChange($now->avg_likes, $previous?->avg_likes ?? 0),
            'avg_views' => $this->calcChange($now->avg_views, $previous?->avg_views ?? 0),
        ];
    }

    private function calcChange(float $current, float $previous): array
    {
        $change = $current - $previous;
        $percent = $previous > 0 ? round(($change / $previous) * 100, 2) : 0;

        return [
            'current' => $current,
            'change' => $change,
            'percent' => $percent,
        ];
    }

    private function aggregateByWeek(Collection $snapshots): Collection
    {
        return $snapshots->groupBy(fn($s) => $s->snapshot_date->startOfWeek()->toDateString())
            ->map(function ($group, $weekStart) {
                return [
                    'date' => $weekStart,
                    'followers' => (int) round($group->avg('followers')),
                    'following' => (int) round($group->avg('following')),
                    'engagement_rate' => round($group->avg('engagement_rate'), 4),
                    'avg_likes' => (int) round($group->avg('avg_likes')),
                    'avg_views' => (int) round($group->avg('avg_views')),
                    'avg_comments' => (int) round($group->avg('avg_comments')),
                    'total_posts' => (int) $group->max('total_posts'),
                    'posting_frequency' => round($group->avg('posting_frequency'), 2),
                ];
            })->values();
    }

    private function aggregateByMonth(Collection $snapshots): Collection
    {
        return $snapshots->groupBy(fn($s) => $s->snapshot_date->format('Y-m'))
            ->map(function ($group, $month) {
                return [
                    'date' => $month . '-01',
                    'followers' => (int) round($group->avg('followers')),
                    'following' => (int) round($group->avg('following')),
                    'engagement_rate' => round($group->avg('engagement_rate'), 4),
                    'avg_likes' => (int) round($group->avg('avg_likes')),
                    'avg_views' => (int) round($group->avg('avg_views')),
                    'avg_comments' => (int) round($group->avg('avg_comments')),
                    'total_posts' => (int) $group->max('total_posts'),
                    'posting_frequency' => round($group->avg('posting_frequency'), 2),
                ];
            })->values();
    }

    private function formatSnapshot(ScoreSnapshot $snapshot): array
    {
        return [
            'date' => $snapshot->snapshot_date->toDateString(),
            'followers' => $snapshot->followers,
            'following' => $snapshot->following,
            'engagement_rate' => $snapshot->engagement_rate,
            'avg_likes' => $snapshot->avg_likes,
            'avg_views' => $snapshot->avg_views,
            'avg_comments' => $snapshot->avg_comments,
            'total_posts' => $snapshot->total_posts,
            'posting_frequency' => $snapshot->posting_frequency,
        ];
    }
}
