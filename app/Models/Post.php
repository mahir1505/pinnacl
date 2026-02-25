<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'social_account_id',
        'platform_post_id',
        'post_type',
        'caption',
        'thumbnail_url',
        'post_url',
        'likes',
        'views',
        'comments',
        'shares',
        'saves',
        'posted_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
