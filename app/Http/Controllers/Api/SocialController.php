<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    /**
     * Get all supported platforms with their connection status.
     */
    public function platforms(Request $request): JsonResponse
    {
        $user = $request->user();
        $connected = $user->socialAccounts->pluck('platform')->toArray();

        $platforms = array_map(function ($platform) use ($connected) {
            return [
                'id' => $platform,
                'name' => $this->platformName($platform),
                'connected' => in_array($platform, $connected),
            ];
        }, ConnectorFactory::platforms());

        return response()->json($platforms);
    }

    /**
     * Redirect to OAuth provider.
     */
    public function connect(Request $request, string $platform): JsonResponse
    {
        if (!in_array($platform, ConnectorFactory::platforms())) {
            return response()->json(['message' => 'Unknown platform.'], 400);
        }

        // Check free tier limit (1 platform)
        $user = $request->user();
        if (!$user->isPremium() && $user->socialAccounts()->count() >= 1) {
            $existing = $user->socialAccounts->first();
            if ($existing->platform !== $platform) {
                return response()->json([
                    'message' => 'Free tier allows only 1 platform. Upgrade to Premium for all platforms.',
                ], 403);
            }
        }

        $connector = ConnectorFactory::make($platform);
        $driver = $this->getSocialiteDriver($platform);

        $url = $driver
            ->scopes($connector->scopes())
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['redirect_url' => $url]);
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request, string $platform)
    {
        if (!in_array($platform, ConnectorFactory::platforms())) {
            return redirect('/?error=unknown_platform');
        }

        try {
            $socialUser = $this->getSocialiteDriver($platform)
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return redirect('/?error=oauth_failed');
        }

        // Find or create the social account for the authenticated user
        // The user should be authenticated via a session token stored before redirect
        $userId = $request->query('state') ? null : null;

        // For stateless OAuth, we use the token from the query param
        $user = $request->user();
        if (!$user) {
            // Redirect to frontend with token to complete linking
            return redirect('/connect?platform=' . $platform
                . '&oauth_token=' . $socialUser->token
                . '&oauth_id=' . $socialUser->getId()
                . '&oauth_name=' . urlencode($socialUser->getName() ?? $socialUser->getNickname() ?? ''));
        }

        $this->linkAccount($user, $platform, $socialUser);

        return redirect('/dashboard?connected=' . $platform);
    }

    /**
     * Link an OAuth account to the current user (called from frontend after callback).
     */
    public function link(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:' . implode(',', ConnectorFactory::platforms())],
            'oauth_token' => ['required', 'string'],
            'oauth_refresh_token' => ['nullable', 'string'],
            'oauth_id' => ['required', 'string'],
            'oauth_name' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Check free tier limit
        if (!$user->isPremium() && $user->socialAccounts()->count() >= 1) {
            $existing = $user->socialAccounts->first();
            if ($existing->platform !== $validated['platform']) {
                return response()->json([
                    'message' => 'Free tier allows only 1 platform. Upgrade to Premium.',
                ], 403);
            }
        }

        $account = SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'],
            ],
            [
                'platform_user_id' => $validated['oauth_id'],
                'username' => $validated['oauth_name'],
                'access_token' => $validated['oauth_token'],
                'refresh_token' => $validated['oauth_refresh_token'] ?? null,
            ]
        );

        // Fetch initial profile data
        $connector = ConnectorFactory::make($validated['platform']);
        $profileData = $connector->fetchProfileData($account);

        if (!empty($profileData)) {
            $account->update([
                'profile_data' => $profileData,
                'last_synced_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Account connected successfully.',
            'account' => $account->fresh(),
        ], 201);
    }

    /**
     * Get all connected accounts for the current user.
     */
    public function accounts(Request $request): JsonResponse
    {
        $accounts = $request->user()
            ->socialAccounts()
            ->with('latestScore')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'platform' => $account->platform,
                    'platform_name' => $this->platformName($account->platform),
                    'username' => $account->username,
                    'profile_data' => $account->profile_data,
                    'latest_score' => $account->latestScore ? [
                        'overall_score' => $account->latestScore->overall_score,
                        'grade' => $account->latestScore->grade(),
                        'calculated_at' => $account->latestScore->created_at,
                    ] : null,
                    'last_synced_at' => $account->last_synced_at,
                ];
            });

        return response()->json($accounts);
    }

    /**
     * Disconnect a social account.
     */
    public function disconnect(Request $request, int $id): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Account disconnected.']);
    }

    /**
     * Sync profile data for an account.
     */
    public function sync(Request $request, int $id): JsonResponse
    {
        $account = $request->user()->socialAccounts()->findOrFail($id);
        $connector = ConnectorFactory::make($account->platform);

        $profileData = $connector->fetchProfileData($account);

        if (!empty($profileData)) {
            $account->update([
                'profile_data' => $profileData,
                'last_synced_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Profile data synced.',
            'account' => $account->fresh(),
        ]);
    }

    private function getSocialiteDriver(string $platform)
    {
        $driverName = match ($platform) {
            'youtube' => 'google',
            'x' => 'twitter',
            default => $platform,
        };

        return Socialite::driver($driverName);
    }

    private function platformName(string $platform): string
    {
        return match ($platform) {
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'x' => 'X (Twitter)',
            'linkedin' => 'LinkedIn',
            default => ucfirst($platform),
        };
    }

    private function linkAccount($user, string $platform, $socialUser): SocialAccount
    {
        return SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => $platform,
            ],
            [
                'platform_user_id' => $socialUser->getId(),
                'username' => $socialUser->getNickname() ?? $socialUser->getName() ?? '',
                'access_token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken ?? null,
            ]
        );
    }
}
