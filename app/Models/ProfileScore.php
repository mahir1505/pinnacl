<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileScore extends Model
{
    protected $fillable = [
        'user_id',
        'social_account_id',
        'overall_score',
        'category_scores',
        'tips',
    ];

    protected function casts(): array
    {
        return [
            'category_scores' => 'array',
            'tips' => 'array',
            'overall_score' => 'integer',
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

    public function grade(): string
    {
        return match (true) {
            $this->overall_score >= 90 => 'A+',
            $this->overall_score >= 80 => 'A',
            $this->overall_score >= 70 => 'B',
            $this->overall_score >= 60 => 'C',
            $this->overall_score >= 50 => 'D',
            default => 'F',
        };
    }
}
