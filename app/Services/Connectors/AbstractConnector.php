<?php

namespace App\Services\Connectors;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractConnector implements PlatformConnector
{
    /**
     * Make an authenticated API request to the platform.
     */
    protected function apiGet(SocialAccount $account, string $url, array $params = []): array
    {
        $response = Http::withToken($account->access_token)
            ->get($url, $params);

        if ($response->failed()) {
            Log::error("API request failed for {$this->platform()}", [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
                'account_id' => $account->id,
            ]);

            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Calculate engagement rate from metrics.
     */
    protected function calculateEngagementRate(int $totalEngagements, int $followers, int $postCount): float
    {
        if ($followers === 0 || $postCount === 0) {
            return 0.0;
        }

        return round(($totalEngagements / $postCount / $followers) * 100, 4);
    }

    /**
     * Calculate posting frequency (posts per week) from dates.
     */
    protected function calculatePostingFrequency(array $postDates): float
    {
        if (count($postDates) < 2) {
            return 0.0;
        }

        $sorted = collect($postDates)->sort()->values();
        $firstPost = $sorted->first();
        $lastPost = $sorted->last();

        $daysBetween = max(1, $firstPost->diffInDays($lastPost));
        $weeksBetween = $daysBetween / 7;

        return $weeksBetween > 0 ? round(count($postDates) / $weeksBetween, 2) : 0.0;
    }
}
