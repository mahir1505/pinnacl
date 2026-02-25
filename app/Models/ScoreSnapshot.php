<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreSnapshot extends Model
{
    protected $fillable = [
        'social_account_id',
        'followers',
        'following',
        'engagement_rate',
        'avg_likes',
        'avg_views',
        'avg_comments',
        'total_posts',
        'posting_frequency',
        'snapshot_date',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'engagement_rate' => 'decimal:4',
            'posting_frequency' => 'decimal:2',
        ];
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
