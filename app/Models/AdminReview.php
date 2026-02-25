<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReview extends Model
{
    protected $fillable = [
        'user_id',
        'social_account_id',
        'admin_id',
        'review_text',
        'ai_analysis',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ai_analysis' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
