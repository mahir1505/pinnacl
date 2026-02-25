<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'platform_user_id',
        'username',
        'access_token',
        'refresh_token',
        'profile_data',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'profile_data' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scores()
    {
        return $this->hasMany(ProfileScore::class);
    }

    public function latestScore()
    {
        return $this->hasOne(ProfileScore::class)->latestOfMany();
    }

    public function snapshots()
    {
        return $this->hasMany(ScoreSnapshot::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
