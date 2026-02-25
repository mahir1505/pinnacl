<?php

namespace App\Console\Commands;

use App\Models\AdminReview;
use App\Models\ProfileScore;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Stats\SnapshotService;
use Illuminate\Console\Command;

class McpServe extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Start the MCP server for Claude Desktop integration (stdio)';

    private array $tools = [
        'list_users' => [
            'description' => 'List all users with their latest scores',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Search by name or email'],
                    'premium_only' => ['type' => 'boolean', 'description' => 'Only show premium users'],
                ],
            ],
        ],
        'get_user_profile' => [
            'description' => 'Get detailed profile data, scores, and stats for a user',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer', 'description' => 'The user ID'],
                ],
                'required' => ['user_id'],
            ],
        ],
        'get_user_content' => [
            'description' => 'Get recent content/posts with metrics for a user on a platform',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer', 'description' => 'The user ID'],
                    'platform' => ['type' => 'string', 'description' => 'Platform name (instagram, tiktok, youtube, x, linkedin)'],
                ],
                'required' => ['user_id', 'platform'],
            ],
        ],
        'get_user_stats' => [
            'description' => 'Get statistics overview for a user on a platform',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer', 'description' => 'The user ID'],
                    'platform' => ['type' => 'string', 'description' => 'Platform name'],
                ],
                'required' => ['user_id', 'platform'],
            ],
        ],
        'list_pending_reviews' => [
            'description' => 'List premium users waiting for a personal review',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [],
            ],
        ],
        'add_review' => [
            'description' => 'Add a personal review for a user\'s social account',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer', 'description' => 'The user ID'],
                    'social_account_id' => ['type' => 'integer', 'description' => 'The social account ID'],
                    'review_text' => ['type' => 'string', 'description' => 'The review text with recommendations'],
                ],
                'required' => ['user_id', 'social_account_id', 'review_text'],
            ],
        ],
        'update_tips' => [
            'description' => 'Add personalized tips for a user\'s profile score',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer', 'description' => 'The user ID'],
                    'social_account_id' => ['type' => 'integer', 'description' => 'The social account ID'],
                    'tips' => [
                        'type' => 'array',
                        'description' => 'Array of tips to add',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'category' => ['type' => 'string'],
                                'tip' => ['type' => 'string'],
                                'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                            ],
                        ],
                    ],
                ],
                'required' => ['user_id', 'social_account_id', 'tips'],
            ],
        ],
        'get_analytics' => [
            'description' => 'Get platform-wide analytics and statistics',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [],
            ],
        ],
    ];

    public function handle(): void
    {
        // Write to stderr so it doesn't interfere with JSON-RPC on stdout
        fwrite(STDERR, "Pinnacl MCP Server started\n");

        while (true) {
            $line = fgets(STDIN);
            if ($line === false) {
                break;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $request = json_decode($line, true);
            if (!$request) {
                continue;
            }

            $response = $this->handleRequest($request);
            fwrite(STDOUT, json_encode($response) . "\n");
            fflush(STDOUT);
        }
    }

    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $id = $request['id'] ?? null;

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, $request['params'] ?? []),
            'notifications/initialized' => ['jsonrpc' => '2.0'],
            default => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => ['code' => -32601, 'message' => "Method not found: {$method}"],
            ],
        };
    }

    private function handleInitialize(mixed $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => ['tools' => (object) []],
                'serverInfo' => [
                    'name' => 'pinnacl-mcp',
                    'version' => '1.0.0',
                ],
            ],
        ];
    }

    private function handleToolsList(mixed $id): array
    {
        $toolList = [];
        foreach ($this->tools as $name => $definition) {
            $toolList[] = [
                'name' => $name,
                'description' => $definition['description'],
                'inputSchema' => $definition['inputSchema'],
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => ['tools' => $toolList],
        ];
    }

    private function handleToolsCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        try {
            $result = match ($toolName) {
                'list_users' => $this->toolListUsers($arguments),
                'get_user_profile' => $this->toolGetUserProfile($arguments),
                'get_user_content' => $this->toolGetUserContent($arguments),
                'get_user_stats' => $this->toolGetUserStats($arguments),
                'list_pending_reviews' => $this->toolListPendingReviews(),
                'add_review' => $this->toolAddReview($arguments),
                'update_tips' => $this->toolUpdateTips($arguments),
                'get_analytics' => $this->toolGetAnalytics(),
                default => throw new \RuntimeException("Unknown tool: {$toolName}"),
            };

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT)],
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => "Error: {$e->getMessage()}"],
                    ],
                    'isError' => true,
                ],
            ];
        }
    }

    private function toolListUsers(array $args): array
    {
        $query = User::with(['socialAccounts' => function ($q) {
            $q->with(['scores' => function ($sq) {
                $sq->latest()->limit(1);
            }]);
        }]);

        if (!empty($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($args['premium_only'])) {
            $query->where('is_premium', true);
        }

        return $query->orderBy('created_at', 'desc')->limit(50)->get()->map(function (User $user) {
            $accounts = $user->socialAccounts->map(function ($account) {
                $score = $account->scores->first();
                return [
                    'id' => $account->id,
                    'platform' => $account->platform,
                    'username' => $account->username,
                    'score' => $score ? $score->overall_score : null,
                    'grade' => $score ? $score->grade() : null,
                ];
            });

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_premium' => $user->isPremium(),
                'accounts' => $accounts,
            ];
        })->toArray();
    }

    private function toolGetUserProfile(array $args): array
    {
        $user = User::with(['socialAccounts' => function ($q) {
            $q->with(['scores' => function ($sq) {
                $sq->latest()->limit(1);
            }]);
        }])->findOrFail($args['user_id']);

        $profiles = $user->socialAccounts->map(function ($account) {
            $score = $account->scores->first();
            return [
                'id' => $account->id,
                'platform' => $account->platform,
                'username' => $account->username,
                'profile_data' => $account->profile_data,
                'last_synced_at' => $account->last_synced_at,
                'score' => $score ? [
                    'overall_score' => $score->overall_score,
                    'grade' => $score->grade(),
                    'category_scores' => $score->category_scores,
                    'tips' => $score->tips,
                    'calculated_at' => $score->created_at->toIso8601String(),
                ] : null,
            ];
        });

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_premium' => $user->isPremium(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'profiles' => $profiles->toArray(),
        ];
    }

    private function toolGetUserContent(array $args): array
    {
        $user = User::findOrFail($args['user_id']);
        $account = $user->socialAccounts()->where('platform', $args['platform'])->firstOrFail();

        $posts = $account->posts()
            ->orderBy('posted_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'type' => $post->post_type,
                    'caption' => $post->caption,
                    'likes' => $post->likes,
                    'views' => $post->views,
                    'comments' => $post->comments,
                    'shares' => $post->shares,
                    'posted_at' => $post->posted_at,
                ];
            });

        return [
            'account' => $account->username,
            'platform' => $account->platform,
            'post_count' => $posts->count(),
            'posts' => $posts->toArray(),
        ];
    }

    private function toolGetUserStats(array $args): array
    {
        $user = User::findOrFail($args['user_id']);
        $account = $user->socialAccounts()->where('platform', $args['platform'])->firstOrFail();

        $snapshotService = app(SnapshotService::class);
        $growth = $snapshotService->getGrowthSummary($account);

        $latestSnapshot = $account->snapshots()->latest('snapshot_date')->first();

        return [
            'account' => $account->username,
            'platform' => $account->platform,
            'current' => $latestSnapshot ? [
                'followers' => $latestSnapshot->followers,
                'following' => $latestSnapshot->following,
                'engagement_rate' => $latestSnapshot->engagement_rate,
                'avg_likes' => $latestSnapshot->avg_likes,
                'avg_views' => $latestSnapshot->avg_views,
                'total_posts' => $latestSnapshot->total_posts,
            ] : null,
            'growth' => $growth,
        ];
    }

    private function toolListPendingReviews(): array
    {
        $premiumUsers = User::where('is_premium', true)
            ->with('socialAccounts')
            ->get();

        $pending = [];
        foreach ($premiumUsers as $user) {
            foreach ($user->socialAccounts as $account) {
                $lastReview = AdminReview::where('user_id', $user->id)
                    ->where('social_account_id', $account->id)
                    ->latest()
                    ->first();

                if (!$lastReview || $lastReview->created_at->lt(now()->subDays(30))) {
                    $latestScore = $account->scores()->latest()->first();
                    $pending[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'social_account_id' => $account->id,
                        'platform' => $account->platform,
                        'username' => $account->username,
                        'score' => $latestScore?->overall_score,
                        'grade' => $latestScore?->grade(),
                        'last_review_at' => $lastReview?->created_at?->toIso8601String(),
                    ];
                }
            }
        }

        return $pending;
    }

    private function toolAddReview(array $args): string
    {
        // Use superadmin (ID 1) as default admin for MCP reviews
        $admin = User::where('role', 'superadmin')->first() ?? User::first();

        $review = AdminReview::create([
            'user_id' => $args['user_id'],
            'social_account_id' => $args['social_account_id'],
            'admin_id' => $admin->id,
            'review_text' => $args['review_text'],
            'ai_analysis' => ['source' => 'mcp', 'created_via' => 'claude_desktop'],
            'status' => 'completed',
        ]);

        return "Review #{$review->id} created successfully for user #{$args['user_id']}.";
    }

    private function toolUpdateTips(array $args): string
    {
        $account = SocialAccount::findOrFail($args['social_account_id']);
        $score = $account->scores()->latest()->first();

        if (!$score) {
            throw new \RuntimeException('No score found for this account. Calculate a score first.');
        }

        $existingTips = $score->tips ?? [];
        $newTips = array_merge($existingTips, $args['tips']);
        $score->tips = $newTips;
        $score->save();

        return "Added " . count($args['tips']) . " tips to the latest score. Total tips: " . count($newTips);
    }

    private function toolGetAnalytics(): array
    {
        return [
            'total_users' => User::count(),
            'premium_users' => User::where('is_premium', true)->count(),
            'total_accounts' => SocialAccount::count(),
            'total_scores' => ProfileScore::count(),
            'avg_score' => round(ProfileScore::avg('overall_score') ?? 0, 1),
            'platform_breakdown' => SocialAccount::selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform')
                ->toArray(),
            'total_reviews' => AdminReview::count(),
            'recent_signups_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }
}
