<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_score'); // 0-100
            $table->json('category_scores'); // {engagement: 75, consistency: 80, ...}
            $table->json('tips'); // [{category, tip, priority}]
            $table->timestamps();

            $table->index(['social_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_scores');
    }
};
